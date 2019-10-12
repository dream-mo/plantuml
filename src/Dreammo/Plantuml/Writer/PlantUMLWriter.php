<?php

namespace Dreammo\Plantuml\Helper;

use DocBlockReader\Reader;

/**
 * Class PlantUMLWriter
 * @package Dreammo\Plantuml\Helper
 *
 * 生成plantuml文件类
 *
 */
class PlantUMLWriter
{
    /**
     * @var array
     *
     * 扫描到的所有类
     */
    private $originClassNames = [];

    /**
     * PlantUMLWriter constructor.
     * @param $sourcePath
     *
     * 传入需要分析的源码目录的路径
     *
     */
    public function __construct($sourcePath)
    {
        // 获取还未include源码目录php文件 已经定义好的类名(全文限定名)数组
        $oldClasses = get_declared_classes();

        // include扫描的目标源码目录下的所有php文件
        $sourcePath = rtrim($sourcePath, '\\');
        $sourcePath = rtrim($sourcePath, '/');
        $this->scanDirFolder($sourcePath);

        // 获取当前include之后总的定义好的类名(全文限定名)数组
        $allClasses = get_declared_classes();

        // 求差集 拿到源码定义好的所有类名数组
        $diffClassNames = array_diff($allClasses, $oldClasses);

        // 排除Composer的类
        foreach ($diffClassNames as $diffClassName) {
            if (strpos($diffClassName, 'Composer') !== false) {
                continue;
            }
        }

        // 保存要处理分析的classNames
        $this->originClassNames = $diffClassNames;
    }

    /**
     * @param $targetFileName
     * @throws \ReflectionException
     *
     * 生成plantuml文件
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
     *
     * 将class以及涉及到的interface转换
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
     * 绘制palntuml文本
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

        // 处理每个Class的定义
        foreach ($plantUmlClasses as $plantUmlClass) {
            $allClassStr .= $plantUmlClass->getClassUmlString().PHP_EOL;
        }

        // 处理class关系定义
        $extendsStr = '';
        foreach ($plantUmlClasses as $plantUmlClass) {
            $extendsStr .= $plantUmlClass->getClassRelationString($this->originClassNames).PHP_EOL;
        }

        // 处理method依赖关系定义
        foreach ($plantUmlClasses as $plantUmlClass) {
            $extendsStr .= $plantUmlClass->getMethodClassRelationString($this->originClassNames);
        }

        $allClassStr .= $extendsStr;
        $uml = sprintf($uml, $allClassStr);

        // 写入文件
        file_put_contents( $targetFileName, $uml);

        echo "Successfully generated".PHP_EOL;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param $plantUmlClasses
     * @return PlantUMLClass
     * @throws \Exception
     *
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
     * 转换类的方法
     *
     */
    private function transformClassMethods(\ReflectionClass $reflectionClass)
    {
        $plantUmlMethods = [];

        //方法
        $methods = $reflectionClass->getMethods();
        foreach ($methods as $method) {

            // 忽略魔术方法
            if (strpos($method->getName(), "__") !== false) {
                continue;
            }

            // 获取方法上面的注解@return
            $reader = new Reader($reflectionClass->getName(), $method->getName());
            $returnValType = $reader->getParameter('return'); // 通过注解获取到的数据类型
            $methodName = $method->getName();

            // 访问修饰符
            $accessLevel = 'public';
            if ($method->isPrivate()) {
                $accessLevel = 'private';
            }else if ($method->isProtected()) {
                $accessLevel = 'protected';
            }

            // 形参
            $params = $reader->getVariableDeclarations('param');

            $plantUmlParams = [];
            foreach ($params as $param) {
                if ($param == $returnValType) {
                    continue;
                }else{
                    $plantUmlParam = new PlantUMLMethodParam($param['type'], $param['name'], '');
                    $plantUmlParams[] = $plantUmlParam;
                }
            }

            $plantUmlMethod = new PlantUMLMethod($methodName, $plantUmlParams,$returnValType, $accessLevel);
            $plantUmlMethods[] = $plantUmlMethod;
        }

        return $plantUmlMethods;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @return array
     * @throws \Exception
     *
     * 转换类的属性
     *
     */
    private function transformClassProperties(\ReflectionClass $reflectionClass)
    {
        $plantUmlProperties = [];

        // 属性分析
        $attrs = $reflectionClass->getProperties();
        foreach ($attrs as $attr) {

            // 获取属性上面的注解@var
            $reader = new Reader($reflectionClass->getName(), $attr->getName(),'property');
            $valType = $reader->getParameter('var'); // 通过注解获取到的数据类型

            $attrName = $attr->getName();
            $accessLevel = 'public';

            if ($attr->isPrivate()) {
                $accessLevel = 'private';
            }else if ($attr->isProtected()) {
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
     * 扫描源码目录 include源码文件
     *
     */
    private function scanDirFolder($path)
    {
        $temp_list = scandir($path);
        foreach ($temp_list as $file) {
            //排除根目录
            if ($file != ".." && $file != ".") {
                if (is_dir($path . "/" . $file)) {
                    //子文件夹，进行递归
                    $this->scandirFolder($path . "/" . $file);
                } else {
                    //根目录下的文件
                    $filePath = $path.'/'.$file;
                    if (pathinfo($filePath)['extension'] == 'php') {
                        @include_once $filePath;
                    }
                }
            }
        }
    }
}