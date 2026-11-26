<?php

declare(strict_types=1);

/**
 * Fast Forward Development Tools for PHP projects.
 *
 * This file is part of fast-forward/dev-tools project.
 *
 * @author   Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 *
 * @see      https://github.com/php-fast-forward/
 * @see      https://github.com/php-fast-forward/dev-tools
 * @see      https://github.com/php-fast-forward/dev-tools/issues
 * @see      https://php-fast-forward.github.io/dev-tools/
 * @see      https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\PhpUnit\Result;

use JsonException;

use function Safe\json_decode;

/**
 * Interprets captured PHPUnit output without console or process dependencies.
 *
 * A failed process or reporter MUST remain a failure. Multiple reporter
 * payloads MUST NOT be interpreted as one successful execution.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */
final readonly class ProcessResultInterpreter
{
    /**
     * Limits recovery work to a fixed multiple of the captured output length.
     */
    private const int SCAN_WORK_PER_BYTE = 4;

    /**
     * Preserves reporter fields while rejecting failed or ambiguous results.
     *
     * @param string $rawOutput the captured process output
     * @param int $exitCode the exit status returned by process execution
     *
     * @return array<string, mixed> the interpreted reporter payload
     */
    public function __invoke(string $rawOutput, int $exitCode): array
    {
        $payload = [
            'result' => 0 === $exitCode ? 'success' : 'failure',
        ];
        $rawOutput = trim($rawOutput);

        if ('' === $rawOutput) {
            return $payload;
        }

        $supplementalOutput = null;

        try {
            $decoded = json_decode($rawOutput, true);
        } catch (JsonException) {
            $decoded = null;
            $reports = $this->findReporterPayloads($rawOutput);
            $supplementalOutput = $rawOutput;

            if (null === $reports || 1 < \count($reports)) {
                return [
                    'result' => 'failure',
                    'raw_output' => $rawOutput,
                ];
            }

            if ([] !== $reports) {
                [$start, $end, $report] = $reports[0];

                if ('' === trim(substr($rawOutput, $end + 1))) {
                    $decoded = $report;
                    $prefix = trim(substr($rawOutput, 0, $start));
                    $supplementalOutput = '' === $prefix ? null : $prefix;
                } elseif ('failure' === $report['result']) {
                    $payload['result'] = 'failure';
                }
            }
        }

        if (\is_array($decoded)) {
            $payload = [
                ...$decoded,
                'result' => 'failure' === ($decoded['result'] ?? null) ? 'failure' : $payload['result'],
            ];
        }

        if (null !== $supplementalOutput) {
            $payload['raw_output'] = $supplementalOutput;
        }

        return $payload;
    }

    /**
     * Finds complete reporter objects, recovering after malformed diagnostic prefixes.
     *
     * Valid outer objects consume their nested objects so nested result fields
     * are never counted as additional reports.
     *
     * @param string $rawOutput the captured output containing diagnostics or multiple documents
     *
     * @return list<array{int, int, array<string, mixed>}>|null reporter objects, or null when recovery exhausts its work budget
     */
    private function findReporterPayloads(string $rawOutput): ?array
    {
        $reports = [];
        $length = \strlen($rawOutput);
        $remaining = $length * self::SCAN_WORK_PER_BYTE;

        for ($start = 0; $start < $length; ++$start) {
            if ('{' !== $rawOutput[$start]) {
                continue;
            }

            $end = $this->findObjectEnd($rawOutput, $start, $remaining);

            if (null === $end) {
                if (0 === $remaining) {
                    return null;
                }

                continue;
            }

            try {
                $decoded = json_decode(substr($rawOutput, $start, $end - $start + 1), true);
            } catch (JsonException) {
                continue;
            }

            if (\is_array($decoded) && \in_array($decoded['result'] ?? null, ['success', 'failure'], true)) {
                $reports[] = [$start, $end, $decoded];

                if (2 === \count($reports)) {
                    return $reports;
                }
            }

            $start = $end;
        }

        return $reports;
    }

    /**
     * Locates one balanced object independently of preceding malformed text.
     *
     * @param string $rawOutput the captured process output
     * @param int $start the opening brace offset
     * @param int $remaining the remaining shared byte-inspection budget
     *
     * @return int|null the closing brace offset, or null for an incomplete object
     */
    private function findObjectEnd(string $rawOutput, int $start, int &$remaining): ?int
    {
        $depth = 0;
        $quoted = false;
        $escaped = false;
        $length = \strlen($rawOutput);

        for ($end = $start; $end < $length; ++$end) {
            if (0 === $remaining) {
                return null;
            }

            --$remaining;
            $character = $rawOutput[$end];

            if ($quoted) {
                if ("\n" === $character || "\r" === $character) {
                    return null;
                }

                if ($escaped) {
                    $escaped = false;
                } elseif ('\\' === $character) {
                    $escaped = true;
                } elseif ('"' === $character) {
                    $quoted = false;
                }

                continue;
            }

            if ('"' === $character) {
                $quoted = true;
            } elseif ('{' === $character) {
                ++$depth;
            } elseif ('}' === $character && 0 === --$depth) {
                return $end;
            }
        }

        return null;
    }
}
