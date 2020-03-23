<?php

namespace Dreammo\Plantuml\Helper;

/**
 * Class AbstractPlantUMLParam
 * @package Dreammo\Plantuml\Helper
 *
 * Param Abstract Class
 *
 */
class AbstractPlantUMLParam
{
    protected $name;
    protected $dataType;
    protected $accessLevel;

    /**
     * AbstractPlantUMLParam constructor.
     * @param $name
     * @param $dataType
     * @param $accessLevel
     */
    public function __construct($name, $dataType, $accessLevel)
    {
        $this->name = $name;
        $this->dataType = $dataType;
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
     * @return mixed
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * @param mixed $dataType
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
    }

    /**
     * @return mixed
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
            default:
            {
                return '';
            }
        }
    }

    /**
     * @param mixed $accessLevel
     */
    public function setAccessLevel($accessLevel)
    {
        $this->accessLevel = $accessLevel;
    }
}