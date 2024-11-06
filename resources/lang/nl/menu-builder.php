<?php

declare(strict_types=1);

return [
    'form' => [
        'title' => 'Titel',
        'url' => 'URL',
        'linkable_type' => 'Type',
        'linkable_id' => 'ID',
    ],
    'resource' => [
        'name' => [
            'label' => 'Naam',
        ],
        'locations' => [
            'label' => 'Locaties',
            'empty' => 'Niet ingesteld',
        ],
        'items' => [
            'label' => 'Items',
        ],
        'is_visible' => [
            'label' => 'Zichtbaarheid',
            'visible' => 'Zichtbaar',
            'hidden' => 'Verborgen',
        ],
    ],
    'actions' => [
        'add' => [
            'label' => 'Toevoegen aan menu',
        ],
        'locations' => [
            'label' => 'Locaties',
            'heading' => 'Locaties beheren',
            'description' => 'Stel in welk menu op welke locatie wordt getoond.',
            'submit' => 'Wijzigingen opslaan',
            'form' => [
                'location' => [
                    'label' => 'Locatie',
                ],
                'menu' => [
                    'label' => 'Gekoppeld menu',
                ],
            ],
            'empty' => [
                'heading' => 'Geen locaties gevonden',
            ],
        ],
    ],
    'items' => [
        'expand' => 'Uitklappen',
        'empty' => [
            'heading' => 'Dit menu heeft geen items.',
        ],
    ],
    'custom_link' => 'Aangepaste link',
    'open_in' => [
        'label' => 'Openen op',
        'options' => [
            'self' => 'Huidig tabblad',
            'blank' => 'Nieuw tabblad',
            'parent' => 'Bovenliggend tabblad',
            'top' => 'Hoofdtabblad',
        ],
    ],
    'notifications' => [
        'created' => [
            'title' => 'Link aangemaakt',
        ],
        'locations' => [
            'title' => 'Menulocaties bijgewerkt',
        ],
    ],
    'panel' => [
        'empty' => [
            'heading' => 'Geen items gevonden',
            'description' => 'Dit menu heeft geen items.',
        ],
        'pagination' => [
            'previous' => 'Vorige',
            'next' => 'Volgende',
        ],
    ],
];
