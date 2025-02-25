<?php

declare(strict_types=1);

return [
    'form' => [
        'title' => 'العنوان',
        'url' => 'الرابط',
        'linkable_type' => 'النوع',
        'linkable_id' => 'المعرف',
    ],
    'resource' => [
        'name' => [
            'label' => 'الاسم',
        ],
        'locations' => [
            'label' => 'المواقع',
            'empty' => 'غير معين',
        ],
        'items' => [
            'label' => 'العناصر',
        ],
        'is_visible' => [
            'label' => 'الرؤية',
            'visible' => 'مرئي',
            'hidden' => 'مخفي',
        ],
    ],
    'actions' => [
        'add' => [
            'label' => 'إضافة إلى القائمة',
        ],
        'locations' => [
            'label' => 'المواقع',
            'heading' => 'إدارة المواقع',
            'description' => 'اختر القائمة التي تظهر في كل موقع.',
            'submit' => 'تحديث',
            'form' => [
                'location' => [
                    'label' => 'الموقع',
                ],
                'menu' => [
                    'label' => 'القائمة المعينة',
                ],
            ],
            'empty' => [
                'heading' => 'لا توجد مواقع مسجلة',
            ],
        ],
    ],
    'items' => [
        'expand' => 'توسيع',
        'collapse' => 'طي',
        'empty' => [
            'heading' => 'لا توجد عناصر في هذه القائمة.',
        ],
    ],
    'custom_link' => 'رابط مخصص',
    'custom_text' => 'نص مخصص',
    'open_in' => [
        'label' => 'فتح في',
        'options' => [
            'self' => 'نفس علامة التبويب',
            'blank' => 'علامة تبويب جديدة',
            'parent' => 'علامة التبويب الأصلية',
            'top' => 'علامة التبويب العليا',
        ],
    ],
    'notifications' => [
        'created' => [
            'title' => 'تم إنشاء الرابط',
        ],
        'locations' => [
            'title' => 'تم تحديث مواقع القائمة',
        ],
    ],
    'panel' => [
        'empty' => [
            'heading' => 'لم يتم العثور على عناصر',
            'description' => 'لا توجد عناصر في هذه القائمة.',
        ],
        'pagination' => [
            'previous' => 'السابق',
            'next' => 'التالي',
        ],
    ],
];
