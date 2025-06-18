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
        // Try to drop the index first (it's okay if this fails)
        try {
            DB::statement('DROP INDEX IF EXISTS personal_access_tokens_tokenable_type_tokenable_id_index');
        } catch (\Exception $e) {
            // Index might not exist, that's fine
        }
        
        // Use Schema builder to change the column type
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->string('tokenable_id', 36)->change();
        });
        
        // Recreate the index
        try {
            DB::statement('CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON personal_access_tokens(tokenable_type, tokenable_id)');
        } catch (\Exception $e) {
            // Index might already exist, that's fine
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Try to drop the index first (it's okay if this fails)
        try {
            DB::statement('DROP INDEX IF EXISTS personal_access_tokens_tokenable_type_tokenable_id_index');
        } catch (\Exception $e) {
            // Index might not exist, that's fine
        }
        
        // Revert the column type back to bigint
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Note: This will set all values to NULL since UUIDs can't be cast to bigint
            $table->bigInteger('tokenable_id')->nullable()->change();
        });
        
        // Recreate the index
        try {
            DB::statement('CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON personal_access_tokens(tokenable_type, tokenable_id)');
        } catch (\Exception $e) {
            // Index might already exist, that's fine
        }
    }
};
