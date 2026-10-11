<?php

declare(strict_types=1);

/**
 * Minimal Mautic definitions for PHPStan (mautic/core-lib is not installed).
 * Runtime / PHPUnit stubs live under Tests/Stubs; this file covers classes
 * not needed at unit-test time (FormController, AbstractPluginBundle, …).
 */

namespace Mautic\IntegrationsBundle\Bundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

abstract class AbstractPluginBundle extends Bundle
{
}

namespace Mautic\PluginBundle\Bundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

abstract class PluginBundleBase extends Bundle
{
}

namespace Mautic\CoreBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class FormController extends AbstractController
{
    protected CorePermissions $security;

    protected function accessDenied(): Response
    {
        return new Response('', 403);
    }

    protected function notFound(string $msg = 'mautic.core.url.error.404'): Response
    {
        return new Response($msg, 404);
    }

    /**
     * @param array<string, mixed> $messageVars
     */
    protected function addFlashMessage(
        string $message,
        array $messageVars = [],
        string $level = 'notice',
        string $domain = 'flashes',
        bool $addNotification = false,
    ): void {
    }

    protected function getDoctrine(): ManagerRegistry
    {
        throw new \RuntimeException('Stub');
    }

    /**
     * @param array<string, mixed> $args
     */
    protected function delegateView(array $args): Response
    {
        return new Response();
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function createFormBuilder(mixed $data = null, array $options = []): FormBuilderInterface
    {
        throw new \RuntimeException('Stub');
    }

    /**
     * @param \Symfony\Component\Form\FormInterface<mixed> $form
     */
    protected function isFormCancelled(\Symfony\Component\Form\FormInterface $form): bool
    {
        return false;
    }

    /**
     * @param \Symfony\Component\Form\FormInterface<mixed> $form
     */
    protected function isFormApplied(\Symfony\Component\Form\FormInterface $form): bool
    {
        return false;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return new RedirectResponse('/', $status);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function generateUrl(string $route, array $parameters = [], int $referenceType = 1): string
    {
        return '/';
    }

    public function get(string $id): object
    {
        throw new \RuntimeException('Stub');
    }
}

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

/**
 * @extends AbstractType<mixed>
 */
class YesNoButtonGroupType extends AbstractType
{
}

/**
 * @extends AbstractType<mixed>
 */
class FormButtonsType extends AbstractType
{
}

namespace Mautic\CoreBundle\Security\Permissions;

class CorePermissions
{
    /**
     * @param string|string[] $permission
     *
     * @return bool|array<string, bool>
     */
    public function isGranted($permission, string $mode = 'MATCH_ALL')
    {
        return true;
    }

    public function isAdmin(): bool
    {
        return true;
    }
}

namespace Mautic\IntegrationsBundle\Exception;

use Exception;

class IntegrationNotFoundException extends Exception
{
}

namespace Mautic\IntegrationsBundle\Helper;

use Mautic\IntegrationsBundle\Integration\Interfaces\IntegrationInterface;

class IntegrationsHelper
{
    public function getIntegration(string $integration): IntegrationInterface
    {
        throw new \RuntimeException('Stub');
    }
}

namespace Mautic\IntegrationsBundle\Integration\Interfaces;

use Mautic\PluginBundle\Entity\Integration;

interface IntegrationInterface
{
    public function getIntegrationConfiguration(): Integration;
}

interface BasicInterface extends IntegrationInterface
{
}

interface ConfigFormInterface
{
    public function getConfigFormName(): ?string;

    public function getConfigFormContentTemplate(): ?string;

    public function getSyncConfigFormName(): ?string;
}

namespace Mautic\IntegrationsBundle\Integration;

use Mautic\PluginBundle\Entity\Integration;

class BasicIntegration
{
    public function setIntegrationSettings(Integration $integration): void
    {
    }

    public function getIntegrationConfiguration(): Integration
    {
        throw new \RuntimeException('Stub');
    }
}

trait ConfigurationTrait
{
}

trait DefaultConfigFormTrait
{
    public function getConfigFormName(): ?string
    {
        return null;
    }

    public function getConfigFormContentTemplate(): ?string
    {
        return null;
    }

    public function getSyncConfigFormName(): ?string
    {
        return null;
    }
}

namespace Mautic\PluginBundle\Entity;

class Integration
{
    public function getIsPublished(): bool
    {
        return false;
    }

    public function setIsPublished(bool $published): void
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getFeatureSettings(): array
    {
        return [];
    }
}

namespace Mautic\CoreBundle;

final class CoreEvents
{
    public const BUILD_MENU = 'mautic.menu_build';
}

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class MenuEvent extends Event
{
    /**
     * @param array<string, mixed> $menuItems
     */
    public function addMenuItems(array $menuItems): void
    {
    }

    public function getType(): string
    {
        return 'main';
    }
}

namespace Symfony\Component\Validator;

class Constraint
{
    /**
     * @param array<string, mixed>|null $options
     */
    public function __construct($options = null)
    {
    }
}

namespace Symfony\Component\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class NotBlank extends Constraint
{
}

class GreaterThan extends Constraint
{
}
