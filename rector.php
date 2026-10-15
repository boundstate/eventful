<?php

use Rector\CodingStyle\Rector\ArrowFunction\ArrowFunctionDelegatingCallToFirstClassCallableRector;
use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/demo/config',
        __DIR__.'/src',
    ])
    ->withPhpSets()
    ->withImportNames()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    )
    ->withSkip([
        SafeDeclareStrictTypesRector::class,
        // Pest binds dataset closures to the test case, which fails for first-class callables of functions
        ArrowFunctionDelegatingCallToFirstClassCallableRector::class => [
            __DIR__.'/tests',
        ],
        // return types on test closures (e.g. `function (): void`) are just clutter
        AddArrowFunctionReturnTypeRector::class => [
            __DIR__.'/tests',
        ],
        AddClosureVoidReturnTypeWhereNoReturnRector::class => [
            __DIR__.'/tests',
        ],
    ]);
