<?php

declare(strict_types=1);

return [
    'form' => [
        'title' => 'Title',
        'url' => 'URL',
    ],
    'resource' => [
        'name' => [
            'label' => 'Name',
        ],
        'locations' => [
            'label' => 'Locations',
            'description' => 'Choose where to display the menu.',
            'empty' => 'Unassigned',
        ],
        'is_visible' => [
            'label' => 'Visible',
        ],
    ],
    'actions' => [
        'add' => [
            'label' => 'Add to Menu',
        ],
    ],
    'items' => [
        'empty' => [
            'heading' => 'There are no items in this menu.',
        ],
    ],
    'custom_link' => 'Custom Link',
    'open_in' => [
        'label' => 'Open in',
        'options' => [
            'self' => 'Same tab',
            'blank' => 'New tab',
            'parent' => 'Parent tab',
            'top' => 'Top tab',
        ],
    ],
    'notifications' => [
        'created' => [
            'title' => 'Link created',
        ],
    ],
    'panel' => [
        'empty' => [
            'heading' => 'No items found',
            'description' => 'There are no items in this menu.',
        ],
    ],
];
