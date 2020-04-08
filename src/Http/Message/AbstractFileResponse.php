<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

declare(strict_types=1);

namespace BitFrame\Http\Message;

use Psr\Http\Message\{ResponseInterface, StreamInterface};
use BitFrame\Factory\HttpFactory;
use InvalidArgumentException;

use function file_exists;
use function is_resource;
use function is_string;
use function mime_content_type;

/**
 * Common implementation for embedded/downloadable file Http response.
 */
class AbstractFileResponse extends ResponseDecorator
{
    /**
     * @param string|resource|StreamInterface $file
     *
     * @return ResponseInterface
     *
     * @throws InvalidArgumentException
     */
    protected function createEmbeddedFileResponse($file): ResponseInterface
    {
        $isFilePath = is_string($file);

        if ($isFilePath && ! file_exists($file)) {
            throw new InvalidArgumentException("File \"{$file}\" does not exist.");
        }

        $mimeType = ($isFilePath) ? mime_content_type($file) : 'application/octet-stream';
        $stream = $this->getFileAsStream($file, HttpFactory::getFactory());

        return HttpFactory::createResponse()
            ->withHeader('Content-Type', $mimeType)
            ->withBody($stream);
    }

    /**
     * @param string|resource|StreamInterface $file
     * @param \BitFrame\Factory\HttpFactoryInterface|object|null $factory
     *
     * @return StreamInterface
     */
    protected function getFileAsStream($file, $factory): StreamInterface
    {
        if (is_string($file)) {
            $file = $factory->createStreamFromFile($file, 'r');
        } elseif (is_resource($file)) {
            $file = $factory->createStreamFromResource($file);
        }

        return $file;
    }
}
