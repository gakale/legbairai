<?php

declare(strict_types=1);

namespace Gbairai\Core\Models;

use Gbairai\Core\Concerns\HasUuidPrimaryKey;
use Gbairai\Core\Contracts\UserContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Gbairai\Core\Models\SpaceMessage
 *
 * @property string $id UUID
 * @property string $space_id UUID
 * @property string $user_id UUID
 * @property string $content
 * @property bool $is_pinned
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read \Gbairai\Core\Models\Space $space
 * @property-read UserContract $user Sender of the message
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\Gbairai\Core\Models\SpaceMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\Gbairai\Core\Models\SpaceMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\Gbairai\Core\Models\SpaceMessage query()
 */
class SpaceMessage extends Model
{
    use HasUuidPrimaryKey;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'space_messages'; // Sera écrasé par la config

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'space_id',
        'user_id',
        'content',
        'is_pinned',
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
        'is_pinned' => 'boolean',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true; // Gère created_at et updated_at

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('gbairai-core.table_names.space_messages', 'space_messages');
    }

    /**
     * Get the space this message belongs to.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(config('gbairai-core.models.space'), 'space_id');
    }

    /**
     * Get the user who sent the message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('gbairai-core.models.user'), 'user_id');
    }

    // TODO: Ajouter des scopes si nécessaire (ex: pinned())
}
