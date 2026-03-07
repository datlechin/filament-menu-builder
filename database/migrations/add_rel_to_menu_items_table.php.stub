<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('filament-menu-builder.tables.menu_items', 'menu_items');

        if (Schema::hasColumn($table, 'rel')) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            $table->string('rel')->nullable()->after('classes');
        });
    }

    public function down(): void
    {
        $table = config('filament-menu-builder.tables.menu_items', 'menu_items');

        Schema::table($table, function (Blueprint $table) {
            $table->dropColumn('rel');
        });
    }
};
