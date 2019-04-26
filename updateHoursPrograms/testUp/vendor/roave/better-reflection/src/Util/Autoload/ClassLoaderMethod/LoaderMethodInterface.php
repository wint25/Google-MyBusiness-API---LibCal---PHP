<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Util\Autoload\ClassLoaderMethod;

use Roave\BetterReflection\Reflection\ReflectionClass;

interface LoaderMethodInterface
{
    /**
     * @param ReflectionClass $classInfo
     * @return void
     */
    public function __invoke(ReflectionClass $classInfo) : void;
}
