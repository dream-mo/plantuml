#  PHP source project to planuml language

## Installation

Get [composer](http://getcomposer.org/) and learn to use it.

Library is on [packagist](https://packagist.org/packages/dreammo/plantuml).

```bash
  composer require dreammo/plantuml
```

## Introduction

This project can easily convert the PHP source project to the plantuml language. 
Afterwards, the development can be converted to the class diagram png format or svg format using, 
for example, the IDE phpstorm installation plugin plantuml.

planuml document: [plantuml](http://plantuml.com/zh/class-diagram)

online plantuml editor website: [liveuml](https://liveuml.com/)

##Quick Start

 ```php
 ./vendor/bin/puml ./demo/test  diagram.puml
 ```

 then

   you can use like IDE phpstorm and install plugin "plantuml", and you can 
 Preview class diagram. You can try directly by using demo/test do demo test
 

## Annotation and class diagram relationship description

For details, please refer to demo/test/classes.php

```text

@var    dataType
@param  dataType
@return dataType

@Agg            // Aggregation
@Comp           // Composition
@Assoc          // Normal Association

```