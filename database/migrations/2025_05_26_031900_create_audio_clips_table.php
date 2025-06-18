<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected string $spacesTable;
    protected string $usersTable;
    protected string $audioClipsTable;

    public function __construct()
    {
        $this->spacesTable = config('gbairai-core.table_names.spaces', 'spaces');
        $this->usersTable = config('gbairai-core.table_names.users', 'users');
        $this->audioClipsTable = config('gbairai-core.table_names.audio_clips', 'audio_clips');
    }

    public function up(): void
    {
        Schema::create($this->audioClipsTable, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('space_id')->constrained($this->spacesTable)->cascadeOnDelete();
            $table->foreignUuid('creator_user_id')->constrained($this->usersTable)->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('clip_url')->comment('URL to the actual audio file (e.g., S3)');
            $table->unsignedInteger('start_time_in_space')->comment('In seconds from the start of the space recording');
            $table->unsignedInteger('duration_seconds')->comment('Duration of the clip in seconds');
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestampsTz();

            $table->index('space_id');
            $table->index('creator_user_id');
            $table->index('views_count'); // Pour trier par popularité
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->audioClipsTable);
    }
};
