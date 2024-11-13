<?php

return [

    'label' => 'בונה שאילתות',

    'form' => [

        'operator' => [
            'label' => 'אופרטור',
        ],

        'or_groups' => [

            'label' => 'קבוצות',

            'block' => [
                'label' => 'בלוק (או)',
                'or' => 'או',
            ],

        ],

        'rules' => [

            'label' => 'תנאים',

            'item' => [
                'and' => 'ו',
            ],

        ],

    ],

    'no_rules' => '(אין תנאים)',

    'item_separators' => [
        'and' => 'וגם',
        'or' => 'או',
    ],

    'operators' => [

        'is_filled' => [

            'label' => [
                'direct' => 'מלא',
                'inverse' => 'ריק',
            ],

            'summary' => [
                'direct' => ':attribute מלא',
                'inverse' => ':attribute ריק',
            ],

        ],

        'boolean' => [

            'is_true' => [

                'label' => [
                    'direct' => 'אמת',
                    'inverse' => 'שקר',
                ],

                'summary' => [
                    'direct' => ':attribute אמת',
                    'inverse' => ':attribute שקר',
                ],

            ],

        ],

        'date' => [

            'is_after' => [

                'label' => [
                    'direct' => 'הוא אחרי',
                    'inverse' => 'הוא לא אחרי',
                ],

                'summary' => [
                    'direct' => ':attribute אחרי :date',
                    'inverse' => ':attribute לא אחרי :date',
                ],

            ],

            'is_before' => [

                'label' => [
                    'direct' => 'הוא לפני',
                    'inverse' => 'הוא לא לפני',
                ],

                'summary' => [
                    'direct' => ':attribute לפני :date',
                    'inverse' => ':attribute לא לפני :date',
                ],

            ],

            'is_date' => [

                'label' => [
                    'direct' => 'Is הוא תאריך',
                    'inverse' => 'Is הוא לא תאריך',
                ],

                'summary' => [
                    'direct' => ':attribute הוא :date',
                    'inverse' => ':attribute הוא לא :date',
                ],

            ],

            'is_month' => [

                'label' => [
                    'direct' => 'הוא חודש',
                    'inverse' => 'הוא לא חודש',
                ],

                'summary' => [
                    'direct' => ':attribute הוא :month',
                    'inverse' => ':attribute הוא לא :month',
                ],

            ],

            'is_year' => [

                'label' => [
                    'direct' => 'הוא שנה',
                    'inverse' => 'הוא לא שנה',
                ],

                'summary' => [
                    'direct' => ':attribute הוא :year',
                    'inverse' => ':attribute הוא לא :year',
                ],

            ],

            'form' => [

                'date' => [
                    'label' => 'תאריך',
                ],

                'month' => [
                    'label' => 'חודש',
                ],

                'year' => [
                    'label' => 'שנה',
                ],

            ],

        ],

        'number' => [

            'equals' => [

                'label' => [
                    'direct' => 'שווה',
                    'inverse' => 'לא שווה',
                ],

                'summary' => [
                    'direct' => ':attribute שווה ל:number',
                    'inverse' => ':attribute לא שווה ל:number',
                ],

            ],

            'is_max' => [

                'label' => [
                    'direct' => 'מקסימום',
                    'inverse' => 'הוא יותר מ',
                ],

                'summary' => [
                    'direct' => ':attribute הוא מקסימום :number',
                    'inverse' => ':attribute הוא יותר מ :number',
                ],

            ],

            'is_min' => [

                'label' => [
                    'direct' => 'הוא מינימום',
                    'inverse' => 'הוא פחות מ',
                ],

                'summary' => [
                    'direct' => ':attribute הוא מינימום :number',
                    'inverse' => ':attribute הוא פחות מ :number',
                ],

            ],

            'aggregates' => [

                'average' => [
                    'label' => 'ממוצע',
                    'summary' => 'ממוצע :attribute',
                ],

                'max' => [
                    'label' => 'מקס\'',
                    'summary' => 'מקס\' :attribute',
                ],

                'min' => [
                    'label' => 'מינ\'',
                    'summary' => 'מינ\' :attribute',
                ],

                'sum' => [
                    'label' => 'סה"כ',
                    'summary' => 'סה"כ :attribute',
                ],

            ],

            'form' => [

                'aggregate' => [
                    'label' => 'צבירות',
                ],

                'number' => [
                    'label' => 'מספר',
                ],

            ],

        ],

        'relationship' => [

            'equals' => [

                'label' => [
                    'direct' => 'יש לו',
                    'inverse' => 'אין לו',
                ],

                'summary' => [
                    'direct' => 'יש לו :count :relationship',
                    'inverse' => 'אין לו :count :relationship',
                ],

            ],

            'has_max' => [

                'label' => [
                    'direct' => 'יש לו מקסימום',
                    'inverse' => 'יש לו יותר מ',
                ],

                'summary' => [
                    'direct' => 'יש לו מקסימום :count :relationship',
                    'inverse' => 'יש לו יותר מ :count :relationship',
                ],

            ],

            'has_min' => [

                'label' => [
                    'direct' => 'יש לו  מינימום',
                    'inverse' => 'יש לו פחות מ',
                ],

                'summary' => [
                    'direct' => 'יש לו  מינימום :count :relationship',
                    'inverse' => 'יש לו פחות מ :count :relationship',
                ],

            ],

            'is_empty' => [

                'label' => [
                    'direct' => 'הוא ריק',
                    'inverse' => 'הוא לא ריק',
                ],

                'summary' => [
                    'direct' => ':relationship הוא ריק',
                    'inverse' => ':relationship הוא לא ריק',
                ],

            ],

            'is_related_to' => [

                'label' => [

                    'single' => [
                        'direct' => 'הוא',
                        'inverse' => 'הוא לא',
                    ],

                    'multiple' => [
                        'direct' => 'מכיל',
                        'inverse' => 'לא מכיל',
                    ],

                ],

                'summary' => [

                    'single' => [
                        'direct' => ':relationship הוא :values',
                        'inverse' => ':relationship הוא לא :values',
                    ],

                    'multiple' => [
                        'direct' => ':relationship מכיל :values',
                        'inverse' => ':relationship לא מכיל :values',
                    ],

                    'values_glue' => [
                        0 => ', ',
                        'final' => ' או ',
                    ],

                ],

                'form' => [

                    'value' => [
                        'label' => 'ערך',
                    ],

                    'values' => [
                        'label' => 'ערכים',
                    ],

                ],

            ],

            'form' => [

                'count' => [
                    'label' => 'ספירה',
                ],

            ],

        ],

        'select' => [

            'is' => [

                'label' => [
                    'direct' => 'הוא',
                    'inverse' => 'הוא לא',
                ],

                'summary' => [
                    'direct' => ':attribute הוא :values',
                    'inverse' => ':attribute הוא לא :values',
                    'values_glue' => [
                        ', ',
                        'final' => ' או ',
                    ],
                ],

                'form' => [

                    'value' => [
                        'label' => 'ערך',
                    ],

                    'values' => [
                        'label' => 'ערכים',
                    ],

                ],

            ],

        ],

        'text' => [

            'contains' => [

                'label' => [
                    'direct' => 'מכיל',
                    'inverse' => 'לא מכיל',
                ],

                'summary' => [
                    'direct' => ':attribute מכיל :text',
                    'inverse' => ':attribute לא מכיל :text',
                ],

            ],

            'ends_with' => [

                'label' => [
                    'direct' => 'מסתיים ב',
                    'inverse' => 'לא מסתיים ב',
                ],

                'summary' => [
                    'direct' => ':attribute מסתיים ב :text',
                    'inverse' => ':attribute לא מסתיים ב :text',
                ],

            ],

            'equals' => [

                'label' => [
                    'direct' => 'שווה ל',
                    'inverse' => 'לא שווה ל',
                ],

                'summary' => [
                    'direct' => ':attribute שווה ל :text',
                    'inverse' => ':attribute לא שווה ל :text',
                ],

            ],

            'starts_with' => [

                'label' => [
                    'direct' => 'מתחיל ב',
                    'inverse' => 'לא מתחיל ב',
                ],

                'summary' => [
                    'direct' => ':attribute מתחיל ב :text',
                    'inverse' => ':attribute לא מתחיל ב :text',
                ],

            ],

            'form' => [

                'text' => [
                    'label' => 'טקסט',
                ],

            ],

        ],

    ],

    'actions' => [

        'add_rule' => [
            'label' => 'הוסף תנאי',
        ],

        'add_rule_group' => [
            'label' => 'הוסף קבוצת תנאים',
        ],

    ],

];
