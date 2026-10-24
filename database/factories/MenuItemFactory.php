<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Database\Factories;

use Datlechin\FilamentMenuBuilder\MenuBuilder;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    public function modelName(): string
    {
        return MenuBuilder::menuItemModel();
    }

    public function definition(): array
    {
        return [
            'menu_id' => MenuBuilder::menuModel()::factory(),
            'type' => 'link',
            'title' => fake()->words(2, true),
            'url' => '/' . fake()->slug(2),
            'order' => 0,
        ];
    }

    public function text(): static
    {
        return $this->state(['type' => 'text', 'url' => null]);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function route(string $name, array $parameters = []): static
    {
        return $this->state(['type' => 'route', 'url' => null, 'route' => $name, 'route_parameters' => $parameters ?: null]);
    }

    public function linkedTo(Model $linkable): static
    {
        return $this->state([
            'type' => 'model',
            'url' => null,
            'linkable_type' => $linkable->getMorphClass(),
            'linkable_id' => $linkable->getKey(),
        ]);
    }

    public function childOf(MenuItem $parent): static
    {
        return $this->state(['menu_id' => $parent->menu_id, 'parent_id' => $parent->getKey()]);
    }

    public function hidden(): static
    {
        return $this->state(['is_visible' => false]);
    }
}
