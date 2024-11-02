<?php

declare(strict_types=1);

return [
    'form' => [
        'title' => 'Titre',
        'url' => 'URL',
        'linkable_type' => 'Type',
        'linkable_id' => 'ID',
    ],
    'resource' => [
        'name' => [
            'label' => 'Nom',
        ],
        'locations' => [
            'label' => 'Emplacements',
            'empty' => 'Non assigné',
        ],
        'items' => [
            'label' => 'Éléments',
        ],
        'is_visible' => [
            'label' => 'Visibilité',
            'visible' => 'Visible',
            'hidden' => 'Masqué',
        ],
    ],
    'actions' => [
        'add' => [
            'label' => 'Ajouter au menu',
        ],
        'locations' => [
            'label' => 'Emplacements',
            'heading' => 'Gérer les emplacements',
            'description' => 'Choisissez quel menu apparaît à chaque emplacement.',
            'submit' => 'Mettre à jour',
            'form' => [
                'location' => [
                    'label' => 'Emplacement',
                ],
                'menu' => [
                    'label' => 'Menu assigné',
                ],
            ],
            'empty' => [
                'heading' => 'Aucun emplacement enregistré',
            ],
        ],
    ],
    'items' => [
        'expand' => 'Développer',
        'empty' => [
            'heading' => 'Il n’y a aucun élément dans ce menu.',
        ],
    ],
    'custom_link' => 'Lien personnalisé',
    'open_in' => [
        'label' => 'Ouvrir dans',
        'options' => [
            'self' => 'Même onglet',
            'blank' => 'Nouvel onglet',
            'parent' => 'Onglet parent',
            'top' => 'Onglet supérieur',
        ],
    ],
    'notifications' => [
        'created' => [
            'title' => 'Lien créé',
        ],
        'locations' => [
            'title' => 'Emplacements de menu mis à jour',
        ],
    ],
    'panel' => [
        'empty' => [
            'heading' => 'Aucun élément trouvé',
            'description' => 'Il n’y a aucun élément dans ce menu.',
        ],
        'pagination' => [
            'previous' => 'Précédent',
            'next' => 'Suivant',
        ],
    ],
];
