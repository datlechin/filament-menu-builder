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
            'empty' => 'Chưa gán',
        ],
        'is_visible' => [
            'label' => 'Hiển thị',
            'visible' => 'Hiển thị',
            'hidden' => 'Ẩn',
        ],
    ],
    'actions' => [
        'add' => [
            'label' => 'Thêm vào Menu',
        ],
        'locations' => [
            'label' => 'Quản lý Vị trí',
            'description' => 'Chọn menu nào xuất hiện ở mỗi vị trí.',
            'form' => [
                'location' => [
                    'label' => 'Vị trí',
                ],
                'menu' => [
                    'label' => 'Menu đã gán',
                ],
            ],
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
    'panel' => [
        'empty' => [
            'heading' => 'Không tìm thấy mục nào',
            'description' => 'Không có mục nào trong menu này.',
        ],
        'pagination' => [
            'previous' => 'Trước',
            'next' => 'Tiếp',
        ],
    ],
];
