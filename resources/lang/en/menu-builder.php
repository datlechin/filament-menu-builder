<?php

declare(strict_types=1);

return [
    'resource' => [
        'label' => 'Menu',
        'plural_label' => 'Menus',
        'name' => 'Name',
        'locations' => 'Locations',
        'no_locations' => 'None',
        'location_taken' => 'Used by :menu',
        'items' => 'Items',
        'is_visible' => 'Visible',
        'settings' => 'Settings',
    ],
    'item_types' => [
        'link' => 'Link',
        'text' => 'Text',
        'route' => 'Route',
    ],
    'form' => [
        'title' => 'Title',
        'url' => 'URL',
        'route' => 'Route',
        'route_parameters' => 'Route parameters',
        'open_in_new_tab' => 'Open in a new tab',
        'advanced' => 'Advanced',
        'rel' => 'Rel attribute',
        'icon' => 'Icon',
        'classes' => 'CSS classes',
        'is_visible' => 'Visible',
    ],
    'items' => [
        'heading' => 'Items',
        'toggle' => 'Toggle sub-items',
        'hidden' => 'Hidden',
        'broken' => 'Broken link',
        'empty' => [
            'heading' => 'This menu has no items',
            'description' => 'Add links, pages or headings to start building it.',
        ],
    ],
    'actions' => [
        'add_item' => 'Add item',
        'add_child' => 'Add :type inside',
        'create' => [
            'heading' => 'Add :type',
        ],
        'delete' => [
            'description' => 'Its sub-item is deleted too.|Its :count sub-items are deleted too.',
        ],
        'reorder' => 'Drag to reorder',
        'duplicateItem' => 'Duplicate',
        'moveUp' => 'Move up',
        'moveDown' => 'Move down',
        'indent' => 'Indent',
        'unindent' => 'Outdent',
    ],
    'notifications' => [
        'duplicated' => [
            'title' => 'Duplicated',
        ],
        'move_rejected' => [
            'title' => 'This item can\'t be placed there',
            'body' => 'Menu items cannot be nested more than :depth level deep.|Menu items cannot be nested more than :depth levels deep.',
            'nesting' => 'This menu item cannot be nested inside that item.',
        ],
    ],
    'validation' => [
        'url' => 'The :attribute must be a web address, a path, or a mailto: or tel: link.',
    ],
];
