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
        // Create carts table if it doesn't exist
        if (!Schema::hasTable('carts')) {
            Schema::create('carts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->timestamp('created_at')->useCurrent();
            });
        }
        
        // Create cart_items table if it doesn't exist
        if (!Schema::hasTable('cart_items')) {
            Schema::create('cart_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cart_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->integer('quantity')->default(1);
                $table->decimal('price', 10, 2);
                $table->timestamp('added_at')->useCurrent();
            });
        }
        
        // Create orders table if it doesn't exist
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('status', 20)->nullable(false)
                    ->default('pending')
                    ->comment('Order status: pending, shipped, delivered');
                $table->decimal('total_price', 10, 2)->default(0);
                $table->string('address')->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('payment_method', 20)->default('cash_on_delivery');
                $table->timestamp('created_at')->useCurrent();
            });
        }
        
        // Create order_items table if it doesn't exist
        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->integer('quantity')->default(1);
                $table->decimal('price', 10, 2);
                $table->timestamp('added_at')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't want to drop these tables on rollback if they were already there
    }
}; 