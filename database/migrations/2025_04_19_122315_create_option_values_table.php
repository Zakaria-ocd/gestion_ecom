<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('option_values', function (Blueprint $table) {
            $table->id();
            $table->string('value', 255); // e.g., "XL", "Blue"
            $table->foreignId('option_id')->constrained('options')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('option_values');
    }
};