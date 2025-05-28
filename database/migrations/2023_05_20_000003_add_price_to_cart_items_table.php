<?php

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
        if (Schema::hasTable('cart_items') && !Schema::hasColumn('cart_items', 'price')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->decimal('price', 10, 2)->after('quantity');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cart_items') && Schema::hasColumn('cart_items', 'price')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }
    }
}; 