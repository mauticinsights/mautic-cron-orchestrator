<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\DependencyInjection;

final class MauticCoreExtension
{
    public const DEFAULT_EXCLUDES = [
        'Config',
        'DependencyInjection',
        'Entity',
        'Model',
        'Repository',
        'Resources',
        'Tests',
        'vendor',
        'Translations',
    ];
}
