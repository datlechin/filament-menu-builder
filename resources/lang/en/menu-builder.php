<?php

declare(strict_types=1);

return [
    'form' => [
        'title' => 'Title',
        'url' => 'URL',
        'linkable_type' => 'Type',
        'linkable_id' => 'ID',
    ],
    'resource' => [
        'name' => [
            'label' => 'Name',
        ],
        'locations' => [
            'label' => 'Locations',
            'empty' => 'Unassigned',
        ],
        'items' => [
            'label' => 'Items',
        ],
        'is_visible' => [
            'label' => 'Visibility',
            'visible' => 'Visible',
            'hidden' => 'Hidden',
        ],
    ],
    'actions' => [
        'add' => [
            'label' => 'Add to Menu',
        ],
        'locations' => [
            'label' => 'Locations',
            'heading' => 'Manage Locations',
            'description' => 'Choose which menu appears at each location.',
            'submit' => 'Update',
            'form' => [
                'location' => [
                    'label' => 'Location',
                ],
                'menu' => [
                    'label' => 'Assigned Menu',
                ],
            ],
            'empty' => [
                'heading' => 'No locations registered',
            ],
        ],
    ],
    'items' => [
        'expand' => 'Expand',
        'collapse' => 'Collapse',
        'empty' => [
            'heading' => 'There are no items in this menu.',
        ],
    ],
    'custom_link' => 'Custom Link',
    'custom_text' => 'Custom Text',
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
        'locations' => [
            'title' => 'Menu locations updated',
        ],
    ],
    'panel' => [
        'empty' => [
            'heading' => 'No items found',
            'description' => 'There are no items in this menu.',
        ],
        'pagination' => [
            'previous' => 'Previous',
            'next' => 'Next',
        ],
    ],
];
