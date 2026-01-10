<?php

return [
    'ctrl' => [
        'title' => 'My Table',
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
                    my_input, my_passthrough,
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
        'my_input' => [
            'exclude' => true,
            'label' => 'My Input',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
            ],
        ],
        'my_passthrough' => [
            'exclude' => true,
            'label' => 'My Passthrough',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
];
