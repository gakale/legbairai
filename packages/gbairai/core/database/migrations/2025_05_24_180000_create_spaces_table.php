<?php

declare(strict_types=1);

use Gbairai\Core\Enums\SpaceStatus;
use Gbairai\Core\Enums\SpaceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected string $usersTable;
    protected string $spacesTable;

    public function __construct()
    {
        $this->usersTable = config('gbairai-core.table_names.users', 'users');
        $this->spacesTable = config('gbairai-core.table_names.spaces', 'spaces');
    }

    public function up(): void
    {
        Schema::create($this->spacesTable, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('host_user_id')->comment('Référence vers l\'hôte du space');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->string('status')->default(SpaceStatus::SCHEDULED->value)->index();
            $table->string('type')->default(SpaceType::PUBLIC_FREE->value)->index();
            $table->decimal('ticket_price', 10, 2)->nullable();
            $table->string('currency', 3)->nullable(); // ex: XOF, USD, EUR
            $table->integer('max_participants')->nullable();
            $table->boolean('is_recording_enabled_by_host')->default(false);
            $table->timestampTz('scheduled_at')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('ended_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->timestampsTz(); // created_at, updated_at avec timezone

            // Ajouter des index pour les colonnes fréquemment interrogées
            $table->index(['host_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->spacesTable);
    }
};
