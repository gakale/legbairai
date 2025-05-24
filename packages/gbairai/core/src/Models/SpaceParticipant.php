<?php

declare(strict_types=1);

namespace Gbairai\Core\Models;

use Gbairai\Core\Concerns\HasUuidPrimaryKey;
use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Enums\SpaceParticipantRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Gbairai\Core\Models\SpaceParticipant
 *
 * @property string $id UUID
 * @property string $space_id UUID
 * @property string $user_id UUID
 * @property SpaceParticipantRole $role
 * @property Carbon $joined_at
 * @property Carbon|null $left_at
 * @property bool $is_muted_by_host
 * @property bool $is_self_muted
 * @property bool $has_raised_hand
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read \Gbairai\Core\Models\Space $space
 * @property-read UserContract $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\Gbairai\Core\Models\SpaceParticipant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\Gbairai\Core\Models\SpaceParticipant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\Gbairai\Core\Models\SpaceParticipant query()
 */
class SpaceParticipant extends Model
{
    use HasUuidPrimaryKey;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'space_participants'; // Sera écrasé par la config

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'space_id',
        'user_id',
        'role',
        'joined_at',
        'left_at',
        'is_muted_by_host',
        'is_self_muted',
        'has_raised_hand',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'space_id' => 'string',
        'user_id' => 'string',
        'role' => SpaceParticipantRole::class,
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'is_muted_by_host' => 'boolean',
        'is_self_muted' => 'boolean',
        'has_raised_hand' => 'boolean',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true; // created_at et updated_at seront gérés

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('gbairai-core.table_names.space_participants', 'space_participants');
    }

    /**
     * Get the space this participant belongs to.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(config('gbairai-core.models.space'), 'space_id');
    }

    /**
     * Get the user associated with this participation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('gbairai-core.models.user'), 'user_id');
    }
}
