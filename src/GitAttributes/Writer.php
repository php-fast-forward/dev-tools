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

namespace FastForward\DevTools\GitAttributes;

use Symfony\Component\Filesystem\Filesystem;

use function Safe\preg_split;

/**
 * Persists normalized .gitattributes content.
 *
 * This writer SHALL align attribute declarations using the longest path spec,
 * write the provided textual content to the target path, and MUST append a
 * final trailing line feed for deterministic formatting.
 */
final readonly class Writer implements WriterInterface
{
    /**
     * @param Filesystem $filesystem the filesystem service responsible for writing the file
     */
    public function __construct(
        private Filesystem $filesystem
    ) {}

    /**
     * Writes the .gitattributes content to the specified filesystem path.
     *
     * @param string $gitattributesPath The filesystem path to the .gitattributes file.
     * @param string $content The merged .gitattributes content to persist.
     *
     * @return void
     */
    public function write(string $gitattributesPath, string $content): void
    {
        $this->filesystem->dumpFile($gitattributesPath, $this->format($content));
    }

    /**
     * Formats .gitattributes content with aligned attribute columns.
     *
     * @param string $content The merged .gitattributes content to normalize.
     *
     * @return string
     */
    private function format(string $content): string
    {
        $rows = [];
        $maxPathSpecLength = 0;

        foreach (preg_split('/\R/', $content) as $line) {
            $trimmedLine = trim((string) $line);

            if ('' === $trimmedLine) {
                $rows[] = [
                    'type' => 'raw',
                    'line' => '',
                ];

                continue;
            }

            if (str_starts_with($trimmedLine, '#')) {
                $rows[] = [
                    'type' => 'raw',
                    'line' => $trimmedLine,
                ];

                continue;
            }

            $entry = $this->parseEntry($trimmedLine);

            if (null === $entry) {
                $rows[] = [
                    'type' => 'raw',
                    'line' => $trimmedLine,
                ];

                continue;
            }

            $maxPathSpecLength = max($maxPathSpecLength, \strlen($entry['path_spec']));
            $rows[] = [
                'type' => 'entry',
                'path_spec' => $entry['path_spec'],
                'attributes' => $entry['attributes'],
            ];
        }

        $formattedLines = [];

        foreach ($rows as $row) {
            if ('entry' !== $row['type']) {
                $formattedLines[] = $row['line'];

                continue;
            }

            $formattedLines[] = str_pad($row['path_spec'], $maxPathSpecLength + 1) . $row['attributes'];
        }

        return implode("\n", $formattedLines) . "\n";
    }

    /**
     * Parses a .gitattributes entry into its path spec and attribute segment.
     *
     * @param string $line The normalized .gitattributes line.
     *
     * @return array{path_spec: string, attributes: string}|null
     */
    private function parseEntry(string $line): ?array
    {
        $separatorPosition = $this->firstUnescapedWhitespacePosition($line);

        if (null === $separatorPosition) {
            return null;
        }

        $pathSpec = substr($line, 0, $separatorPosition);
        $attributes = ltrim(substr($line, $separatorPosition));

        if ('' === $pathSpec || '' === $attributes) {
            return null;
        }

        return [
            'path_spec' => $pathSpec,
            'attributes' => $attributes,
        ];
    }

    /**
     * Locates the first non-escaped whitespace separator in a line.
     *
     * @param string $line the line to inspect
     *
     * @return int|null
     */
    private function firstUnescapedWhitespacePosition(string $line): ?int
    {
        $length = \strlen($line);

        for ($position = 0; $position < $length; ++$position) {
            if (! \in_array($line[$position], [' ', "\t"], true)) {
                continue;
            }

            $backslashCount = 0;

            for ($index = $position - 1; $index >= 0 && '\\' === $line[$index]; --$index) {
                ++$backslashCount;
            }

            if (0 === $backslashCount % 2) {
                return $position;
            }
        }

        return null;
    }
}
