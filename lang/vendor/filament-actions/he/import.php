<?php

return [

    'label' => 'ייבוא :label',

    'modal' => [

        'heading' => 'ייבוא :label',

        'form' => [

            'file' => [
                'label' => 'קובץ',
                'placeholder' => 'בחר קובץ CSV',
            ],

            'columns' => [
                'label' => 'עמודות',
                'placeholder' => 'בחר עמודה',
            ],

        ],

        'actions' => [

            'download_example' => [
                'label' => 'הורד קובץ דוגמא',
            ],

            'import' => [
                'label' => 'ייבא',
            ],

        ],

    ],

    'notifications' => [

        'completed' => [

            'title' => 'ייבוא הושלם',

            'actions' => [

                'download_failed_rows_csv' => [
                    'label' => 'הורד מידע על השורה שנכשלה|הורד מידע על השורות שנכשלו',
                ],

            ],

        ],

        'max_rows' => [
            'title' => 'הקובץ אותו בחרת גדול מדי',
            'body' => 'אינך יכול לייבא יותר משורה אחת בכל פעם.|אינך יכול לייבא יותר מ-:count שורות בכל פעם.',
        ],

        'started' => [
            'title' => 'הייבוא החל',
            'body' => 'הייבוא שלך החל ושורה אחת תעובד ברקע.|הייבוא שלך החל ו-:count שורות יעובדו ברקע.'
        ],

    ],

    'example_csv' => [
        'file_name' => ':importer-example',
    ],

    'failure_csv' => [
        'file_name' => 'import-:import_id-:csv_name-failed-rows',
        'error_header' => 'שגיאה',
        'system_error' => 'שגיאת מערכת',
    ],

];
