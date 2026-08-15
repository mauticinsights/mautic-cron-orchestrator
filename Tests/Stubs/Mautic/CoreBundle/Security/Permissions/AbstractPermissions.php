<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Security\Permissions;

use Symfony\Component\Form\FormBuilderInterface;

abstract class AbstractPermissions
{
    protected array $permissions = [];

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(protected array $params)
    {
        $this->definePermissions();
    }

    public function definePermissions(): void
    {
    }

    abstract public function getName(): string;

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
    }

    public function isEnabled(): bool
    {
        return true;
    }

    protected function addCustomPermission(string $name, array $levels): void
    {
        $this->permissions[$name] = $levels;
    }

    /**
     * @param array<string, string> $choices
     */
    protected function addCustomFormFields(string $bundle, string $level, FormBuilderInterface &$builder, string $label, array $choices, array $data): void
    {
    }

    protected function addStandardPermissions(string $name, bool $includePublish = true): void
    {
    }

    protected function addExtendedPermissions(string $name, bool $includePublish = true): void
    {
    }

    protected function addStandardFormFields(string $bundle, string $level, FormBuilderInterface &$builder, array $data, bool $includePublish = true): void
    {
    }

    protected function addExtendedFormFields(string $bundle, string $level, FormBuilderInterface &$builder, array $data, bool $includePublish = true, bool $includeHasAccess = true, array $masked = []): void
    {
    }

    protected function getSynonym(string $name, string $level): array
    {
        return [$name, $level];
    }
}
