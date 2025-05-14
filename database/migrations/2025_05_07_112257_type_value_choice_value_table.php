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
        Schema::create('type_value_choice_value', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_value_id')->constrained()->onDelete('cascade');
            $table->foreignId('choice_value_id')->constrained('choice_values')->onDelete('cascade');
            $table->string('colorCode')->nullable()->after('choice_value_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('type_value_choice_value');
    }
};
