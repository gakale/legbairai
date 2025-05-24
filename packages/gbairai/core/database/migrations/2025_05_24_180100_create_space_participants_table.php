<?php

declare(strict_types=1);

use Gbairai\Core\Enums\SpaceParticipantRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected string $usersTable;
    protected string $spacesTable;
    protected string $spaceParticipantsTable;

    public function __construct()
    {
        $this->usersTable = config('gbairai-core.table_names.users', 'users');
        $this->spacesTable = config('gbairai-core.table_names.spaces', 'spaces');
        $this->spaceParticipantsTable = config('gbairai-core.table_names.space_participants', 'space_participants');
    }

    public function up(): void
    {
        Schema::create($this->spaceParticipantsTable, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('space_id')->constrained($this->spacesTable)->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained($this->usersTable)->cascadeOnDelete();
            $table->string('role')->default(SpaceParticipantRole::LISTENER->value);
            $table->timestampTz('joined_at')->useCurrent();
            $table->timestampTz('left_at')->nullable();
            $table->boolean('is_muted_by_host')->default(true);
            $table->boolean('is_self_muted')->default(true);
            $table->boolean('has_raised_hand')->default(false);
            $table->timestampsTz();

            $table->unique(['space_id', 'user_id']);
            $table->index(['space_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->spaceParticipantsTable);
    }
};
