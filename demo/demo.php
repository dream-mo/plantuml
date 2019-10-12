<?php

include_once "../vendor/autoload.php";

$path = __DIR__.'/demo/test';

$writer = new \Dreammo\Plantuml\Helper\PlantUMLWriter($path);

$writer->write("./plant.puml");
