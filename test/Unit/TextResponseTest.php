<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2019 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

declare(strict_types=1);

namespace BitFrame\Test\Unit;

use PHPUnit\Framework\TestCase;
use BitFrame\Http\Message\TextResponse;
use TypeError;

/**
 * @covers \BitFrame\Http\Message\TextResponse
 */
class TextResponseTest extends TestCase
{
    public function testConstructorAcceptsBodyAsString()
    {
        $body = 'Lorem ipsum';
        $response = new TextResponse($body);
        $this->assertSame($body, (string) $response->getBody());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCanAddStatusAndHeader()
    {
        $body = 'Not found';
        $status = 404;
        
        $response = (new TextResponse($body))
            ->withStatus($status)
            ->withHeader('x-custom', ['foo-bar']);
        
        $this->assertSame(['foo-bar'], $response->getHeader('x-custom'));
        $this->assertSame('text/plain; charset=utf-8', $response->getHeaderLine('content-type'));
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame($body, (string) $response->getBody());
    }

    public function testStaticCreateWithCustomContentType()
    {
        $response = TextResponse::create('test')
            ->withHeader('content-type', 'text/richtext');
        
        $this->assertSame('text/richtext', $response->getHeaderLine('Content-Type'));
    }

    public function invalidContentProvider()
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'zero' => [0],
            'int' => [1],
            'zero-float' => [0.0],
            'float' => [1.1],
            'array' => [['php://temp']],
            'object' => [(object) ['php://temp']],
        ];
    }

    /**
     * @dataProvider invalidContentProvider
     */
    public function testRaisesExceptionforNonStringContent($body)
    {
        $this->expectException(TypeError::class);
        new TextResponse($body);
    }
}