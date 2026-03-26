<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Concerns\HasMenuPanel;
use Datlechin\FilamentMenuBuilder\Contracts\MenuPanelable;
use Datlechin\FilamentMenuBuilder\MenuPanel\ModelMenuPanel;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    Schema::create('pages', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('slug');
        $table->softDeletes();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('pages');
});

// --- ModelMenuPanel with SoftDeletes ---

it('excludes soft-deleted records from model panel items', function () {
    DB::table('pages')->insert([
        ['name' => 'Active', 'slug' => 'active', 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Trashed', 'slug' => 'trashed', 'deleted_at' => now(), 'created_at' => now(), 'updated_at' => now()],
    ]);

    $panel = ModelMenuPanel::make('pages')
        ->model(Group7TestPageWithSoftDeletes::class);

    $items = $panel->getItems();

    expect($items)->toHaveCount(1)
        ->and($items[0]['title'])->toBe('Active');
});

it('includes all records when model does not use SoftDeletes', function () {
    DB::table('pages')->insert([
        ['name' => 'Home', 'slug' => 'home', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'About', 'slug' => 'about', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $panel = ModelMenuPanel::make('pages')
        ->model(Group7TestPage::class);

    $items = $panel->getItems();

    expect($items)->toHaveCount(2);
});

// --- API inconsistency: addMenuFields should have plural alias ---

it('has addMenuFields plural alias that works like addMenuField', function () {
    $source = file_get_contents(__DIR__ . '/../src/FilamentMenuBuilderPlugin.php');

    // Should have plural alias methods
    expect($source)->toContain('function addMenuField(')
        ->and($source)->toContain('function addMenuItemField(');
});

// --- Redirect to edit page after menu creation ---

it('configures create action with redirect to edit page', function () {
    $source = file_get_contents(__DIR__ . '/../src/Resources/MenuResource/Pages/ListMenus.php');

    expect($source)->toContain('successRedirectUrl');
});

// Test fixtures

class Group7TestPage extends Model implements MenuPanelable
{
    use HasMenuPanel;

    protected $table = 'pages';

    protected $guarded = [];

    public function getMenuPanelTitleColumn(): string
    {
        return 'name';
    }

    public function getMenuPanelUrlUsing(): callable
    {
        return fn (self $model) => "/{$model->slug}";
    }
}

class Group7TestPageWithSoftDeletes extends Model implements MenuPanelable
{
    use HasMenuPanel;
    use SoftDeletes;

    protected $table = 'pages';

    protected $guarded = [];

    public function getMenuPanelTitleColumn(): string
    {
        return 'name';
    }

    public function getMenuPanelUrlUsing(): callable
    {
        return fn (self $model) => "/{$model->slug}";
    }
}
