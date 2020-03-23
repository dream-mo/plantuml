<?php

namespace Dreammo\Plantuml\Helper;

use DocBlockReader\Reader;

/**
 * Class PlantUMLWriter
 * @package Dreammo\Plantuml\Helper
 *
 * Generate plantuml file class
 *
 */
class PlantUMLWriter
{
    /**
     * @var array
     *
     * All classes scanned
     *
     */
    private $originClassNames = [];

    /**
     * PlantUMLWriter constructor.
     *
     * @param $sourcePath
     *
     * Path to the source directory to be analyzed
     *
     */
    public function __construct($sourcePath)
    {
        // Get an array of class names (full text qualified names) that have not been included in the source directory php files
        $oldClasses = get_declared_classes();

        // include scan all php files in the target source directory
        $sourcePath = rtrim($sourcePath, '\\');
        $sourcePath = rtrim($sourcePath, '/');
        $this->scanDirFolder($sourcePath);

        // Get the total defined class name (full text qualified name) array after the current include
        $allClasses = get_declared_classes();

        // Find the difference set and get an array of all the class names defined by the source code
        $diffClassNames = array_diff($allClasses, $oldClasses);

        // Exclude Composer classes
        foreach ($diffClassNames as $diffClassName) {
            if (strpos($diffClassName, 'Composer') !== false) {
                continue;
            }
        }

        // Save the classNames to be processed for analysis
        $this->originClassNames = $diffClassNames;
    }

    /**
     * @param $targetFileName
     * @throws \ReflectionException
     *
     * Generate plantuml file
     *
     */
    public function write($targetFileName)
    {
        $plantUmlClasses = $this->getTransformedClass();
        $this->draw($plantUmlClasses, $targetFileName);
    }

    /**
     * @return array
     * @throws \ReflectionException
     * @throws \Exception
     *
     * Converting classes and related interfaces
     *
     */
    private function getTransformedClass()
    {
        $plantUmlClasses = [];

        foreach ($this->originClassNames as $originClassName) {

            $reflectionClass = new \ReflectionClass($originClassName);

            $parentClass = $reflectionClass->getParentClass();
            $parentClassUml = null;
            if ($parentClass) {
                $this->originClassNames[] = $parentClass->getName();
                $reflectionParent = new \ReflectionClass($parentClass->getName());
                $parentClassUml = $this->transformClassNames($reflectionParent, $plantUmlClasses);
            }

            $implementsInterfacesUmls = [];
            foreach ($reflectionClass->getInterfaces() as $interface) {
                $this->originClassNames[] = $interface->getName();
                $reflectionInterface = new \ReflectionClass($interface->getName());
                $implementsInterfacesUml = $this->transformClassNames($reflectionInterface, $plantUmlClasses);
                $implementsInterfacesUmls[] = $implementsInterfacesUml;
            }

            $selfPlantUmlClass = $this->transformClassNames($reflectionClass, $plantUmlClasses);
            if ($selfPlantUmlClass) {
                if ($parentClass) {
                    $selfPlantUmlClass->setParentClass($parentClassUml);
                }
                if ($implementsInterfacesUmls) {
                    $selfPlantUmlClass->setImplementsInterfaces($implementsInterfacesUmls);
                }
            }
        }

        return $plantUmlClasses;
    }


