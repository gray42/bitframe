<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

declare(strict_types=1);

namespace BitFrame;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use Psr\Http\Server\RequestHandlerInterface;
use BitFrame\Factory\HttpFactory;
use BitFrame\Http\MiddlewareDecoratorTrait;
use InvalidArgumentException;

use function array_shift;

/**
 * The central point of a BitFrame application which:
 *   1. Provides shared data via a container;
 *   2. Stores & Runs middlewares.
 */
class App implements RequestHandlerInterface
{
    use MiddlewareDecoratorTrait;

    private ContainerInterface $container;

    private array $middlewares = [];

    private ServerRequestInterface $request;

    private ResponseInterface $response;

    public function __construct(
        ?ContainerInterface $container = null,
        ?ServerRequestInterface $request = null,
        ?ResponseInterface $response = null
    ) {
        $this->container = $container ?? new Container();
        $this->request = $request ?? HttpFactory::createServerRequestFromGlobals();
        $this->response = $response ?? HttpFactory::createResponse();
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handle($request);
    }

    /**
     * Push `$middleware` to the end of middlewares array.
     *
     * @param array|string|callable|\Psr\Http\Server\MiddlewareInterface $middleware
     *
     * @return $this
     */
    public function use($middleware): self
    {
        $this->middlewares = [
            ...$this->middlewares,
            ...$this->getUnpackedMiddleware($middleware)
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = array_shift($this->middlewares);

        if ($middleware) {
            $this->response = $middleware->process($request, $this);
        }

        // @see https://tools.ietf.org/html/rfc7231#section-4.3.2
        if ($request->getMethod() === 'HEAD') {
            $this->response = $this->response
                ->withBody(HttpFactory::createStream(''));
        }

        return $this->response;
    }

    /**
     * Run middleware with shared request/response from any previously run
     * middlewares.
     *
     * @param null|array|string|callable|\Psr\Http\Server\MiddlewareInterface $middlewares
     *
     * @return ResponseInterface
     *
     * @throws InvalidArgumentException
     */
    public function run($middlewares = null): ResponseInterface
    {
        $app = $this;
        $request = $this->request;

        if (! empty($middlewares)) {
            $app = new self($this->container, $request, $this->response);
            $app->use($middlewares);
        }

        if (empty($app->getMiddlewares())) {
            throw new InvalidArgumentException('Can\'t run, no middleware found');
        }

        return $app->handle($request);
    }

    /**
     * @param mixed $data
     */
    public function write($data): void
    {
        $this->response->getBody()->write($data);
    }

    public function isXhrRequest(): bool
    {
        return ($this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest');
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
