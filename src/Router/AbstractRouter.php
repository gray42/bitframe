<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

declare(strict_types=1);

namespace BitFrame\Router;

use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use BitFrame\Http\MiddlewareDecoratorTrait;
use BitFrame\Http\Message\{
    TextResponse,
    HtmlResponse,
    JsonResponse,
    JsonpResponse,
    XmlResponse,
    FileResponse,
    DownloadResponse,
    RedirectResponse
};

/**
 * Common router implementation.
 */
abstract class AbstractRouter
{
    use MiddlewareDecoratorTrait;

    /**
     * Add a route to the map.
     *
     * @param string|string[] $methods
     * @param string $path
     * @param callable|string|array|MiddlewareInterface $handler
     */
    abstract public function map($methods, string $path, $handler);

    /**
     * Add a route to the map using $middleware.
     *
     * @param string|string[] $methods
     * @param array|string|callable|\Psr\Http\Server\MiddlewareInterface $middleware
     * @param string $path
     * @param callable|string|array $handler
     */
    public function use($methods, $middleware, string $path, $handler): void
    {
        $middlewares = $this->getUnpackedMiddleware($middleware);
        $middlewares[] = $this->getDecoratedMiddleware($handler);

        $handlerWithMiddleware = new class ($middlewares) implements MiddlewareInterface {
            private $middlewares;

            public function __construct(array $middlewares)
            {
                $this->middlewares = $middlewares;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                foreach ($this->middlewares as $middleware) {
                    $middleware->process($request, $handler);
                }

                return $handler->handle($request);
            }
        };

        $this->map((array) $methods, $path, $handlerWithMiddleware);
    }

    public function group(string $prefix, callable $group): void
    {
        new RouteGroup($prefix, $group, $this);
    }

    /**
     * Add a route that responds to GET HTTP method.
     *
     * @param string $path
     * @param callable|string|array $handler
     */
    public function get(string $path, $handler): void
    {
        $this->map(['GET'], $path, $handler);
    }

    /**
     * Add a route that responds to POST HTTP method.
     *
     * @param string $path
     * @param callable|string|array $handler
     */
    public function post(string $path, $handler): void
    {
        $this->map(['POST'], $path, $handler);
    }

    /**
     * Add a route that responds to PUT HTTP method.
     *
     * @param string $path
     * @param callable|string|array $handler
     */
    public function put(string $path, $handler): void
    {
        $this->map(['PUT'], $path, $handler);
    }

    /**
     * Add a route that responds to PATCH HTTP method.
     *
     * @param string $path
     * @param callable|string|array $handler
     */
    public function patch(string $path, $handler): void
    {
        $this->map(['PATCH'], $path, $handler);
    }

    /**
     * Add a route that responds to DELETE HTTP method.
     *
     * @param string $path
     * @param callable|string|array $handler
     */
    public function delete(string $path, $handler): void
    {
        $this->map(['DELETE'], $path, $handler);
    }

    /**
     * Add a route that responds to HEAD HTTP method.
     *
     * @param string $path
     * @param callable|string|array $handler
     */
    public function head(string $path, $handler): void
    {
        $this->map(['HEAD'], $path, $handler);
    }

    /**
     * Add a route that responds to OPTIONS HTTP method.
     *
     * @param string $path
     * @param callable|string|array $handler
     */
    public function options(string $path, $handler): void
    {
        $this->map(['OPTIONS'], $path, $handler);
    }

    /**
     * Add route for any HTTP method.
     *
     * @param string $path
     * @param callable|string|array $handler
     */
    public function any(string $path, $handler): void
    {
        $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $path, $handler);
    }

    /**
     * Add a route that sends a text response.
     *
     * @param string[]|string $methods
     * @param string $route
     * @param string $text
     * @param int $statusCode
     */
    public function text($methods, string $route, string $text, int $statusCode = 200): void
    {
        $this->map(
            (array) $methods,
            $route,
            static fn () => (new TextResponse($text))->withStatus($statusCode)
        );
    }

    /**
     * Add a route that sends HTML response.
     *
     * @param string[]|string $methods
     * @param string $route
     * @param string $html
     * @param int $statusCode
     */
    public function html($methods, string $route, string $html, int $statusCode = 200): void
    {
        $this->map(
            (array) $methods,
            $route,
            static fn (): ResponseInterface => (new HtmlResponse($html))->withStatus($statusCode)
        );
    }

    /**
     * Add a route that sends a JSON response.
     *
     * @param string[]|string $methods
     * @param string $route
     * @param array $data
     * @param int $statusCode
     */
    public function json($methods, string $route, array $data, int $statusCode = 200): void
    {
        $this->map(
            (array) $methods,
            $route,
            static fn (): ResponseInterface => (new JsonResponse($data))->withStatus($statusCode)
        );
    }

    /**
     * Add a route that sends a JSONP response.
     *
     * @param string[]|string $methods
     * @param string $route
     * @param array $data
     * @param string $callback
     * @param int $statusCode
     */
    public function jsonp(
        $methods,
        string $route,
        array $data,
        string $callback,
        int $statusCode = 200
    ): void {
        $this->map(
            (array) $methods,
            $route,
            static fn (): ResponseInterface => (new JsonpResponse($data, $callback))->withStatus($statusCode)
        );
    }

    /**
     * Add a route that sends XML response.
     *
     * @param string[]|string $methods
     * @param string $route
     * @param string $xml
     * @param int $statusCode
     */
    public function xml($methods, string $route, string $xml, int $statusCode = 200): void
    {
        $this->map(
            (array) $methods,
            $route,
            static fn (): ResponseInterface => (new XmlResponse($xml))->withStatus($statusCode)
        );
    }

    /**
     * Add a route that sends a file response.
     *
     * @param string $route
     * @param string $filePath
     */
    public function file(string $route, string $filePath): void
    {
        $this->map(
            ['GET'],
            $route,
            static fn (): ResponseInterface => new FileResponse($filePath)
        );
    }

    /**
     * Add a route that sends a file download response.
     *
     * @param string $route
     * @param string $downloadUrl
     * @param string $serveFilenameAs
     */
    public function download(
        string $route,
        string $downloadUrl,
        string $serveFilenameAs = ''
    ): void {
        $this->map(
            ['GET'],
            $route,
            static fn (): ResponseInterface => new DownloadResponse($downloadUrl, $serveFilenameAs)
        );
    }

    /**
     * Add a route that sends an HTTP redirect.
     *
     * @param string $fromUrl
     * @param string $toUrl
     * @param int $statusCode
     */
    public function redirect(string $fromUrl, string $toUrl, int $statusCode = 302): void
    {
        $this->map(
            ['GET'],
            $fromUrl,
            static fn (): ResponseInterface => new RedirectResponse($toUrl, $statusCode)
        );
    }
}
