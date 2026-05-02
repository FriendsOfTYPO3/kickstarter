<?php

return [
    'ctrl' => [
        'title' => 'Article',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'typeicon_classes' => [
            'default' => 'actions-brand-typo3',
        ],
    ],
    'types' => [
        [
            'showitem' => '
                --div--;core.form.tabs:general,
                    title, content, images, featured, published_on,
                --div--;core.form.tabs:language,
                    --palette--;;language,
                --div--;core.form.tabs:access,
                    hidden,--palette--;;access,
                --div--;core.form.tabs:extended,
            ',
        ],
    ],
    'palettes' => [
        'access' => [
            'showitem' => 'starttime;core.db.general:starttime,endtime;core.db.general:endtime',
        ],
        'language' => [
            'showitem' => 'sys_language_uid, l10n_parent',
        ],
    ],
    'columns' => [
        'title' => [
            'exclude' => true,
            'label' => 'Title',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
            ],
        ],
        'content' => [
            'exclude' => true,
            'label' => 'Content',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
            ],
        ],
        'images' => [
            'exclude' => true,
            'label' => 'Images',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
            ],
        ],
        'featured' => [
            'exclude' => true,
            'label' => 'Do you want to feature this blog post? ',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => 'Enable',
                    ],
                ],
            ],
        ],
        'published_on' => [
            'exclude' => true,
            'label' => 'When was this article first published? ',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'default' => 0,
            ],
        ],
    ],
];
