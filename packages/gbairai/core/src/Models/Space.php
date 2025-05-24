<?php

declare(strict_types=1);

namespace Gbairai\Core\Models;

use Gbairai\Core\Concerns\HasUuidPrimaryKey;
use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Enums\SpaceStatus;
use Gbairai\Core\Enums\SpaceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Gbairai\Core\Models\Space
 *
 * @property string $id UUID
 * @property string $host_user_id UUID
 * @property string $title
 * @property string|null $description
 * @property string|null $cover_image_url
 * @property SpaceStatus $status
 * @property SpaceType $type
 * @property float|null $ticket_price
 * @property string|null $currency
 * @property int|null $max_participants
 * @property bool $is_recording_enabled_by_host
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 * @property int|null $duration_seconds
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read UserContract $host
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Gbairai\Core\Models\SpaceParticipant> $participants
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Gbairai\Core\Models\SpaceMessage> $messages
 * @property-read \Gbairai\Core\Models\SpaceRecording|null $recording
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\Gbairai\Core\Models\Space newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\Gbairai\Core\Models\Space newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\Gbairai\Core\Models\Space query()
 */
class Space extends Model
{
    use HasUuidPrimaryKey;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'spaces'; // Sera écrasé par la config si différent

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'host_user_id',
        'title',
        'description',
        'cover_image_url',
        'status',
        'type',
        'ticket_price',
        'currency',
        'max_participants',
        'is_recording_enabled_by_host',
        'scheduled_at',
        'started_at',
        'ended_at',
        'duration_seconds',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'host_user_id' => 'string',
        'status' => SpaceStatus::class,
        'type' => SpaceType::class,
        'ticket_price' => 'decimal:2',
        'max_participants' => 'integer',
        'is_recording_enabled_by_host' => 'boolean',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var list<string>
     */
    protected $with = [
        // 'host' // Exemple, à charger conditionnellement pour éviter N+1
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('gbairai-core.table_names.spaces', 'spaces');
    }

    /**
     * Get the host (user) of the space.
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(config('gbairai-core.models.user'), 'host_user_id');
    }

    /**
     * Get the participants of the space.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(config('gbairai-core.models.space_participant'), 'space_id');
    }

    /**
     * Get the messages in the space.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(config('gbairai-core.models.space_message'), 'space_id');
    }

    /**
     * Get the recording of the space, if any.
     */
    public function recording(): HasOne
    {
        return $this->hasOne(config('gbairai-core.models.space_recording'), 'space_id');
    }

    // TODO: Ajouter Scopes (live(), scheduled(), ended(), etc.)
    // TODO: Ajouter des accesseurs/mutateurs si nécessaire (ex: isLive(), durationForHumans())
}
