<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php84: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        naming: true,
        instanceOf: true,
        earlyReturn: true,
    )
    ->withSymfonyContainerXml(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml')
    ->withSets([
        SymfonySetList::SYMFONY_73,
        DoctrineSetList::DOCTRINE_CODE_QUALITY,
    ])
    // Doctrine entities carry the idiomatic `$id` property regardless of its
    // PHP type — Rector's name-matching would rename it to `$uuid` and break
    // the column mapping (column name stays `id`).
    ->withSkip([
        RenamePropertyToMatchTypeRector::class => [
            __DIR__ . '/src/Entity',
        ],
        RenameParamToMatchTypeRector::class => [
            __DIR__ . '/src/Entity',
        ],
    ]);
