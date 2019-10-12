<?php

interface Fly
{
    /**
     * @param Animal
     * @return mixed
     *
     */
    public function startFly(Animal $animal);
}


abstract class Animal
{
    /**
     * @var string
     *
     */
    public $name;

    /**
     * @var int
     *
     */
    protected $age;
}

class GroupDogs
{
    /**
     * @var Dog
     * @Agg Dog[]
     *
     */
    public $dogs;
}

class Leg
{

}

class Hat
{
    /**
     * @var string
     *
     */
    private $color;
}

class Dog extends Animal implements Fly
{
    /**
     * @var Leg
     *
     * @Comp Leg
     *
     */
    private $leg;

    /**
     * @var Hat
     *
     * @Assoc Hat
     *
     */
    private $hat;


    /**
     * @param Animal $animal
     * @return string
     *
     */
    public function startFly(Animal $animal)
    {
        return "start fly";
    }
}
