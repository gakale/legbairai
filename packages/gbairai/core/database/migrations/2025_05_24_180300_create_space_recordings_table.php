<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected string $spacesTable;
    protected string $spaceRecordingsTable;

    public function __construct()
    {
        $this->spacesTable = config('gbairai-core.table_names.spaces', 'spaces');
        $this->spaceRecordingsTable = config('gbairai-core.table_names.space_recordings', 'space_recordings');
    }

    public function up(): void
    {
        Schema::create($this->spaceRecordingsTable, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('space_id')->unique()->constrained($this->spacesTable)->cascadeOnDelete();
            $table->string('recording_url')->unique();
            $table->decimal('file_size_mb', 8, 2)->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->boolean('is_publicly_accessible')->default(true);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->spaceRecordingsTable);
    }
};
