<?php

namespace Dreammo\Plantuml\Helper;

use DocBlockReader\Reader;

/**
 * Class PlantUMLClass
 * @package Dreammo\Plantuml\Helper
 *
 * plantuml的Class类
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
     * 数据对象集合
     *
     */
    private $attrs = [];

    /**
     * @var PlantUMLMethod[]
     *
     * 方法集合
     *
     */
    private $methods = [];


    /**
     * @var PlantUMLClass
     *
     * 父类
     *
     */
    private $parentClass = null;

    /**
     * @var PlantUMLClass[]
     *
     * 实现接口类
     *
     */
    private $implementsInterfaces = [];

    /**
     * @var bool
     *
     * 是否是抽象类
     */
    private $isAbstract = false;

    /**
     * @var bool
     *
     * 是否是接口类型
     *
     */
    private $isInterface = false;


    /**
     * PlantUMLClass constructor.
     * @param string $package
     * @param string $name
     *
     */
    public function __construct($package , $name)
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
     * 获取当前class转换为plantuml之后的字符串
     *
     */
    public function getClassUmlString()
    {
        // 属性
        $attrStr = '';
        foreach ($this->getAttrs() as $attr) {
            $attrStr .= $attr->getAccessLevel().$attr->getName().':'.$attr->getDataType().PHP_EOL;
        }

        // 方法
        $methodStr = '';
        foreach ($this->getMethods() as $method) {
            $methodStr .= $method->getAccessLevel().$method->getName().'(';
            $paramStrs = '';
            foreach ($method->getParams() as $param) {
                $paramStrs .= $param->getName().':'.$param->getDataType().', ';
            }
            $paramStrs = rtrim($paramStrs, ', ');
            $methodStr .= $paramStrs.'):'.$method->getReturnDataType().PHP_EOL;
        }

        // class内部数据
        $classBodyStr = $attrStr.$methodStr;

        // 整体数据
        $wholeClassUml = sprintf($this->getTemplateString(), $classBodyStr);

        return $wholeClassUml;
    }

    /**
     * @param $originClassNames
     * @return string
     * @throws Exception
     *
     * 获取类之间关系描述plantuml字符串
     *
     */
    public function getClassRelationString($originClassNames)
    {
        // 继承关系
        $extendsStr = '';

        if ($this->getParentClass()) {
            if ($this->isAbstract()) {
                $className = 'abstract class '.$this->getName();
            }else if ($this->isInterface()) {
                $className = 'interface '.$this->getName();
            }else{
                $className = 'class '.$this->getName();
            }
            $extendsStr .= $className.' extends '.$this->getParentClass()->getName().PHP_EOL;
        }

        // 实现关系
        foreach ($this->getImplementsInterfaces() as $implementsInterface) {
            if ($implementsInterface) {
                if ($this->isAbstract()) {
                    $className = 'abstract class '.$this->getName();
                }else if ($this->isInterface()) {
                    $className = 'interface '.$this->getName();
                }else{
                    $className = 'class '.$this->getName();
                }
                $extendsStr .= $className.' implements '.$implementsInterface->getName().PHP_EOL;
            }
        }

        // 组合关系 聚合关系 普通关联关系
        $attrRelation = [];
        foreach ($this->attrs as $attr) {
            $reader = new Reader($this->name, $attr->getName(), 'property');
            $aggregationClass = $reader->getParameter('Agg'); // 聚合类型
            $compositionClass = $reader->getParameter('Comp'); // 组合类型
            $associationClass = $reader->getParameter('Assoc'); // 普通关联关系

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
     * 处理关系
     *
     */
    private function handleRelation($relationClassAnnotation, $originClassNames, $flags)
    {
        $attrRelation = '';

        if (!$relationClassAnnotation) {
            return $attrRelation;
        }



        $note  = [];
        if (strpos($relationClassAnnotation, '[]') !== false) {
            $note[] = '"1"';
            $note[] = '"many"';
        }else{
            $note[] = '"1"';
            $note[] = '"1"';
        }

        $relationClassAnnotation = str_replace("[]", '', $relationClassAnnotation);
        if (strpos($relationClassAnnotation, '\\') === false && $relationClassAnnotation) {
            foreach ($originClassNames as $originClassName) {
                // 类名称结尾 则拿到全文限定类名称  暂时前提条件时 多个包之间没有重复类名
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
     * 获取方法参数类依赖关系
     *
     */
    public function getMethodClassRelationString($originClassNames)
    {
        //方法依赖关系
        $dependStr = '';
        foreach ($this->methods as $method) {
            $params = $method->getParams();
            foreach ($params as $param) {
                if (in_array($param->getDataType(),$originClassNames, true)) {
                    $dependName = str_replace('\\','.',$param->getDataType());
                    $dependStr .= $this->getName().' --> '.$dependName.PHP_EOL;
                }
            }

        }

        return $dependStr;
    }

    /**
     * @return string
     *
     * 根据class的类型获取palntuml模板
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
        }else if ($this->isAbstract()) {
            $classUml = <<<STR
abstract class {$this->getName()}
{
%s
}
STR;
        } else{
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