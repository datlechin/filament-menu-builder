<?php

declare(strict_types=1);

return [
    'resource' => [
        'label' => 'Menu',
        'plural_label' => 'Menus',
        'name' => 'Nom',
        'locations' => 'Emplacements',
        'no_locations' => 'Aucun',
        'location_taken' => 'Affiche actuellement :menu',
        'items' => 'Éléments',
        'is_visible' => 'Visible',
        'settings' => 'Paramètres',
    ],
    'item_types' => [
        'link' => 'Lien',
        'text' => 'Texte',
        'route' => 'Route',
    ],
    'form' => [
        'title' => 'Titre',
        'url' => 'URL',
        'route' => 'Route',
        'route_parameters' => 'Paramètres de la route',
        'open_in_new_tab' => 'Ouvrir dans un nouvel onglet',
        'advanced' => 'Avancé',
        'rel' => 'Attribut rel',
        'icon' => 'Icône',
        'classes' => 'Classes CSS',
        'is_visible' => 'Visible',
    ],
    'items' => [
        'heading' => 'Éléments',
        'toggle' => 'Afficher ou masquer les sous-éléments',
        'hidden' => 'Masqué',
        'broken' => 'Lien cassé',
        'empty' => [
            'heading' => 'Ce menu ne contient aucun élément',
            'description' => 'Ajoutez des liens, des pages ou des titres pour le construire.',
        ],
    ],
    'actions' => [
        'add_item' => 'Ajouter un élément',
        'add_child' => 'Ajouter :type à l\'intérieur',
        'create' => [
            'heading' => 'Ajouter :type',
        ],
        'delete' => [
            'description' => 'Son sous-élément sera aussi supprimé.|Ses :count sous-éléments seront aussi supprimés.',
        ],
        'reorder' => 'Glisser pour réordonner',
        'duplicateItem' => 'Dupliquer',
        'moveUp' => 'Monter',
        'moveDown' => 'Descendre',
        'indent' => 'Indenter',
        'unindent' => 'Désindenter',
    ],
    'notifications' => [
        'duplicated' => [
            'title' => 'Dupliqué',
        ],
        'move_rejected' => [
            'title' => 'L\'élément de menu ne peut pas être placé ici',
            'body' => 'Les éléments de menu ne peuvent pas être imbriqués sur plus de :depth niveau.|Les éléments de menu ne peuvent pas être imbriqués sur plus de :depth niveaux.',
            'nesting' => 'Cet élément de menu ne peut pas être imbriqué dans cet élément.',
        ],
    ],
    'validation' => [
        'url' => 'Le champ :attribute doit être une adresse web, un chemin, ou un lien mailto: ou tel:.',
    ],
];
