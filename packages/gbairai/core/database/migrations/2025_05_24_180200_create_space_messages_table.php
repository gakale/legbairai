<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected string $usersTable;
    protected string $spacesTable;
    protected string $spaceMessagesTable;

    public function __construct()
    {
        $this->usersTable = config('gbairai-core.table_names.users', 'users');
        $this->spacesTable = config('gbairai-core.table_names.spaces', 'spaces');
        $this->spaceMessagesTable = config('gbairai-core.table_names.space_messages', 'space_messages');
    }

    public function up(): void
    {
        Schema::create($this->spaceMessagesTable, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('space_id')->constrained($this->spacesTable)->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained($this->usersTable)->cascadeOnDelete();
            $table->text('content');
            $table->boolean('is_pinned')->default(false);
            $table->timestampsTz();

            $table->index('space_id');
            $table->index(['space_id', 'is_pinned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->spaceMessagesTable);
    }
};
