<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        
        if (Schema::hasTable('carts') && Schema::hasColumn('carts', 'buyer_id')) {
            Schema::table('carts', function (Blueprint $table) {
                
                $foreignKeys = $this->listTableForeignKeys('carts');
                if (in_array('carts_buyer_id_foreign', $foreignKeys)) {
                    $table->dropForeign(['buyer_id']);
                }
                
                
                $table->renameColumn('buyer_id', 'user_id');
                
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        } 
        
        else if (Schema::hasTable('carts') && !Schema::hasColumn('carts', 'user_id')) {
            Schema::table('carts', function (Blueprint $table) {
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('carts') && Schema::hasColumn('carts', 'user_id')) {
            Schema::table('carts', function (Blueprint $table) {
                
                $foreignKeys = $this->listTableForeignKeys('carts');
                if (in_array('carts_user_id_foreign', $foreignKeys)) {
                    $table->dropForeign(['user_id']);
                }
                
                
                $table->renameColumn('user_id', 'buyer_id');
                
                
                $table->foreign('buyer_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }
    
    /**
     * Get the foreign keys for a given table.
     *
     * @param string $table
     * @return array
     */
    protected function listTableForeignKeys($table)
    {
        $conn = Schema::getConnection()->getDoctrineSchemaManager();
        
        return array_map(
            fn($key) => $key->getName(),
            $conn->listTableForeignKeys($table)
        );
    }
}; 