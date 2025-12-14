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
    case CHECK_TOGGLE = 'check-toggle';
    case COLOR = 'color';
    case DATETIME = 'datetime';
    case EMAIL = 'email';
    case FILE = 'file';
    case FILE_IMAGES = 'file-images';
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
    case SELECT_FOREIGN = 'select-foreign';
    case SLUG = 'slug';
    case TEXT = 'text';
    case TEXT_RTE = 'text-rte';
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

    public function basicFields(): array
    {
        return [
            self::INPUT,
            self::TEXT_RTE,
            self::LINK,
            self::FILE_IMAGES,
            self::NUMBER,
            self::DATETIME,
            self::CHECK_TOGGLE,
            self::RADIO,
            self::SELECT,
        ];
    }

    public function additionalFields(): array
    {
        // Get all defined cases
        $all = self::cases();

        // Collect all fields already used in the other groups
        $used = array_merge(
            $this->basicFields(),
            $this->systemFields(),
            $this->relationalFields(),
        );

        // Filter out the used ones
        return array_values(array_filter(
            $all,
            fn(self $case): bool => !in_array($case, $used, true)
        ));
    }

    public function systemFields(): array
    {
        return [
            self::FLEX,
            self::IMAGE_MANIPULATION,
            self::JSON,
            self::NONE,
            self::PASSTHROUGH,
            self::UUID,
        ];
    }

    public function relationalFields(): array
    {
        return [
            self::INLINE,
            self::GROUP,
            self::SELECT_FOREIGN,
        ];
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
                'items' => [
                    ['label' => 'Option 1'],
                    ['label' => 'Option 2'],
                ],
            ],
            self::CHECK_TOGGLE => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    ['label' => 'Enable'],
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
                'allowed' => 'common-text-types',
            ],
            self::FILE_IMAGES => [
                'type' => 'file',
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
                'allowed' => 'tx_someextension_changeme',
            ],
            self::IMAGE_MANIPULATION => [
                'type' => 'imageManipulation',
            ],
            self::INLINE => [
                'type' => 'inline',
                'foreign_table' => 'tx_someextension_changeme',
            ],
            self::INPUT => [
                'type' => 'input',
                'eval' => 'trim',
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
            self::SELECT_FOREIGN => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_someextension_changeme',
            ],
            self::SLUG => [
                'type' => 'slug',
            ],
            self::TEXT => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 7,
            ],
            self::TEXT_RTE => [
                'type' => 'text',
                'enableRichtext' => true,
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
