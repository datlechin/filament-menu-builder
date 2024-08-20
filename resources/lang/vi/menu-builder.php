<?php

declare(strict_types=1);

return [
    'form' => [
        'title' => 'Tiêu đề',
        'url' => 'URL',
        'linkable_type' => 'Loại',
        'linkable_id' => 'ID',
    ],
    'resource' => [
        'name' => [
            'label' => 'Tên',
        ],
        'locations' => [
            'label' => 'Vị trí',
            'empty' => 'Chưa gán',
        ],
        'items' => [
            'label' => 'Mục',
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
            'label' => 'Vị trí',
            'heading' => 'Quản lý vị trí',
            'description' => 'Chọn menu nào xuất hiện ở mỗi vị trí.',
            'submit' => 'Cập nhật',
            'form' => [
                'location' => [
                    'label' => 'Vị trí',
                ],
                'menu' => [
                    'label' => 'Menu đã gán',
                ],
            ],
            'empty' => [
                'heading' => 'Không có vị trí nào được đăng ký',
            ],
        ],
    ],
    'items' => [
        'expand' => 'Mở rộng',
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
        'locations' => [
            'title' => 'Cập nhật vị trí menu',
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
