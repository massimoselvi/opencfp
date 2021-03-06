<?php

declare(strict_types=1);

/**
 * Copyright (c) 2013-2017 OpenCFP
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/opencfp/opencfp
 */

namespace OpenCFP\Test\Integration;

use Mockery;
use OpenCFP\Domain\CallForPapers;
use OpenCFP\Domain\Services\Authentication;
use OpenCFP\Domain\Services\RequestValidator;
use OpenCFP\Infrastructure\Auth\CsrfValidator;
use OpenCFP\Infrastructure\Auth\UserInterface;
use OpenCFP\Test\BaseTestCase;
use OpenCFP\Test\Helper\MockableAuthenticator;
use OpenCFP\Test\Helper\ResponseHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class WebTestCase extends BaseTestCase
{
    use ResponseHelper;

    /**
     * Additional headers for a request.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Additional server variables to be sent with a request.
     *
     * @var array
     */
    protected $server = [];

    /**
     * Swap implementations of a service in the container.
     *
     * @param string $service
     * @param object $instance
     *
     * @return object
     */
    protected function swap($service, $instance)
    {
        $this->app[$service] = $instance;

        return $instance;
    }

    /**
     * Define additional headers to be sent with the request.
     *
     * @param array $headers
     *
     * @return $this
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = \array_merge($this->headers, $headers);

        return $this;
    }

    /**
     * Add a header to be sent with the request.
     *
     * @param string $name
     * @param string $value
     *
     * @return $this
     */
    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function withNoHeaders()
    {
        $this->headers = [];

        return $this;
    }

    public function withServerVariables(array $server): self
    {
        $this->server = $server;

        return $this;
    }

    public function call(string $method, string $uri, array $parameters = [], array $cookies = [], array $files = [], array $server = [], string $content = null): Response
    {
        $request = Request::create(
            $uri,
            $method,
            $parameters,
            $cookies,
            $files,
            \array_replace($this->server, $server),
            $content
        );

        $response = $this->app->handle($request);
        $this->app->terminate($request, $response);

        return $response;
    }

    public function get(string $uri, array $parameters = [], array $cookies = [], array $files = [], array $server = [], string $content = null): Response
    {
        return $this->call('GET', $uri, $parameters, $cookies, $files, $server, $content);
    }

    public function post(string $uri, array $parameters = [], array $cookies = [], array $files = [], array $server = [], string $content = null): Response
    {
        return $this->call('POST', $uri, $parameters, $cookies, $files, $server, $content);
    }

    public function patch(string $uri, array $parameters = [], array $cookies = [], array $files = [], array $server = [], string $content = null): Response
    {
        return $this->call('PATCH', $uri, $parameters, $cookies, $files, $server, $content);
    }

    public function delete(string $uri, array $parameters = [], array $cookies = [], array $files = [], array $server = [], string $content = null): Response
    {
        return $this->call('DELETE', $uri, $parameters, $cookies, $files, $server, $content);
    }

    public function callForPapersIsOpen(): self
    {
        $cfp = Mockery::mock(CallForPapers::class);
        $cfp->shouldReceive('isOpen')->andReturn(true);
        $this->swap(CallForPapers::class, $cfp);
        $this->container->get('twig')->addGlobal('cfp_open', true);

        return $this;
    }

    public function callForPapersIsClosed(): self
    {
        $cfp = Mockery::mock(CallForPapers::class);
        $cfp->shouldReceive('isOpen')->andReturn(false);
        $this->swap(CallForPapers::class, $cfp);
        $this->container->get('twig')->addGlobal('cfp_open', false);

        return $this;
    }

    public function isOnlineConference(): self
    {
        $config                                     = $this->container->get('config');
        $config['application']['online_conference'] = true;
        $this->swap('config', $config);
        $this->container->get('twig')->addGlobal('site', $config['application']);

        return $this;
    }

    public function asLoggedInSpeaker(int $id = 1): self
    {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('id')->andReturn($id);
        $user->shouldReceive('getId')->andReturn($id);
        $user->shouldReceive('hasAccess')->with('admin')->andReturn(false);
        $user->shouldReceive('hasPermission')->with('admin')->andReturn(false);
        $user->shouldReceive('hasAccess')->with('reviewer')->andReturn(false);
        $user->shouldReceive('hasPermission')->with('reviewer')->andReturn(false);
        $user->shouldReceive('getLogin')->andReturn('my@email.com');

        /** @var MockableAuthenticator $authentication */
        $authentication = $this->container->get(Authentication::class);
        $authentication->overrideUser($user);

        return $this;
    }

    public function asAdmin(int $id = 1): self
    {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('id')->andReturn($id);
        $user->shouldReceive('getId')->andReturn($id);
        $user->shouldReceive('hasAccess')->with('admin')->andReturn(true);
        $user->shouldReceive('hasPermission')->with('admin')->andReturn(true);
        $user->shouldReceive('hasAccess')->with('reviewer')->andReturn(false);
        $user->shouldReceive('hasPermission')->with('reviewer')->andReturn(false);

        /** @var MockableAuthenticator $authentication */
        $authentication = $this->container->get(Authentication::class);
        $authentication->overrideUser($user);

        return $this;
    }

    public function asReviewer(int $id = 1): self
    {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('id')->andReturn($id);
        $user->shouldReceive('getId')->andReturn($id);
        $user->shouldReceive('hasAccess')->with('admin')->andReturn(false);
        $user->shouldReceive('hasPermission')->with('admin')->andReturn(false);
        $user->shouldReceive('hasAccess')->with('reviewer')->andReturn(true);
        $user->shouldReceive('hasPermission')->with('reviewer')->andReturn(true);

        /** @var MockableAuthenticator $authentication */
        $authentication = $this->container->get(Authentication::class);
        $authentication->overrideUser($user);

        return $this;
    }

    public function passCsrfValidator(): self
    {
        $csrf = Mockery::mock(RequestValidator::class);
        $csrf->shouldReceive('isValid')->andReturn(true);
        $this->swap(CsrfValidator::class, $csrf);

        return $this;
    }
}
