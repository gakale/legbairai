<?php

declare(strict_types=1);

use Gbairai\Core\Enums\DonationStatus; // Importer l'Enum
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected string $usersTable;
    protected string $spacesTable;
    protected string $donationsTable;

    public function __construct()
    {
        $this->usersTable = config('gbairai-core.table_names.users', 'users');
        $this->spacesTable = config('gbairai-core.table_names.spaces', 'spaces');
        $this->donationsTable = config('gbairai-core.table_names.donations', 'donations');
    }

    public function up(): void
    {
        Schema::create($this->donationsTable, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('donor_user_id')->constrained($this->usersTable)->comment('User making the donation');
            $table->foreignUuid('recipient_user_id')->constrained($this->usersTable)->comment('User receiving the donation (creator)');
            $table->foreignUuid('space_id')->nullable()->constrained($this->spacesTable)->nullOnDelete()->comment('Optional space where donation was made');

            $table->unsignedBigInteger('amount_subunit')->comment('Amount in smallest currency unit (e.g., kobo, cents)');
            $table->string('currency', 3)->comment('Currency code (e.g., NGN, USD)');

            $table->string('paystack_reference')->unique()->comment('Unique reference for Paystack transaction attempt');
            $table->string('paystack_transaction_id')->nullable()->unique()->comment('Paystack transaction ID upon success');

            $table->string('status')->default(DonationStatus::PENDING->value)->index()->comment('Status of the donation');
            $table->json('metadata')->nullable()->comment('Additional data, e.g., Paystack response');

            $table->timestampsTz();

            $table->index('recipient_user_id');
            $table->index('space_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->donationsTable);
    }
};
