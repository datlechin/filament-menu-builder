<?php

declare(strict_types=1);

return [
    'resource' => [
        'label' => 'Menu',
        'plural_label' => 'Menu',
        'name' => 'Tên',
        'locations' => 'Vị trí',
        'no_locations' => 'Chưa gán',
        'location_taken' => 'Đang dùng cho :menu',
        'items' => 'Mục',
        'is_visible' => 'Hiển thị',
        'settings' => 'Cài đặt',
    ],
    'item_types' => [
        'link' => 'Liên kết',
        'text' => 'Văn bản',
        'route' => 'Route',
    ],
    'form' => [
        'title' => 'Tiêu đề',
        'url' => 'URL',
        'route' => 'Route',
        'route_parameters' => 'Tham số route',
        'open_in_new_tab' => 'Mở trong tab mới',
        'advanced' => 'Nâng cao',
        'rel' => 'Thuộc tính rel',
        'icon' => 'Biểu tượng',
        'classes' => 'CSS class',
        'is_visible' => 'Hiển thị',
    ],
    'items' => [
        'heading' => 'Các mục',
        'toggle' => 'Ẩn/hiện mục con',
        'hidden' => 'Đang ẩn',
        'broken' => 'Liên kết hỏng',
        'empty' => [
            'heading' => 'Menu này chưa có mục nào',
            'description' => 'Thêm liên kết, trang hoặc tiêu đề để bắt đầu.',
        ],
    ],
    'actions' => [
        'add_item' => 'Thêm mục',
        'add_child' => 'Thêm :type bên trong',
        'create' => [
            'heading' => 'Thêm :type',
        ],
        'delete' => [
            'description' => ':count mục con của nó cũng sẽ bị xóa.',
        ],
        'reorder' => 'Kéo để sắp xếp',
        'duplicateItem' => 'Nhân bản',
        'moveUp' => 'Lên trên',
        'moveDown' => 'Xuống dưới',
        'indent' => 'Thụt vào',
        'unindent' => 'Thụt ra',
    ],
    'notifications' => [
        'duplicated' => [
            'title' => 'Đã nhân bản',
        ],
        'move_rejected' => [
            'title' => 'Không thể đặt mục menu ở đó',
            'body' => 'Không thể lồng mục menu sâu quá :depth cấp.',
            'nesting' => 'Không thể lồng mục menu này vào mục đó.',
        ],
    ],
    'validation' => [
        'url' => ':attribute phải là địa chỉ web, đường dẫn, hoặc liên kết mailto: hay tel:.',
    ],
];
