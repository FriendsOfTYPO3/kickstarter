<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Validator;

final readonly class PhpClassNameValidator
{
    /**
     * List of reserved keywords in PHP that cannot be used as class names (case-insensitive).
     * This list is a common compilation of hard-reserved words.
     * Some of these are context-sensitive keywords in newer PHP versions but are often
     * treated as reserved in the context of class/interface/trait names for compatibility
     * or future-proofing.
     * Note: 'void', 'iterable', 'object', etc., are not reserved as class names
     * in older PHP versions but might be in stricter contexts.
     *
     * @var array<string>
     */
    private const RESERVED_KEYWORDS = [
        '__halt_compiler',
        'abstract',
        'and',
        'array',
        'as',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'enum', // Added for PHP 8.1+
        'exit',
        'extends',
        'final',
        'finally',
        'fn', // Added for PHP 7.4+
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'interface',
        'isset',
        'list',
        'match', // Added for PHP 8.0+
        'namespace',
        'new',
        'or',
        'parent',
        'print',
        'private',
        'protected',
        'public',
        'readonly', // Added for PHP 8.1+
        'require',
        'require_once',
        'return',
        'self',
        'static',
        'switch',
        'throw',
        'trait',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor',
        'yield',
    ];

    public function validate(string $className): bool
    {
        // regex from https://www.php.net/manual/en/language.oop5.basic.php
        $isValidFormat = (bool)preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $className);
        if (!$isValidFormat) {
            return false;
        }

        return !in_array(strtolower($className), self::RESERVED_KEYWORDS, true);
    }
}
