<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2019 Daniyal Hamid (https://designcise.com)
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
    RedirectResponse
};

use function class_exists;
use function explode;
use function is_string;
use function ltrim;
use function method_exists;
use function strpos;

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
     * @param callable|string|array $handler
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
    public function use($methods, $middleware, string $path, $handler)
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

        $this->map($methods, $path, $handlerWithMiddleware);
    }

    /**
     * Add a group of routes to the collection.
     *
     * @param string $prefix
     * @param callable $group
     */
    public function group(string $prefix, callable $group)
    {
        new RouteGroup($prefix, $group, $this);
    }
    
    /**
     * Add a route that responds to GET HTTP method.
     *
     * @param string $path
     * @param callable|string|array $handler
     */
    public function get(string $path, $handler)
    {
        $this->map(['GET'], $path, $handler);
    }
    
    /**
     * Add a route that responds to POST HTTP method.
     *
     * @param string $path
     * @param callable|string|array $handler
     */
    public function post(string $path, $handler)
    {
        $this->map(['POST'], $path, $handler);
    }
    
    /**
     * Add a route that responds to PUT HTTP method.
     *
     * @param string $path
     * @param callable|string|array $handler
     */
    public function put(string $path, $handler)
    {
        $this->map(['PUT'], $path, $handler);
    }
    
    /**
     * Add a route that responds to PATCH HTTP method.
     *
     * @param string $path
     * @param callable|string|array $handler
     */
    public function patch(string $path, $handler)
    {
        $this->map(['PATCH'], $path, $handler);
    }
    
    /**
     * Add a route that responds to DELETE HTTP method.
     *
     * @param string $path
     * @param callable|string|array $handler
     */
    public function delete(string $path, $handler)
    {
        $this->map(['DELETE'], $path, $handler);
    }
    
    /**
     * Add a route that responds to HEAD HTTP method.
     *
     * @param string $path
     * @param callable|string|array $handler
     */
    public function head(string $path, $handler)
    {
        $this->map(['HEAD'], $path, $handler);
    }
    
    /**
     * Add a route that responds to OPTIONS HTTP method.
     *
     * @param string $path
     * @param callable|string|array $handler
     */
    public function options(string $path, $handler)
    {
        $this->map(['OPTIONS'], $path, $handler);
    }
    
    /**
     * Add route for any HTTP method.
     *
     * @param string $path
     * @param callable|string|array $handler
     */
    public function any(string $path, $handler)
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
    public function text($methods, string $route, string $text, int $statusCode = 200)
    {
        $this->map(
            (array) $methods,
            $route,
            static fn () => (new TextResponse($text, $statusCode))->withStatus($statusCode)
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
    public function html($methods, string $route, string $html, int $statusCode = 200)
    {
        $this->map(
            (array) $methods,
            $route,
            static fn () => (new HtmlResponse($html))->withStatus($statusCode)
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
    public function json($methods, string $route, array $data, int $statusCode = 200)
    {
        $this->map(
            (array) $methods,
            $route,
            static fn () => (new JsonResponse($data))->withStatus($statusCode)
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
    ) {
        $this->map(
            (array) $methods,
            $route,
            static fn () => (new JsonpResponse($data, $callback))->withStatus($statusCode)
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
    public function xml($methods, string $route, string $xml, int $statusCode = 200)
    {
        $this->map(
            (array) $methods,
            $route,
            static fn () => (new XmlResponse($xml))->withStatus($statusCode)
        );
    }

    /**
     * Add a route that sends a file response.
     *
     * @param string $route
     * @param string $fileUrl
     */
    public function file(string $route, string $fileUrl)
    {
        $this->map(
            ['GET'],
            $route,
            static fn () => new FileResponse($fileUrl)
        );
    }

    /**
     * Add a route that sends a file download response.
     *
     * @param string $route
     * @param string $downloadUrl
     * @param string $serveFilenameAs
     */
    public function download(string $route, string $downloadUrl, string $serveFilenameAs = '')
    {
        $this->map(
            ['GET'],
            $route,
            static fn () => (new FileResponse($downloadUrl))->withDownload($serveFilenameAs)
        );
    }
    
    /**
     * Add a route that sends an HTTP redirect.
     *
     * @param string $fromUrl
     * @param string $toUrl
     * @param int $statusCode
     */
    public function redirect(string $fromUrl, string $toUrl, int $statusCode = 302)
    {
        $this->map(
            ['GET'],
            $fromUrl,
            static fn () => new RedirectResponse($toUrl, $statusCode)
        );
    }

    /**
     * Auto-append controller action name from path.
     *
     * @param string $routeController
     * @param string $path
     *
     * @return string
     */
    protected function addControllerActionFromPath(string $routeController, string $path): string
    {
        $pathChunks = explode('/', ltrim($path, '/'));
        $methodName = "{$pathChunks[1]}Action";

        if (isset($pathChunks[1]) && method_exists($routeController, $methodName)) {
            $routeController .= "::{$methodName}";
        }

        return $routeController;
    }

    /**
     * @param mixed $routeHandler
     *
     * @return boolean
     */
    protected function isClassName($routeHandler): bool
    {
        return is_string($routeHandler)
            && strpos($routeHandler, '::') === false
            && class_exists($routeHandler);
    }
}