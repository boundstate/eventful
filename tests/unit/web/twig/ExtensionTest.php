<?php

use boundstate\eventful\web\twig\Extension;

it('only has defaults that Twig can compile', function () {
    $extension = new Extension(null, null);

    foreach ([...$extension->getFilters(), ...$extension->getFunctions()] as $callable) {
        foreach ((new ReflectionFunction(Closure::fromCallable($callable->getCallable())))->getParameters() as $parameter) {
            if ($parameter->isDefaultValueAvailable()) {
                expect($parameter->getDefaultValue())
                    ->not->toBeObject("{$callable->getName()}: \${$parameter->getName()}");
            }
        }
    }
});
