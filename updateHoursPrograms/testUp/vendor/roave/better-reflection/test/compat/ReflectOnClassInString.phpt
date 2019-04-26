--TEST--
Reflecting a class from a string
--FILE--
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$source = <<<EOF
<?php

class MyClassInString {}
EOF;

$reflector = new \Roave\BetterReflection\Reflector\ClassReflector(
    new Roave\BetterReflection\SourceLocator\Type\StringSourceLocator(
        $source,
        (new Roave\BetterReflection\BetterReflection())->astLocator()
    )
);

$classInfo = $reflector->reflect(MyClassInString::class);
var_dump($classInfo->getName());

?>
--EXPECT--
string(15) "MyClassInString"
