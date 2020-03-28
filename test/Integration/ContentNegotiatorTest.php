<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2019 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

namespace BitFrame\Test\Integration;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use BitFrame\Http\ContentNegotiator;
use BitFrame\Factory\HttpFactory;
use BitFrame\Parser\MediaParserInterface;
use BitFrame\Parser\{DefaultMediaParser, JsonMediaParser, XmlMediaParser};

/**
 * @covers \BitFrame\Http\ContentNegotiator
 */
class ContentNegotiatorTest extends TestCase
{
    /**
     * @dataProvider preferredMediaParserProvider
     */
    public function testGetPreferredMediaParserFromRequest(
        string $mimeType, 
        string $parserClassName
    ) {
        $request = HttpFactory::createServerRequest('GET', '/')
            ->withHeader('accept', $mimeType);
        
        $this->assertInstanceOf(
            $parserClassName, 
            ContentNegotiator::getPreferredMediaParserFromRequest($request)
        );
    }

    public function preferredMediaParserProvider()
    {
        return [
            'text_html' => ['text/html', DefaultMediaParser::class],
            'app_xhtml_xml' => ['application/xhtml+xml', DefaultMediaParser::class],

            'app_json' => ['application/json', JsonMediaParser::class],
            'text_json' => ['text/json', JsonMediaParser::class],
            'app_x_json' => ['application/x-json', JsonMediaParser::class],

            'text_xml' => ['text/xml', XmlMediaParser::class],
            'app_xml' => ['application/xml', XmlMediaParser::class],
            'app_x_xml' => ['application/x-xml', XmlMediaParser::class],

            'text_plain' => ['text/plain', DefaultMediaParser::class],
        ];
    }

    /**
     * @dataProvider preferredContentTypeProvider
     */
    public function testGetPreferredContentTypeFromRequest(
        string $mimeType, 
        string $contentType
    ) {
        $request = HttpFactory::createServerRequest('GET', '/')
            ->withHeader('accept', $mimeType);
        
        $this->assertSame(
            $contentType, 
            ContentNegotiator::getPreferredContentTypeFromRequest($request)
        );
    }

    public function preferredContentTypeProvider()
    {
        $html = ContentNegotiator::CONTENT_TYPE_HTML;
        $json = ContentNegotiator::CONTENT_TYPE_JSON;
        $xml = ContentNegotiator::CONTENT_TYPE_XML;
        $text = ContentNegotiator::CONTENT_TYPE_TEXT;

        return [
            'text_html' => ['text/html', $html],
            'app_xhtml_xml' => ['application/xhtml+xml', $html],

            'app_json' => ['application/json', $json],
            'text_json' => ['text/json', $json],
            'app_x_json' => ['application/x-json', $json],

            'text_xml' => ['text/xml', $xml],
            'app_xml' => ['application/xml', $xml],
            'app_x_xml' => ['application/x-xml', $xml],

            'text_plain' => ['text/plain', $text],
        ];
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddContentType()
    {
        $mime = 'foo/bar';

        $request = HttpFactory::createServerRequest('GET', '/')
            ->withHeader('accept', $mime);
        
        ContentNegotiator::addContentType('json', $mime);

        $this->assertInstanceOf(
            JsonMediaParser::class, 
            ContentNegotiator::getPreferredMediaParserFromRequest($request)
        );
    }
}