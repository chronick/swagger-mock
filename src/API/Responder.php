<?php
/*
 * This file is part of Swagger Mock.
 *
 * (c) Igor Lazarev <strider2038@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Mock\Exception\NotSupportedException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * @author Igor Lazarev <strider2038@yandex.ru>
 */
class Responder
{
    private const RAW_MEDIA_TYPES = [
        '',
        'text/html',
    ];

    /** @var EncoderInterface */
    private $encoder;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(EncoderInterface $encoder, LoggerInterface $logger)
    {
        $this->encoder = $encoder;
        $this->logger = $logger;
    }

    public function createResponse(int $statusCode, string $mediaType, $data): Response
    {
        $encodedData = $this->encodeDataByMediaType($data, $mediaType);
        $headers = $this->createHeaders($mediaType);

        $this->logger->info(
            sprintf(
                'Response with status code "%d" and content type "%s" was generated.',
                $statusCode,
                $headers['Content-Type'] ?? ''
            )
        );

        return new Response($encodedData, $statusCode, $headers);
    }

    private function encodeDataByMediaType($data, string $mediaType): string
    {
        if ($this->isRawMediaType($data, $mediaType)) {
            $encodedData = $data;
        } else {
            $format = $this->guessSerializationFormat($mediaType);
            $encodedData = $this->encoder->encode($data, $format);
        }

        return $encodedData;
    }

    private function guessSerializationFormat(string $mediaType): string
    {
        if (preg_match('/^application\/.*json$/', $mediaType)) {
            $format = 'json';
        } elseif (preg_match('/^application\/.*xml$/', $mediaType)) {
            $format = 'xml';
        } else {
            throw new NotSupportedException(sprintf('Not supported media type "%s".', $mediaType));
        }

        return $format;
    }

    private function createHeaders(string $mediaType): array
    {
        $headers = [];

        if ('' !== $mediaType) {
            $headers['Content-Type'] = sprintf('%s; charset=utf-8', $mediaType);
        }

        return $headers;
    }

    private function isRawMediaType($data, string $mediaType): bool
    {
        return is_string($data) && in_array($mediaType, self::RAW_MEDIA_TYPES, true);
    }
}
