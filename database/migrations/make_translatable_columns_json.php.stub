<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The default locale to use when migrating existing string data to JSON.
     */
    protected string $defaultLocale = 'en';

    public function up(): void
    {
        $menuItemsTable = config('filament-menu-builder.tables.menu_items', 'menu_items');
        $menusTable = config('filament-menu-builder.tables.menus', 'menus');

        $this->convertColumnToJson($menuItemsTable, 'title');
        $this->convertColumnToJson($menusTable, 'name');
    }

    public function down(): void
    {
        $menuItemsTable = config('filament-menu-builder.tables.menu_items', 'menu_items');
        $menusTable = config('filament-menu-builder.tables.menus', 'menus');

        $this->revertColumnFromJson($menuItemsTable, 'title');
        $this->revertColumnFromJson($menusTable, 'name');
    }

    protected function convertColumnToJson(string $table, string $column): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        DB::table($table)->get()->each(function ($row) use ($table, $column) {
            $value = $row->{$column};

            if (is_string($value) && ! str_starts_with($value, '{') && ! str_starts_with($value, '[')) {
                DB::table($table)->where('id', $row->id)->update([
                    $column => json_encode([$this->defaultLocale => $value]),
                ]);
            }
        });

        Schema::table($table, function (Blueprint $table) use ($column) {
            $table->json($column)->change();
        });
    }

    protected function revertColumnFromJson(string $table, string $column): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        DB::table($table)->get()->each(function ($row) use ($table, $column) {
            $value = $row->{$column};
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                DB::table($table)->where('id', $row->id)->update([
                    $column => collect($decoded)->first() ?? '',
                ]);
            }
        });

        Schema::table($table, function (Blueprint $table) use ($column) {
            $table->string($column)->change();
        });
    }
};
