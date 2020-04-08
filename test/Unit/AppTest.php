<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

namespace BitFrame\Test\Unit;

use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use BitFrame\App;
use InvalidArgumentException;

/**
 * @covers \BitFrame\App
 */
class AppTest extends TestCase
{
    private App $app;

    public function setUp(): void
    {
        $this->app = new App();
    }

    public function testCanGetContainerViaHandlerInCallback(): void
    {
        $app = $this->app;
        $container = $app->getContainer();
        $container['foo'] = 'bar';
        $container['baz'] = 'qux';
        $container['test'] = ['deep'];

        $app->run(function ($req, $handler) {
            /** @var ContainerInterface $container */
            $container = $handler->getContainer();

            $this->assertInstanceOf(ContainerInterface::class, $container);
            $this->assertSame('bar', $container['foo']);
            $this->assertSame('qux', $container['baz']);
            $this->assertSame(['deep'], $container['test']);

            return $handler($req);
        });
    }

    public function emptyValuesProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'empty array' => [[]],
            'boolean' => [false],
            'number 0' => [0],
            'float 0.0' => [0.0],
            'string 0' => ['0'],
        ];
    }

    /**
     * @dataProvider emptyValuesProvider
     *
     * @param mixed $middleware
     */
    public function testRunWithoutMiddleware($middleware): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->app->run($middleware);
    }
}