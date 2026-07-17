<?php

declare(strict_types=1);

return [
    'form' => [
        'title' => 'Заголовок',
        'url' => 'Ссылка (URL)',
        'icon' => 'Иконка',
        'classes' => 'CSS-классы',
        'rel' => 'Атрибут rel',
        'linkable_type' => 'Тип',
        'linkable_id' => 'ID',
    ],
    'resource' => [
        'name' => [
            'label' => 'Название',
        ],
        'locations' => [
            'label' => 'Места вывода',
            'empty' => 'Не назначено',
        ],
        'items' => [
            'label' => 'Пункты',
        ],
        'is_visible' => [
            'label' => 'Видимость',
            'visible' => 'Показать',
            'hidden' => 'Скрыть',
        ],
    ],
    'actions' => [
        'add' => [
            'label' => 'Добавить в меню',
        ],
        'edit' => 'Редактировать',
        'delete' => 'Удалить',
        'indent' => 'Сдвинуть вправо',
        'unindent' => 'Сдвинуть влево',
        'locations' => [
            'label' => 'Места вывода',
            'heading' => 'Управление местами вывода',
            'description' => 'Выберите, какое меню должно отображаться в каждом месте.',
            'submit' => 'Обновить',
            'form' => [
                'location' => [
                    'label' => 'Место',
                ],
                'menu' => [
                    'label' => 'Назначенное меню',
                ],
            ],
            'empty' => [
                'heading' => 'Нет зарегистрированных мест вывода',
            ],
        ],
    ],
    'items' => [
        'expand' => 'Развернуть',
        'collapse' => 'Свернуть',
        'empty' => [
            'heading' => 'В этом меню пока нет пунктов.',
        ],
    ],
    'custom_link' => 'Произвольная ссылка',
    'custom_text' => 'Текст без ссылки',
    'open_in' => [
        'label' => 'Открывать в',
        'options' => [
            'self' => 'Текущая вкладка',
            'blank' => 'Новая вкладка',
            'parent' => 'Родительская вкладка',
            'top' => 'Главная вкладка',
        ],
    ],
    'notifications' => [
        'created' => [
            'title' => 'Пункт добавлен',
        ],
        'locations' => [
            'title' => 'Места вывода обновлены',
        ],
    ],
    'panel' => [
        'empty' => [
            'heading' => 'Ничего не найдено',
            'description' => 'В этом меню пока нет пунктов.',
        ],
        'pagination' => [
            'previous' => 'Назад',
            'next' => 'Вперёд',
        ],
    ],
];
