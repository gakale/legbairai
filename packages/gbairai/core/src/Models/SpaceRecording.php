<?php

declare(strict_types=1);

namespace Gbairai\Core\Models;

use Gbairai\Core\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Gbairai\Core\Models\SpaceRecording
 *
 * @property string $id UUID
 * @property string $space_id UUID
 * @property string $recording_url
 * @property float|null $file_size_mb
 * @property int|null $duration_seconds
 * @property bool $is_publicly_accessible
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read \Gbairai\Core\Models\Space $space
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\Gbairai\Core\Models\SpaceRecording newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\Gbairai\Core\Models\SpaceRecording newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\Gbairai\Core\Models\SpaceRecording query()
 */
class SpaceRecording extends Model
{
    use HasUuidPrimaryKey;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'space_recordings'; // Sera écrasé par la config

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'space_id',
        'recording_url',
        'file_size_mb',
        'duration_seconds',
        'is_publicly_accessible',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'space_id' => 'string',
        'file_size_mb' => 'decimal:2',
        'duration_seconds' => 'integer',
        'is_publicly_accessible' => 'boolean',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('gbairai-core.table_names.space_recordings', 'space_recordings');
    }

    /**
     * Get the space this recording belongs to.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(config('gbairai-core.models.space'), 'space_id');
    }
}
