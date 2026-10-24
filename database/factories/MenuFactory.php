<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Database\Factories;

use Datlechin\FilamentMenuBuilder\MenuBuilder;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Menu>
 */
class MenuFactory extends Factory
{
    public function modelName(): string
    {
        return MenuBuilder::menuModel();
    }

    public function definition(): array
    {
        return [
            'name' => in_array('name', MenuBuilder::translatableAttributes('menu'), true)
                ? [MenuBuilder::locales()[0] => fake()->words(2, true)]
                : fake()->words(2, true),
            'is_visible' => true,
        ];
    }

    public function hidden(): static
    {
        return $this->state(['is_visible' => false]);
    }
}
