<?php

namespace Dreammo\Plantuml\Helper;

use DocBlockReader\Reader;

/**
 * Class PlantUMLClass
 * @package Dreammo\Plantuml\Helper
 *
 * PlantUMLClass
 *
 */
class PlantUMLClass
{
    /**
     * @var string
     *
     * 类名
     *
     */
    private $name;

    private $package;

    /**
     * @var PlantUMLProperty[]
     *
     * data object collection
     *
     */
    private $attrs = [];

    /**
     * @var PlantUMLMethod[]
     *
     * function collection
     *
     */
    private $methods = [];

    /**
     * @var PlantUMLClass
     *
     * parent class
     *
     */
    private $parentClass = null;

    /**
     * @var PlantUMLClass[]
     *
     * implement interface classes
     *
     */
    private $implementsInterfaces = [];

    /**
     * @var bool
     *
     * is or not abstract class
     *
     */
    private $isAbstract = false;

    /**
     * @var bool
     *
     * is or not interface
     *
     */
    private $isInterface = false;

    /**
     * PlantUMLClass constructor.
     * @param string $package
     * @param string $name
     *
     */
    public function __construct($package, $name)
    {
        $this->package = $package;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        $name = str_replace("\\", '.', $this->name);
        return $name;
    }

    /**
     * @param string $name
     *
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return PlantUMLProperty[]
     *
     */
    public function getAttrs()
    {
        return $this->attrs;
    }

    /**
     * @param PlantUMLProperty[] $attrs
     *
     */
    public function setAttrs($attrs)
    {
        $this->attrs = $attrs;
    }

    /**
     * @return PlantUMLMethod[]
     *
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @param PlantUMLMethod[] $methods
     *
     */
    public function setMethods($methods)
    {
        $this->methods = $methods;
    }

    /**
     * @return PlantUMLClass
     *
     */
    public function getParentClass()
    {
        return $this->parentClass;
    }

    /**
     * @param PlantUMLClass $parentClass
     *
     */
    public function setParentClass($parentClass)
    {
        $this->parentClass = $parentClass;
    }

    /**
     * @return PlantUMLClass[]
     *
     */
    public function getImplementsInterfaces()
    {
        return $this->implementsInterfaces;
    }

    /**
     * @param PlantUMLClass[] $implementsInterfaces
     *
     */
    public function setImplementsInterfaces($implementsInterfaces)
    {
        $this->implementsInterfaces = $implementsInterfaces;
    }

    /**
     * @return mixed
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * @param mixed $package
     *
     */
    public function setPackage($package)
    {
        $this->package = $package;
    }

    /**
     * @return bool
     *
     */
    public function isAbstract()
    {
        return $this->isAbstract;
    }

    /**
     * @param bool $isAbstract
     *
     */
    public function setIsAbstract($isAbstract)
    {
        $this->isAbstract = $isAbstract;
    }

    /**
     * @return bool
     *
     */
    public function isInterface()
    {
        return $this->isInterface;
    }

    /**
     * @param bool $isInterface
     *
     */
    public function setIsInterface($isInterface)
    {
        $this->isInterface = $isInterface;
    }

    /**
     * @return string
     *
     * get current class trans to plantuml string
     *
     */
    public function getClassUmlString()
    {
        // attr
        $attrStr = '';
        foreach ($this->getAttrs() as $attr) {
            $attrStr .= $attr->getAccessLevel() . $attr->getName() . ':' . $attr->getDataType() . PHP_EOL;
        }

        // function
        $methodStr = '';
        foreach ($this->getMethods() as $method) {
            $methodStr .= $method->getAccessLevel() . $method->getName() . '(';
            $paramStrs = '';
            foreach ($method->getParams() as $param) {
                $paramStrs .= $param->getName() . ':' . $param->getDataType() . ', ';
            }
            $paramStrs = rtrim($paramStrs, ', ');
            $methodStr .= $paramStrs . '):' . $method->getReturnDataType() . PHP_EOL;
        }

        // class inner data
        $classBodyStr = $attrStr . $methodStr;

        // whole string
        $wholeClassUml = sprintf($this->getTemplateString(), $classBodyStr);

        return $wholeClassUml;
    }

