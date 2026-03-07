<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('filament-menu-builder.tables.menu_items', 'menu_items');

        if (Schema::hasColumns($table, ['panel', 'icon', 'classes'])) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            if (! Schema::hasColumn($table->getTable(), 'panel')) {
                $table->string('panel')->nullable()->after('linkable_id');
            }

            if (! Schema::hasColumn($table->getTable(), 'icon')) {
                $table->string('icon')->nullable()->after('url');
            }

            if (! Schema::hasColumn($table->getTable(), 'classes')) {
                $table->string('classes')->nullable()->after('icon');
            }
        });
    }

    public function down(): void
    {
        $table = config('filament-menu-builder.tables.menu_items', 'menu_items');

        Schema::table($table, function (Blueprint $table) {
            $table->dropColumn(['panel', 'icon', 'classes']);
        });
    }
};
