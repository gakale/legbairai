<?php

declare(strict_types=1);

namespace Gbairai\Core\Models;

use Gbairai\Core\Concerns\HasUuidPrimaryKey;
use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Enums\DonationStatus; // Nous allons créer cet Enum
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Gbairai\Core\Models\Donation
 *
 * @property string $id UUID
 * @property string $donor_user_id UUID (Utilisateur qui fait le don)
 * @property string $recipient_user_id UUID (Utilisateur qui reçoit le don, le créateur)
 * @property string|null $space_id UUID (Space où le don a été fait, optionnel)
 * @property int $amount_subunit Montant en sous-unité (ex: kobo, centimes)
 * @property string $currency Code de la devise (ex: NGN, USD, GHS)
 * @property string $paystack_reference Référence unique générée pour/par Paystack pour cette tentative
 * @property string|null $paystack_transaction_id ID de la transaction Paystack une fois réussie
 * @property DonationStatus $status Statut du don (pending, successful, failed)
 * @property array|null $metadata Informations supplémentaires (peut stocker la réponse de Paystack)
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read UserContract $donor
 * @property-read UserContract $recipient
 * @property-read Space|null $space
 */
class Donation extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'donations'; // Sera écrasé par la config

    protected $fillable = [
        'donor_user_id',
        'recipient_user_id',
        'space_id',
        'amount_subunit',
        'currency',
        'paystack_reference',
        'paystack_transaction_id',
        'status',
        'metadata',
    ];

    protected $casts = [
        'id' => 'string',
        'donor_user_id' => 'string',
        'recipient_user_id' => 'string',
        'space_id' => 'string',
        'amount_subunit' => 'integer',
        'status' => DonationStatus::class,
        'metadata' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('gbairai-core.table_names.donations', 'donations');
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(config('gbairai-core.models.user'), 'donor_user_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(config('gbairai-core.models.user'), 'recipient_user_id');
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(config('gbairai-core.models.space'), 'space_id');
    }

    /**
     * Accesseur pour obtenir le montant dans l'unité principale.
     * Suppose 100 sous-unités = 1 unité principale (comme kobo/Naira, cents/Dollar).
     * Adaptez si votre sous-unité est différente.
     */
    public function getAmountAttribute(): float
    {
        return $this->amount_subunit / 100;
    }
}