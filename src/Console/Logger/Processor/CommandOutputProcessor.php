<?php

declare(strict_types=1);

/**
 * Fast Forward Development Tools for PHP projects.
 *
 * This file is part of fast-forward/dev-tools project.
 *
 * @author   Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 *
 * @see      https://github.com/php-fast-forward/
 * @see      https://github.com/php-fast-forward/dev-tools
 * @see      https://github.com/php-fast-forward/dev-tools/issues
 * @see      https://php-fast-forward.github.io/dev-tools/
 * @see      https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Console\Logger\Processor;

use JsonException;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\json_decode;

/**
 * Converts buffered command output objects into serializable context entries.
 *
 * JSON payloads are decoded eagerly so parent command envelopes can expose
 * nested structured output without re-encoding it as an escaped string.
 */
final class CommandOutputProcessor implements ContextProcessorInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function process(array $context): array
    {
        foreach ($context as $key => $value) {
            if (! $value instanceof OutputInterface) {
                continue;
            }

            unset($context[$key]);

            $outputContent = $this->extractBufferedOutput($value);

            if (null !== $outputContent) {
                $context[$key] = $outputContent;
            }

            if ($value instanceof ConsoleOutputInterface) {
                $errorOutput = $this->extractBufferedOutput($value->getErrorOutput());

                if (null !== $errorOutput && ! \array_key_exists('error_output', $context)) {
                    $context['error_output'] = $errorOutput;
                }
            }
        }

        return $context;
    }

    /**
     * @param OutputInterface $output
     *
     * @return mixed
     */
    private function extractBufferedOutput(OutputInterface $output): mixed
    {
        if (! $output instanceof BufferedOutput) {
            return null;
        }

        $content = $output->fetch();

        return $this->decodeStructuredOutput($content);
    }

    /**
     * Decodes a buffered output string when it contains JSON content.
     *
     * @param string $content the buffered output contents
     *
     * @return mixed the decoded JSON payload or the original string
     */
    private function decodeStructuredOutput(string $content): mixed
    {
        $trimmedContent = trim($content);

        if ('' === $trimmedContent) {
            return $content;
        }

        try {
            return $this->normalizeStructuredPayload(json_decode($trimmedContent, true));
        } catch (JsonException) {
        }

        $decodedDocuments = $this->decodeJsonDocumentStream($trimmedContent);

        if (null !== $decodedDocuments) {
            return $decodedDocuments;
        }

        return $content;
    }

    /**
     * Decodes a stream that contains multiple JSON documents separated by whitespace.
     *
     * @param string $content the buffered output contents
     *
     * @return ?list<mixed> the decoded JSON documents when the stream is valid
     */
    private function decodeJsonDocumentStream(string $content): ?array
    {
        $decodedDocuments = [];
        $offset = 0;
        $length = \strlen($content);

        while ($offset < $length) {
            while ($offset < $length && ctype_space($content[$offset])) {
                ++$offset;
            }

            if ($offset >= $length) {
                break;
            }

            $document = $this->consumeJsonDocument($content, $offset);

            if (null === $document) {
                return null;
            }

            try {
                $decodedDocuments[] = $this->normalizeStructuredPayload(json_decode($document, true));
            } catch (JsonException) {
                return null;
            }
        }

        return \count($decodedDocuments) > 1 ? $decodedDocuments : null;
    }

    /**
     * Consumes a single top-level JSON document from a multi-document stream.
     *
     * @param string $content the buffered output contents
     * @param int $offset the current stream offset, advanced past the document on success
     *
     * @return ?string the extracted JSON document
     */
    private function consumeJsonDocument(string $content, int &$offset): ?string
    {
        $length = \strlen($content);
        $start = $offset;
        $openingToken = $content[$offset];

        if ('{' !== $openingToken && '[' !== $openingToken) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escaping = false;

        for (; $offset < $length; ++$offset) {
            $character = $content[$offset];

            if ($inString) {
                if ($escaping) {
                    $escaping = false;

                    continue;
                }

                if ('\\' === $character) {
                    $escaping = true;

                    continue;
                }

                if ('"' === $character) {
                    $inString = false;
                }

                continue;
            }

            if ('"' === $character) {
                $inString = true;

                continue;
            }

            if ('{' === $character || '[' === $character) {
                ++$depth;

                continue;
            }

            if ('}' === $character || ']' === $character) {
                --$depth;

                if (0 === $depth) {
                    ++$offset;

                    return substr($content, $start, $offset - $start);
                }
            }
        }

        return null;
    }

    /**
     * Normalizes decoded structured payloads produced by wrapped tooling.
     *
     * @param mixed $payload the decoded payload
     *
     * @return mixed the normalized payload
     */
    private function normalizeStructuredPayload(mixed $payload): mixed
    {
        if (! \is_array($payload)) {
            return $payload;
        }

        if (! isset($payload['totals']) || ! \is_array($payload['totals'])) {
            return $payload;
        }

        $changedFilesTotal = $payload['totals']['changed_files'] ?? null;

        if (! \is_int($changedFilesTotal)) {
            return $payload;
        }

        if (0 === $changedFilesTotal) {
            $payload['changed_files'] = [];

            return $payload;
        }

        if (! isset($payload['file_diffs']) || ! \is_array($payload['file_diffs'])) {
            return $payload;
        }

        $changedFiles = [];

        foreach ($payload['file_diffs'] as $fileDiff) {
            if (! \is_array($fileDiff) || ! isset($fileDiff['file']) || ! \is_string($fileDiff['file'])) {
                continue;
            }

            $changedFiles[$fileDiff['file']] = $fileDiff['file'];
        }

        $payload['changed_files'] = array_values($changedFiles);

        return $payload;
    }
}
