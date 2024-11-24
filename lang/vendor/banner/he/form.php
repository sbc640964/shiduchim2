<?php

return [
    'tabs' => [
        'general' => '1 כללי',
        'styling' => '2 עיצוב',
        'scheduling' => '3 תזמון',
    ],
    'fields' => [
        'id' => 'מזהה',
        'name' => 'שם',
        'content' => 'תוכן',
        'render_location' => 'מיקום תצוגה',
        'render_location_help' => 'בעזרת מיקום התצוגה, תוכלו לבחור היכן באנר יוצג בעמוד. בשילוב עם תחומים, זה הופך לכלי חזק לניהול המיקום והזמן להצגת הבאנרים. ניתן לבחור להציג באנרים בכותרת, בצד או במיקומים אסטרטגיים אחרים כדי למקסם את הנראות וההשפעה שלהם.',
        'render_location_options' => [
            'panel' => [
                'header' => 'כותרת עליונה',
                'page_start' => 'תחילת העמוד',
                'page_end' => 'סוף העמוד',
            ],
            'authentication' => [
                'login_form_before' => 'לפני טופס התחברות',
                'login_form_after' => 'אחרי טופס התחברות',
                'password_reset_form_before' => 'לפני טופס איפוס סיסמה',
                'password_reset_form_after' => 'אחרי טופס איפוס סיסמה',
                'register_form_before' => 'לפני טופס הרשמה',
                'register_form_after' => 'אחרי טופס הרשמה',
            ],
            'global_search' => [
                'before' => 'לפני חיפוש גלובלי',
                'after' => 'אחרי חיפוש גלובלי',
            ],
            'page_widgets' => [
                'header_before' => 'לפני ווידג\'טים בכותרת',
                'header_after' => 'אחרי ווידג\'טים בכותרת',
                'footer_before' => 'לפני ווידג\'טים בכותרת תחתונה',
                'footer_after' => 'אחרי ווידג\'טים בכותרת תחתונה',
            ],
            'sidebar' => [
                'nav_start' => 'לפני ניווט בצד',
                'nav_end' => 'אחרי ניווט בצד',
            ],
            'resource_table' => [
                'before' => 'לפני טבלת המשאבים',
                'after' => 'אחרי טבלת המשאבים',
            ],
        ],
        'scope' => 'תחום',
        'scope_help' => 'בעזרת תחומים ניתן לשלוט היכן הבאנר יוצג. תוכלו לכוון את הבאנר לדפים ספציפיים או למשאבים שלמים, כך שיוצג לקהל הנכון בזמן הנכון.',
        'options' => 'אפשרויות',
        'can_be_closed_by_user' => 'ניתן לסגור את הבאנר על ידי המשתמש',
        'can_truncate_message' => 'קיצור תוכן הבאנר',
        'is_active' => 'פעיל',
        'text_color' => 'צבע טקסט',
        'icon' => 'אייקון',
        'icon_color' => 'צבע אייקון',
        'background' => 'רקע',
        'background_type' => 'סוג רקע',
        'background_type_solid' => 'רקע אחיד',
        'background_type_gradient' => 'רקע מדורג',
        'start_color' => 'צבע התחלה',
        'end_color' => 'צבע סיום',
        'start_time' => 'זמן התחלה',
        'start_time_reset' => 'איפוס זמן התחלה',
        'end_time' => 'זמן סיום',
        'end_time_reset' => 'איפוס זמן סיום',
    ],
    'badges' => [
        'scheduling_status' => [
            'active' => 'פעיל',
            'scheduled' => 'מתוזמן',
            'expired' => 'פג תוקף',
        ],
    ],
    'actions' => [
        'help' => 'עזרה',
        'reset' => 'איפוס',
    ],
];
