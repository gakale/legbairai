<?php

declare(strict_types=1);

namespace Gbairai\Core\Models;

use Gbairai\Core\Concerns\HasUuidPrimaryKey; // Si vous voulez des UUIDs pour la table follow elle-même (optionnel)
                                          // Généralement, pour une table pivot/jonction, une clé composite ou un ID auto-incrémenté simple suffit.
                                          // Pour la cohérence avec le reste, on peut utiliser HasUuidPrimaryKey.
use Gbairai\Core\Contracts\UserContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Gbairai\Core\Models\Follow
 *
 * @property string $id UUID (si HasUuidPrimaryKey est utilisé)
 * @property string $follower_user_id UUID de l'utilisateur qui suit
 * @property string $following_user_id UUID de l'utilisateur qui est suivi
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read UserContract $follower
 * @property-read UserContract $following
 */
class Follow extends Model
{
    // Option A: Utiliser HasUuidPrimaryKey si vous voulez un ID UUID pour chaque enregistrement de suivi.
    use HasUuidPrimaryKey;
    public $timestamps = true; // created_at et updated_at

    // Option B: Pas d'ID séparé, la clé primaire est la combinaison (follower_user_id, following_user_id)
    // Dans ce cas, commentez `use HasUuidPrimaryKey;` et définissez :
    // protected $primaryKey = ['follower_user_id', 'following_user_id'];
    // public $incrementing = false;
    // protected $keyType = 'string'; // Puisque les IDs utilisateurs sont des UUIDs (string)
    // public $timestamps = true; // created_at et updated_at (juste created_at pourrait suffire)

    // Pour la simplicité et la cohérence avec le reste, nous allons opter pour Option A (ID UUID séparé)
    // mais Option B est aussi une approche valide et optimisée pour les tables de jonction.

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'follows'; // Sera écrasé par la config

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'follower_user_id',
        'following_user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string', // Si Option A est utilisée
        'follower_user_id' => 'string',
        'following_user_id' => 'string',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('gbairai-core.table_names.follows', 'follows');
    }

    /**
     * Get the user who is following.
     */
    public function follower(): BelongsTo
    {
        return $this->belongsTo(config('gbairai-core.models.user'), 'follower_user_id');
    }

    /**
     * Get the user who is being followed.
     */
    public function following(): BelongsTo
    {
        return $this->belongsTo(config('gbairai-core.models.user'), 'following_user_id');
    }
}