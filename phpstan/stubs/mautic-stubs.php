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
}
