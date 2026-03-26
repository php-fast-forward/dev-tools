<?php

declare(strict_types=1);

$rules = [
    'phpdoc_indent' => true,
    'phpdoc_order' => [
        'order' => ['param', 'return', 'throws'],
    ],
    'phpdoc_separation' => true,
    'phpdoc_trim' => true,
    'phpdoc_trim_consecutive_blank_line_separation' => true,
    'phpdoc_scalar' => true,
    'phpdoc_types' => true,
    'phpdoc_to_comment' => false,
    'phpdoc_add_missing_param_annotation' => true,
];

$docheader = getcwd() . '/.docheader';

if (file_exists($docheader)) {
    $header = file_get_contents($docheader);

    $header = preg_replace(
        ['!^/\*\*\n!', '! \*/!', '! \* ?!', '!%year%!', '!' . date('Y-Y') . '!'],
        [null, null, null, date('Y'), date('Y')],
        $header
    );

    $header = trim($header);

    $rules['header_comment'] = [
        'header' => $header,
        'comment_type' => 'PHPDoc',
        'location' => 'after_declare_strict',
        'separate' => 'both',
    ];
}

$finder = PhpCsFixer\Finder::create()
    ->in([getcwd()])
    ->exclude('public')
    ->exclude('resources')
    ->exclude('vendor')
    ->exclude('tmp')
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setFinder($finder)
    ->setRules($rules);
