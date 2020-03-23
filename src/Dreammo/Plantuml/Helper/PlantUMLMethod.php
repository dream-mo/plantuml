<?php

namespace Dreammo\Plantuml\Helper;

/**
 * Class PlantUMLMethod
 * @package Dreammo\Plantuml\Helper
 *
 * plantuml method class
 *
 */
class PlantUMLMethod
{
    /**
     * @var $name
     *
     */
    private $name;

    /**
     * @var PlantUMLProperty[]
     *
     */
    private $params = [];
    private $returnDataType = '';
    private $accessLevel = '';

    /**
     * PlantUMLMethod constructor.
     * @param $name
     * @param PlantUMLProperty[] $params
     * @param string $returnDataType
     * @param string $accessLevel
     */
    public function __construct($name, array $params, $returnDataType, $accessLevel)
    {
        $this->name = $name;
        $this->params = $params;
        $this->returnDataType = $returnDataType;
        $this->accessLevel = $accessLevel;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return PlantUMLProperty[]
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * @return string
     */
    public function getReturnDataType()
    {
        return $this->returnDataType;
    }

    /**
     * @param string $returnDataType
     */
    public function setReturnDataType($returnDataType)
    {
        $this->returnDataType = $returnDataType;
    }

    /**
     * @return string
     */
    public function getAccessLevel()
    {
        switch ($this->accessLevel) {
            case 'public':
            {
                return '+';
            }
            case 'private':
            {
                return '-';
            }
            case 'protected':
            {
                return '#';
            }
            default :
            {
                return '';
            }
        }
    }

    /**
     * @param string $accessLevel
     */
    public function setAccessLevel($accessLevel)
    {
        $this->accessLevel = $accessLevel;
    }
}