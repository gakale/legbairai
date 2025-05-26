<?php

declare(strict_types=1);

namespace Gbairai\Core\Models;

use Gbairai\Core\Concerns\HasUuidPrimaryKey;
use Gbairai\Core\Contracts\UserContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Gbairai\Core\Models\AudioClip
 *
 * @property string $id UUID
 * @property string $space_id UUID (Le Space d'où provient le clip)
 * @property string $creator_user_id UUID (L'utilisateur qui a créé le clip, souvent l'hôte du Space)
 * @property string|null $title Titre du clip (optionnel, peut être généré)
 * @property string $clip_url URL vers le fichier audio du clip (ex: S3)
 * @property int $start_time_in_space Secondes depuis le début du Space où le clip commence
 * @property int $duration_seconds Durée du clip en secondes
 * @property int $views_count Nombre de vues du clip (dénormalisé, pour tri/popularité)
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read Space $space
 * @property-read UserContract $creator
 */
class AudioClip extends Model
{
    use HasUuidPrimaryKey;

    /**
     * The table associated with the model.
     */
    protected $table = 'audio_clips'; // Sera écrasé par la config

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'space_id',
        'creator_user_id',
        'title',
        'clip_url',
        'start_time_in_space',
        'duration_seconds',
        'views_count',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'id' => 'string',
        'space_id' => 'string',
        'creator_user_id' => 'string',
        'start_time_in_space' => 'integer',
        'duration_seconds' => 'integer',
        'views_count' => 'integer',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('gbairai-core.table_names.audio_clips', 'audio_clips');
    }

    /**
     * Get the Space from which this clip originates.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(config('gbairai-core.models.space'), 'space_id');
    }

    /**
     * Get the user who created this clip.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('gbairai-core.models.user'), 'creator_user_id');
    }
}