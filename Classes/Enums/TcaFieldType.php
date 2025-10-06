<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Enums;

enum TcaFieldType: string
{
    case CATEGORY = 'category';
    case CHECK = 'check';
    case COLOR = 'color';
    case DATETIME = 'datetime';
    case EMAIL = 'email';
    case FILE = 'file';
    case FLEX = 'flex';
    case FOLDER = 'folder';
    case GROUP = 'group';
    case IMAGE_MANIPULATION = 'imageManipulation';
    case INLINE = 'inline';
    case INPUT = 'input';
    case JSON = 'json';
    case LANGUAGE = 'language';
    case LINK = 'link';
    case NONE = 'none';
    case NUMBER = 'number';
    case PASSTHROUGH = 'passthrough';
    case PASSWORD = 'password';
    case RADIO = 'radio';
    case SELECT = 'select';
    case SLUG = 'slug';
    case TEXT = 'text';
    case USER = 'user';
    case UUID = 'uuid';

    /**
     * Return all TCA type values as a flat list of strings
     * (useful for CLI choice lists etc).
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn(self $c) => $c->value, self::cases());
    }

    /**
     * Example TCA configuration for this column type.
     *
     * @return array<string,mixed>
     */
    public function exampleTca(): array
    {
        return match ($this) {
            self::CATEGORY => [
                'type' => 'category',
            ],
            self::CHECK => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    ['label' => 'Change me'],
                ],
            ],
            self::COLOR => [
                'type' => 'color',
            ],
            self::DATETIME => [
                'type' => 'datetime',
                'format' => 'date',
                'default' => 0,
            ],
            self::EMAIL => [
                'type' => 'email',
            ],
            self::FILE => [
                'type' => 'file',
                'maxitems' => 1,
                'allowed' => 'common-image-types',
            ],
            self::FLEX => [
                'type' => 'flex',
            ],
            self::FOLDER => [
                'type' => 'folder',
            ],
            self::GROUP => [
                'type' => 'group',
                'allowed' => '',
            ],
            self::IMAGE_MANIPULATION => [
                'type' => 'imageManipulation',
            ],
            self::INLINE => [
                'type' => 'inline',
            ],
            self::INPUT => [
                'type' => 'input',
            ],
            self::JSON => [
                'type' => 'json',
            ],
            self::LANGUAGE => [
                'type' => 'language',
            ],
            self::LINK => [
                'type' => 'link',
            ],
            self::NONE => [
                'type' => 'none',
            ],
            self::NUMBER => [
                'type' => 'number',
            ],
            self::PASSTHROUGH => [
                'type' => 'passthrough',
            ],
            self::PASSWORD => [
                'type' => 'password',
            ],
            self::RADIO => [
                'type' => 'radio',
                'items' => [
                    ['label' => 'Change me', 'value' => 1],
                ],
            ],
            self::SELECT => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Change me', 'value' => 1],
                ],
            ],
            self::SLUG => [
                'type' => 'slug',
            ],
            self::TEXT => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 7,
            ],
            self::USER => [
                'type' => 'user',
            ],
            self::UUID => [
                'type' => 'uuid',
            ],
        };
    }

    public function isDatabaseColumnAutoCreated(): bool
    {
        return match ($this) {
            self::PASSTHROUGH,
            self::NONE,
            self::USER,
            => false,

            default => true,
        };
    }
}