    /**
     * @param $originClassNames
     * @return string
     * @throws \Exception
     *
     * Get the description of the relationship between the plantuml strings
     *
     */
    public function getClassRelationString($originClassNames)
    {
        // extends relation
        $extendsStr = '';

        if ($this->getParentClass()) {
            if ($this->isAbstract()) {
                $className = 'abstract class ' . $this->getName();
            } else if ($this->isInterface()) {
                $className = 'interface ' . $this->getName();
            } else {
                $className = 'class ' . $this->getName();
            }
            $extendsStr .= $className . ' extends ' . $this->getParentClass()->getName() . PHP_EOL;
        }

        // Realize relationship
        foreach ($this->getImplementsInterfaces() as $implementsInterface) {
            if ($implementsInterface) {
                if ($this->isAbstract()) {
                    $className = 'abstract class ' . $this->getName();
                } else if ($this->isInterface()) {
                    $className = 'interface ' . $this->getName();
                } else {
                    $className = 'class ' . $this->getName();
                }
                $extendsStr .= $className . ' implements ' . $implementsInterface->getName() . PHP_EOL;
            }
        }

        // Combination relationship , Aggregate relationship ,Ordinary association relationship
        $attrRelation = [];
        foreach ($this->attrs as $attr) {
            $reader = new Reader($this->name, $attr->getName(), 'property');
            $aggregationClass = $reader->getParameter('Agg'); //   Aggregate relationship
            $compositionClass = $reader->getParameter('Comp'); //  Combination relationship
            $associationClass = $reader->getParameter('Assoc'); // Ordinary association relationship

            $aggStr = $this->handleRelation($aggregationClass, $originClassNames, 'o--');
            $compStr = $this->handleRelation($compositionClass, $originClassNames, '*--');
            $assocStr = $this->handleRelation($associationClass, $originClassNames, '--');
            $attrRelation[] = $aggStr;
            $attrRelation[] = $compStr;
            $attrRelation[] = $assocStr;
        }

        $attrRelation = implode("", $attrRelation);
        $extendsStr .= $attrRelation;

        return $extendsStr;
    }

    /**
     * @param $relationClassAnnotation
     * @param $originClassNames
     * @param $flags
     * @return string
     *
     * handle relation
     *
     */
    private function handleRelation($relationClassAnnotation, $originClassNames, $flags)
    {
        $attrRelation = '';

        if (!$relationClassAnnotation) {
            return $attrRelation;
        }

        $note = [];
        if (strpos($relationClassAnnotation, '[]') !== false) {
            $note[] = '"1"';
            $note[] = '"many"';
        } else {
            $note[] = '"1"';
            $note[] = '"1"';
        }

        $relationClassAnnotation = str_replace("[]", '', $relationClassAnnotation);
        if (strpos($relationClassAnnotation, '\\') === false && $relationClassAnnotation) {
            foreach ($originClassNames as $originClassName) {
                // At the end of the class name, get the full text qualified class name.
                // For the temporary prerequisite, there is no duplicate class name between multiple packages.
                if (strchr($originClassName, $relationClassAnnotation) == $relationClassAnnotation) {
                    $relationClassAnnotation = $originClassName;
                    break;
                }
            }
        }

        if ($relationClassAnnotation && in_array($relationClassAnnotation, $originClassNames, true)) {
            $relationClassName = str_replace("\\", '.', $relationClassAnnotation);
            $attrRelation = $this->getName() . " {$note[0]}  {$flags} {$note[1]} " . $relationClassName . PHP_EOL;
        }

        return $attrRelation;
    }

    /**
     * @param $originClassNames
     * @return string
     *
     * Get method parameter class dependencies
     *
     */
    public function getMethodClassRelationString($originClassNames)
    {
        // Method dependency
        $dependStr = '';
        foreach ($this->methods as $method) {
            $params = $method->getParams();
            foreach ($params as $param) {
                if (in_array($param->getDataType(), $originClassNames, true)) {
                    $dependName = str_replace('\\', '.', $param->getDataType());
                    $dependStr .= $this->getName() . ' --> ' . $dependName . PHP_EOL;
                }
            }
        }

        return $dependStr;
    }

    /**
     * @return string
     *
     * Get palntuml template based on class type
     *
     */
    private function getTemplateString()
    {
        if ($this->isInterface()) {
            $classUml = <<<STR
interface {$this->getName()}
{
%s
}
STR;
        } else if ($this->isAbstract()) {
            $classUml = <<<STR
abstract class {$this->getName()}
{
%s
}
STR;
        } else {
            $classUml = <<<STR
class {$this->getName()}
{
%s
}
STR;
        }

        return $classUml;
    }
}