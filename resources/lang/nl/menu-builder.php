<?php

declare(strict_types=1);

return [
    'resource' => [
        'label' => 'Menu',
        'plural_label' => 'Menu\'s',
        'name' => 'Naam',
        'locations' => 'Locaties',
        'no_locations' => 'Geen',
        'location_taken' => 'Toont nu :menu',
        'items' => 'Items',
        'is_visible' => 'Zichtbaar',
        'settings' => 'Instellingen',
    ],
    'item_types' => [
        'link' => 'Link',
        'text' => 'Tekst',
        'route' => 'Route',
    ],
    'form' => [
        'title' => 'Titel',
        'url' => 'URL',
        'route' => 'Route',
        'route_parameters' => 'Routeparameters',
        'open_in_new_tab' => 'Openen in een nieuw tabblad',
        'advanced' => 'Geavanceerd',
        'rel' => 'Rel-attribuut',
        'icon' => 'Icoon',
        'classes' => 'CSS-klassen',
        'is_visible' => 'Zichtbaar',
    ],
    'items' => [
        'heading' => 'Items',
        'toggle' => 'Subitems tonen of verbergen',
        'hidden' => 'Verborgen',
        'broken' => 'Kapotte link',
        'empty' => [
            'heading' => 'Dit menu heeft geen items',
            'description' => 'Voeg links, pagina\'s of koppen toe om het op te bouwen.',
        ],
    ],
    'actions' => [
        'add_item' => 'Item toevoegen',
        'add_child' => ':type toevoegen hierin',
        'create' => [
            'heading' => ':type toevoegen',
        ],
        'delete' => [
            'description' => 'Het subitem wordt ook verwijderd.|De :count subitems worden ook verwijderd.',
        ],
        'reorder' => 'Sleep om te ordenen',
        'duplicateItem' => 'Dupliceren',
        'moveUp' => 'Omhoog',
        'moveDown' => 'Omlaag',
        'indent' => 'Inspringen',
        'unindent' => 'Uitspringen',
    ],
    'notifications' => [
        'duplicated' => [
            'title' => 'Gedupliceerd',
        ],
        'move_rejected' => [
            'title' => 'Het menu-item kan daar niet staan',
            'body' => 'Menu-items kunnen niet dieper dan :depth niveau worden genest.|Menu-items kunnen niet dieper dan :depth niveaus worden genest.',
            'nesting' => 'Dit menu-item kan niet in dat item worden genest.',
        ],
    ],
    'validation' => [
        'url' => ':attribute moet een webadres, een pad, of een mailto:- of tel:-link zijn.',
    ],
];