    /**
     * @param $plantUmlClasses
     * @param $targetFileName
     *
     * Draw palntuml text
     *
     */
    private function draw($plantUmlClasses, $targetFileName)
    {
        $uml = <<<STR
@startuml

%s

@enduml
STR;
        $allClassStr = '';

        // Handle the definition of each Class
        foreach ($plantUmlClasses as $plantUmlClass) {
            $allClassStr .= $plantUmlClass->getClassUmlString() . PHP_EOL;
        }

        // Handling class relationship definitions
        $extendsStr = '';
        foreach ($plantUmlClasses as $plantUmlClass) {
            $extendsStr .= $plantUmlClass->getClassRelationString($this->originClassNames) . PHP_EOL;
        }

        // Handling method dependency definitions
        foreach ($plantUmlClasses as $plantUmlClass) {
            $extendsStr .= $plantUmlClass->getMethodClassRelationString($this->originClassNames);
        }

        $allClassStr .= $extendsStr;
        $uml = sprintf($uml, $allClassStr);

        // Write to file
        file_put_contents($targetFileName, $uml);

        echo "Congratulations! Successfully generated" . PHP_EOL;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param $plantUmlClasses
     * @return PlantUMLClass
     * @throws \Exception
     *
     */
    private function transformClassNames(\ReflectionClass $reflectionClass, &$plantUmlClasses)
    {
        $packageName = $reflectionClass->getNamespaceName();

        $plantUmlClass = new PlantUMLClass($packageName, $reflectionClass->getName());
        $plantUmlProperties = $this->transformClassProperties($reflectionClass);
        $plantUmlMethods = $this->transformClassMethods($reflectionClass);

        if ($reflectionClass->isAbstract()) {
            $plantUmlClass->setIsAbstract(true);
        }

        if ($reflectionClass->isInterface()) {
            $plantUmlClass->setIsInterface(true);
        }

        $plantUmlClass->setAttrs($plantUmlProperties);
        $plantUmlClass->setMethods($plantUmlMethods);

        if (empty($plantUmlClasses[$plantUmlClass->getName()])) {
            $plantUmlClasses[$plantUmlClass->getName()] = $plantUmlClass;
        }

        return $plantUmlClass;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @return array
     * @throws \Exception
     *
     * Methods of Conversion Class
     *
     */
    private function transformClassMethods(\ReflectionClass $reflectionClass)
    {
        $plantUmlMethods = [];

        //methods
        $methods = $reflectionClass->getMethods();
        foreach ($methods as $method) {

            // Ignore magic methods
            if (strpos($method->getName(), "__") !== false) {
                continue;
            }

            // Get annotation on method @return
            $reader = new Reader($reflectionClass->getName(), $method->getName());
            // Data types obtained through annotations
            $returnValType = $reader->getParameter('return');
            $methodName = $method->getName();

            // Access modifier
            $accessLevel = 'public';
            if ($method->isPrivate()) {
                $accessLevel = 'private';
            } else if ($method->isProtected()) {
                $accessLevel = 'protected';
            }

            // Formal parameter
            $params = $reader->getVariableDeclarations('param');

            $plantUmlParams = [];
            foreach ($params as $param) {
                if ($param == $returnValType) {
                    continue;
                } else {
                    $plantUmlParam = new PlantUMLMethodParam($param['type'], $param['name'], '');
                    $plantUmlParams[] = $plantUmlParam;
                }
            }

            $plantUmlMethod = new PlantUMLMethod($methodName, $plantUmlParams, $returnValType, $accessLevel);
            $plantUmlMethods[] = $plantUmlMethod;
        }

        return $plantUmlMethods;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @return array
     * @throws \Exception
     *
     * Transforming Class Properties
     *
     */
    private function transformClassProperties(\ReflectionClass $reflectionClass)
    {
        $plantUmlProperties = [];

        // Attribute analysis
        $attrs = $reflectionClass->getProperties();
        foreach ($attrs as $attr) {

            // Get the annotation on the property @var
            $reader = new Reader($reflectionClass->getName(), $attr->getName(), 'property');
            // The data type obtained through the annotation
            $valType = $reader->getParameter('var');

            $attrName = $attr->getName();
            $accessLevel = 'public';

            if ($attr->isPrivate()) {
                $accessLevel = 'private';
            } else if ($attr->isProtected()) {
                $accessLevel = 'protected';
            }

            $plantUmlProperty = new PlantUMLProperty($attrName, $valType, $accessLevel);
            $plantUmlProperties[] = $plantUmlProperty;
        }

        return $plantUmlProperties;
    }


    /**
     * @param $path
     *
     * Scan source directory include source files
     *
     */
    private function scanDirFolder($path)
    {
        $temp_list = scandir($path);
        foreach ($temp_list as $file) {
            // exclude the root directory
            if ($file != ".." && $file != ".") {
                if (is_dir($path . "/" . $file)) {
                    // 子文件夹，进行递归
                    $this->scandirFolder($path . "/" . $file);
                } else {
                    // 根目录下的文件
                    $filePath = $path . '/' . $file;
                    if (pathinfo($filePath)['extension'] == 'php') {
                        @include_once $filePath;
                    }
                }
            }
        }
    }
}