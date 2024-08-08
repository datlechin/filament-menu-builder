<?php

declare(strict_types=1);

return [
    'form' => [
        'title' => 'Tiêu đề',
        'url' => 'URL',
    ],
    'resource' => [
        'name' => [
            'label' => 'Tên',
        ],
        'locations' => [
            'label' => 'Vị trí',
            'description' => 'Chọn vị trí hiển thị menu.',
            'empty' => 'Chưa gán',
        ],
        'is_visible' => [
            'label' => 'Hiển thị',
        ],
    ],
    'actions' => [
        'add' => [
            'label' => 'Thêm vào Menu',
        ],
    ],
    'items' => [
        'empty' => [
            'heading' => 'Không có mục nào trong menu này.',
        ],
    ],
    'custom_link' => 'Liên kết Tùy chỉnh',
    'open_in' => [
        'label' => 'Mở trong',
        'options' => [
            'self' => 'Cùng tab',
            'blank' => 'Tab mới',
            'parent' => 'Tab cha',
            'top' => 'Tab trên cùng',
        ],
    ],
    'notifications' => [
        'created' => [
            'title' => 'Liên kết đã được tạo',
        ],
    ],
];
