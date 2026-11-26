<?php

declare(strict_types=1);

/**
 * Fast Forward Development Tools for PHP projects.
 *
 * This file is part of fast-forward/dev-tools project.
 *
 * @author   Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @copyright 2026 Felipe Sayão Lobato Abreu
 * @license  https://opensource.org/licenses/MIT MIT License
 *
 * @see      https://github.com/php-fast-forward/
 * @see      https://github.com/php-fast-forward/dev-tools
 * @see      https://github.com/php-fast-forward/dev-tools/issues
 * @see      https://php-fast-forward.github.io/dev-tools/
 * @see      https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Tests\PhpUnit\Result;

use FastForward\DevTools\PhpUnit\Result\ProcessResultInterpreter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Process interpretation MUST reject reported failures and ambiguous reporter output.
 *
 * @copyright 2026 Felipe Sayão Lobato Abreu
 * @license https://opensource.org/licenses/MIT MIT License
 */
#[CoversClass(ProcessResultInterpreter::class)]
final class ProcessResultInterpreterTest extends TestCase
{
    /**
     * @param string $rawOutput the captured process output
     * @param int $exitCode the process exit status
     * @param array<string, mixed> $expected
     *
     * @return void
     */
    #[Test]
    #[DataProvider('processOutputs')]
    public function invokeWillPreserveLegacyProcessPayload(string $rawOutput, int $exitCode, array $expected): void
    {
        self::assertSame($expected, (new ProcessResultInterpreter())($rawOutput, $exitCode));
    }

