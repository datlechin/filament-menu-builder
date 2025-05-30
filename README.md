# Filament Menu Builder

[![Latest Version on Packagist](https://img.shields.io/packagist/v/datlechin/filament-menu-builder.svg?style=flat-square)](https://packagist.org/packages/datlechin/filament-menu-builder)
[![Total Downloads](https://img.shields.io/packagist/dt/datlechin/filament-menu-builder.svg?style=flat-square)](https://packagist.org/packages/datlechin/filament-menu-builder)

This [Filament](https://filamentphp.com) package allows you to create and manage menus in your Filament application.

![Filament Menu Builder](https://github.com/datlechin/filament-menu-builder/raw/main/art/menu-builder.jpg)

> [!NOTE]
> I created this for my personal project, so some features and extensibility are still lacking. Pull requests are welcome.

## Installation

You can install the package via composer:

```bash
composer require datlechin/filament-menu-builder
```

You need to publish the migrations and run them:

```bash
php artisan vendor:publish --tag="filament-menu-builder-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-menu-builder-config"
```

Optionally, if you want to customize the views, you can publish them with:

```bash
php artisan vendor:publish --tag="filament-menu-builder-views"
```

This is the contents of the published config file:

```php
return [
    'tables' => [
        'menus' => 'menus',
        'menu_items' => 'menu_items',
        'menu_locations' => 'menu_locations',
    ],
];
```

Add the plugin to `AdminPanelProvider`:

```php
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;

$panel
    ...
    ->plugin(FilamentMenuBuilderPlugin::make())
```

## Usage

### Adding locations

Locations are the places where you can display menus in the frontend. You can add locations in the `AdminPanelProvider`:

```php
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;

$panel
    ...
    ->plugin(
        FilamentMenuBuilderPlugin::make()
            ->addLocation('header', 'Header')
            ->addLocation('footer', 'Footer')
    )
```

The first argument is the key of the location, and the second argument is the title of the location.

Alternatively, you may add locations using an array:

```php
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;

$panel
    ...
    ->plugin(
        FilamentMenuBuilderPlugin::make()
            ->addLocations([
                'header' => 'Header',
                'footer' => 'Footer',
            ])
    )
```

### Setting up Menu Panels

Menu panels are the panels that contain the menu items which you can add to the menus.

#### Custom Link Menu Panel

By default, the package provides a **Custom Link** menu panel that allows you to add custom links to the menus.

![Custom Link Menu Panel](https://github.com/datlechin/filament-menu-builder/raw/main/art/custom-link.png)

The panel can be disabled by using the following when configuring the plugin, should you not need this functionality.

```php
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;

$panel
    ...
    ->plugin(
        FilamentMenuBuilderPlugin::make()
            ->showCustomLinkPanel(false)
    )
```

#### Custom Text Menu Panel

This package provides a **Custom Text** menu panel that allows you to add custom text items to the menus.

It is identical to the **Custom Link** menu panel except for the fact that you only set a title without a URL or target. This can be useful to add headers to mega-style menus.

The panel is disabled by default to prevent visual clutter. To enable the Custom Text menu panel, you can use the following when configuring the plugin.

```php
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;

$panel
    ...
    ->plugin(
        FilamentMenuBuilderPlugin::make()
            ->showCustomTextPanel()
    )
```

#### Static Menu Panel

The static menu panel allows you to add menu items manually.

```php
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\MenuPanel\StaticMenuPanel;

$panel
    ...
    ->plugin(
        FilamentMenuBuilderPlugin::make()
            ->addMenuPanels([
                StaticMenuPanel::make()
                    ->add('Home', url('/'))
                    ->add('Blog', url('/blog')),
            ])
    )
```

Similarily to locations, you may also add static menu items using an array:

```php
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\MenuPanel\StaticMenuPanel;

$panel
    ...
    ->plugin(
        FilamentMenuBuilderPlugin::make()
            ->addMenuPanels([
                StaticMenuPanel::make()
                    ->addMany([
                        'Home' => url('/'),
                        'Blog' => url('/blog'),
                    ])
            ])
    )
```

![Static Menu Panel](https://github.com/datlechin/filament-menu-builder/raw/main/art/static-menu.png)

#### Model Menu Panel

The model menu panel allows you to add menu items from a model.

To create a model menu panel, your model must implement the `\Datlechin\FilamentMenuBuilder\Contracts\MenuPanelable` interface and `\Datlechin\FilamentMenuBuilder\Concerns\HasMenuPanel` trait.

Then you must also implement the `getMenuPanelTitleColumn` and `getMenuPanelUrlUsing` methods. A complete example of this implementation is as follows:

```php
use Datlechin\FilamentMenuBuilder\Concerns\HasMenuPanel;
use Datlechin\FilamentMenuBuilder\Contracts\MenuPanelable;
use Illuminate\Database\Eloquent\Model;

class Category extends Model implements MenuPanelable
{
    use HasMenuPanel;

    public function getMenuPanelTitleColumn(): string
    {
        return 'name';
    }

    public function getMenuPanelUrlUsing(): callable
    {
        return fn (self $model) => route('categories.show', $model->slug);
    }
}
```

Then you can add the model menu panel to the plugin:

```php
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\MenuPanel\ModelMenuPanel;

$panel
    ...
    ->plugin(
        FilamentMenuBuilderPlugin::make()
            ->addMenuPanels([
                ModelMenuPanel::make()
                    ->model(\App\Models\Category::class),
            ])
    )
```

![Model Menu Panel](https://github.com/datlechin/filament-menu-builder/raw/main/art/model-menu.png)

#### Additional Menu Panel Options

When registering a menu panel, multiple methods are available allowing you to configure the panel's behavior such as collapse state and pagination.

```php
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\MenuPanel\StaticMenuPanel;

$panel
    ...
    ->plugin(
        FilamentMenuBuilderPlugin::make()
            ->addMenuPanels([
                StaticMenuPanel::make()
                    ->addMany([
                        ...
                    ])
                    ->description('Lorem ipsum...')
                    ->icon('heroicon-m-link')
                    ->collapsed(true)
                    ->collapsible(true)
                    ->paginate(perPage: 5, condition: true)
            ])
    )
```

### Custom Fields

In some cases, you may want to extend menu and menu items with custom fields. To do this, start by passing an array of form components to the `addMenuFields` and `addMenuItemFields` methods when registering the plugin:

```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;

$panel
    ...
    ->plugin(
        FilamentMenuBuilderPlugin::make()
            ->addMenuFields([
                Toggle::make('is_logged_in'),
            ])
            ->addMenuItemFields([
                TextInput::make('classes'),
            ])
    )
```

Next, create a migration adding the additional columns to the appropriate tables:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(config('filament-menu-builder.tables.menus'), function (Blueprint $table) {
            $table->boolean('is_logged_in')->default(false);
        });

        Schema::table(config('filament-menu-builder.tables.menu_items'), function (Blueprint $table) {
            $table->string('classes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('filament-menu-builder.tables.menus'), function (Blueprint $table) {
            $table->dropColumn('is_logged_in');
        });

        Schema::table(config('filament-menu-builder.tables.menu_items'), function (Blueprint $table) {
            $table->dropColumn('classes');
        });
    }
}
```

Once done, simply run `php artisan migrate`.

### Customizing the Resource

Out of the box, a default Menu Resource is registered with Filament when registering the plugin in the admin provider. This resource can be extended and overridden allowing for more fine-grained control.

Start by extending the `Datlechin\FilamentMenuBuilder\Resources\MenuResource` class in your application. Below is an example:

```php
namespace App\Filament\Plugins\Resources;

use Datlechin\FilamentMenuBuilder\Resources\MenuResource as BaseMenuResource;

class MenuResource extends BaseMenuResource
{
    protected static ?string $navigationGroup = 'Navigation';

    public static function getNavigationBadge(): ?string
    {
        return number_format(static::getModel()::count());
    }
}
```

Now pass the custom resource to `usingResource` while registering the plugin with the panel:

```php
use App\Filament\Plugins\Resources\MenuResource;
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;

$panel
    ...
    ->plugin(
        FilamentMenuBuilderPlugin::make()
            ->usingResource(MenuResource::class)
    )
```

### Customizing the Models

The default models used by the plugin can be configured and overridden similarly to the plugin resource as seen above.

Simply extend the default models and then pass the classes when registering the plugin in the panel:

```php
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuLocation;
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;

$panel
    ...
    ->plugin(
        FilamentMenuBuilderPlugin::make()
            ->usingMenuModel(Menu::class)
            ->usingMenuItemModel(MenuItem::class)
            ->usingMenuLocationModel(MenuLocation::class)
    )
```

### Using Menus

Getting the assigned menu for a registered location can be done using the `Menu` model. Below we will call the menu assigned to the `primary` location:

```php
use Datlechin\FilamentMenuBuilder\Models\Menu;

$menu = Menu::location('primary');
```

Menu items can be iterated from the `menuItems` relationship:

```php
@foreach ($menu->menuItems as $item)
    <a href="{{ $item->url }}">{{ $item->title }}</a>
@endforeach
```

When a menu item is a parent, a collection of the child menu items will be available on the `children` property:

```php
@foreach ($menu->menuItems as $item)
    <a href="{{ $item->url }}">{{ $item->title }}</a>

    @if ($item->children)
        @foreach ($item->children as $child)
            <a href="{{ $child->url }}">{{ $child->title }}</a>
        @endforeach
    @endif
@endforeach
```

### Configuring Indent/Unindent Actions

The package includes indent and unindent buttons that provide an alternative to drag-and-drop for organizing menu hierarchy. This feature is enabled by default but can be configured:

```php
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;

$panel
    ...
    ->plugin(
        FilamentMenuBuilderPlugin::make()
            ->enableIndentActions(false) // Disable
    )
```

## Changelog

Please see [CHANGELOG](https://github.com/datlechin/filament-menu-builder/raw/main/CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/datlechin/filament-menu-builder/raw/main/.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](https://github.com/datlechin/filament-menu-builder/security/policy) on how to report security vulnerabilities.

## Credits

- [Ngo Quoc Dat](https://github.com/datlechin)
- [Log1x](https://github.com/Log1x)
- [All Contributors](https://github.com/datlechin/filament-menu-builder/contributors)

## License

The MIT License (MIT). Please see [License File](https://github.com/datlechin/filament-menu-builder/raw/main/LICENSE.md) for more information.