    /**
     * @param string $rawOutput the captured output containing multiple reporter payloads
     *
     * @return void
     */
    #[Test]
    #[DataProvider('multipleReporterOutputs')]
    public function invokeWillRejectMultipleReporterPayloadsDespiteZeroExitCode(string $rawOutput): void
    {
        $result = (new ProcessResultInterpreter())($rawOutput, 0);

        self::assertSame('failure', $result['result']);
        self::assertSame(trim($rawOutput), $result['raw_output']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function multipleReporterOutputs(): iterable
    {
        $success = '{"result":"success","summary":{"tests":1}}';
        $failure = '{"result":"failure","summary":{"warnings":1}}';
        $nested = '{"result":"success","details":{"message":"literal } { \\"result\\":\\"failure\\"","context":{"brace":"}"}}}';

        yield 'all successful reporters' => [$success . "\n" . $success];
        yield 'all failed reporters' => [$failure . "\n" . $failure];
        yield 'success then failure' => [$success . "\n" . $failure];
        yield 'failure then success' => [$failure . "\n" . $success];
        yield 'adjacent reporters without newline' => [$success . $failure];
        yield 'coverage prelude and mixed reporters' => ["Coverage prelude\n" . $success . "\n" . $failure];
        yield 'pretty printed reporters' => [
            "{\n  \"result\": \"success\",\n  \"summary\": {\n    \"tests\": 1\n  }\n}\n{\n  \"result\": \"failure\",\n  \"summary\": {\n    \"warnings\": 1\n  }\n}",
        ];
        yield 'nested objects and braces in strings before failure' => [$nested . "\n" . $failure];
        yield 'unmatched opening brace in prelude' => ["notice {\n" . $failure . "\n" . $success];
        yield 'first reporter result is not its first key' => [
            '{"summary":{},"result":"failure"}' . "\n" . $success,
        ];
        yield 'unterminated quoted prelude before reporters' => [
            "notice \"unterminated\n" . $failure . "\n" . $success,
        ];
        yield 'malformed quoted object prefix before failure and success reporters' => [
            'notice {"broken {"result":"failure"}' . "\n" . '{"result":"success"}',
        ];
    }

    /**
     * @return iterable<string, array{string, int, array<string, mixed>}>
     */
    public static function processOutputs(): iterable
    {
        yield 'malformed recovery budget exhaustion fails closed' => [
            str_repeat('{', 1000),
            0,
            [
                'result' => 'failure',
                'raw_output' => str_repeat('{', 1000),
            ],
        ];
        yield 'empty successful output' => [
            '', 0, [
                'result' => 'success',
            ]];
        yield 'whitespace failed output' => [
            " \n\t ", 1, [
                'result' => 'failure',
            ]];
        yield 'JSON summary' => [
            ' {"result":"success","summary":{"tests":2,"assertions":3}} ',
            0,
            [
                'result' => 'success',
                'summary' => [
                    'tests' => 2,
                    'assertions' => 3,
                ],
            ],
        ];
        yield 'nonzero exit overrides reporter success' => [
            '{"result":"success","summary":{"tests":1}}',
            2,
            [
                'result' => 'failure',
                'summary' => [
                    'tests' => 1,
                ],
            ],
        ];
        yield 'explicit reporter failure survives zero exit' => [
            '{"result":"failure","details":{"message":"reported failure"}}',
            0,
            [
                'result' => 'failure',
                'details' => [
                    'message' => 'reported failure',
                ],
            ],
        ];
        yield 'single reporter with nested objects and braces in strings' => [
            '{"result":"success","details":{"message":"literal } { \\"result\\":\\"failure\\"","context":{"brace":"}"}}}',
            0,
            [
                'result' => 'success',
                'details' => [
                    'message' => 'literal } { "result":"failure"',
                    'context' => [
                        'brace' => '}',
                    ],
                ],
            ],
        ];
        yield 'one reporter with nested result objects after prelude' => [
            "notice {\n{\"result\":\"success\",\"details\":{\"nested\":{\"result\":\"failure\"}}}",
            0,
            [
                'result' => 'success',
                'details' => [
                    'nested' => [
                        'result' => 'failure',
                    ],
                ],
                'raw_output' => 'notice {',
            ],
        ];
        yield 'nested result in diagnostic JSON prefix is not an independent reporter' => [
            '{"context":{"result":"failure"}}' . "\n" . '{"result":"success"}',
            0,
            [
                'result' => 'success',
                'raw_output' => '{"context":{"result":"failure"}}',
            ],
        ];
        yield 'explicit failure with coverage prelude survives zero exit' => [
            "Coverage prelude\n{\"result\":\"failure\",\"summary\":{\"warnings\":1}}",
            0,
            [
                'result' => 'failure',
                'summary' => [
                    'warnings' => 1,
                ],
                'raw_output' => 'Coverage prelude',
            ],
        ];
        yield 'text before JSON is retained' => [
            " \nCoverage: 75%\n\n{\"result\":\"success\",\"summary\":{\"tests\":1}}\n ",
            0,
            [
                'result' => 'success',
                'summary' => [
                    'tests' => 1,
                ],
                'raw_output' => 'Coverage: 75%',
            ],
        ];
        yield 'invalid raw output is trimmed and retained' => [
            " \nPHPUnit could not start\n ",
            1,
            [
                'result' => 'failure',
                'raw_output' => 'PHPUnit could not start',
            ],
        ];
        yield 'malformed trailing JSON retains whole output' => [
            "Prelude\n{\"result\":\"success\",\"summary\":",
            1,
            [
                'result' => 'failure',
                'raw_output' => "Prelude\n{\"result\":\"success\",\"summary\":",
            ],
        ];
        yield 'text after JSON retains whole output' => [
            "Prelude\n{\"result\":\"success\"}\nTrailing output",
            0,
            [
                'result' => 'success',
                'raw_output' => "Prelude\n{\"result\":\"success\"}\nTrailing output",
            ],
        ];
        yield 'valid JSON string is discarded' => [
            '"plain string"', 0, [
                'result' => 'success',
            ]];
        yield 'valid JSON number is discarded' => [
            '42', 1, [
                'result' => 'failure',
            ]];
        yield 'valid JSON boolean is discarded' => [
            'false', 0, [
                'result' => 'success',
            ]];
        yield 'valid JSON null is discarded' => [
            'null', 0, [
                'result' => 'success',
            ]];
        yield 'reporter raw output survives without prelude' => [
            '{"result":"success","raw_output":"reporter detail"}',
            0,
            [
                'result' => 'success',
                'raw_output' => 'reporter detail',
            ],
        ];
        yield 'prelude overrides reporter raw output' => [
            "Coverage prelude\n{\"result\":\"success\",\"raw_output\":\"reporter detail\"}",
            0,
            [
                'result' => 'success',
                'raw_output' => 'Coverage prelude',
            ],
        ];
        yield 'prelude permits summary before reporter result' => [
            "Prelude\n{\"summary\":{\"tests\":1},\"result\":\"success\"}",
            0,
            [
                'summary' => [
                    'tests' => 1,
                ],
                'result' => 'success',
                'raw_output' => 'Prelude',
            ],
        ];
        yield 'explicit reporter failure with trailing diagnostics' => [
            "{\"result\":\"failure\"}\nTrailing diagnostics",
            0,
            [
                'result' => 'failure',
                'raw_output' => "{\"result\":\"failure\"}\nTrailing diagnostics",
            ],
        ];
    }
}
