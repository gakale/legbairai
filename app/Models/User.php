<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Panel;
use Gbairai\Core\Concerns\HasUuidPrimaryKey;
use Gbairai\Core\Concerns\InteractsWithGbairaiCore;
use Gbairai\Core\Contracts\UserContract;
use Illuminate\Support\Str;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements UserContract, FilamentUser, HasName
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuidPrimaryKey, InteractsWithGbairaiCore, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'name',
        'password',
        'phone_number',
        'avatar_url',
        'cover_photo_url',
        'bio',
        'is_verified',
        'is_premium',
        'paystack_customer_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_verified' => 'boolean',
        'is_premium' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            // Si username n'est pas défini ET que 'name' (fourni par filament:make-user) est défini
            if (empty($user->username) && !empty($user->name)) {
                // Créer un username unique à partir du nom.
                // Exemple simple : slugifier le nom et ajouter des chiffres si nécessaire pour l'unicité.
                $baseUsername = Str::slug($user->name);
                $username = $baseUsername;
                $counter = 1;
                // Vérifier l'unicité (boucle simple, peut être optimisée pour de très nombreux utilisateurs)
                while (static::where('username', $username)->exists()) {
                    $username = $baseUsername . $counter;
                    $counter++;
                }
                $user->username = $username;
            } elseif (empty($user->username) && !empty($user->email)) {
                // Solution de repli: créer à partir de la partie locale de l'email si 'name' n'est pas là
                $emailParts = explode('@', $user->email);
                $baseUsername = Str::slug($emailParts[0]);
                $username = $baseUsername;
                $counter = 1;
                while (static::where('username', $username)->exists()) {
                    $username = $baseUsername . $counter;
                    $counter++;
                }
                $user->username = $username;
            }
            // Si username est toujours vide ici, et qu'il est NOT NULL, une erreur surviendra.
            // Il faut s'assurer qu'il est toujours rempli.
            if (empty($user->username)) {
                // Fallback ultime : générer un username aléatoire si les autres méthodes échouent
                $user->username = Str::slug(Str::random(8)); // ou une autre logique pour assurer l'unicité
                 // S'assurer de l'unicité pour ce fallback aussi
                $baseUsername = $user->username;
                $counter = 1;
                while (static::where('username', $user->username)->exists()) {
                    $user->username = $baseUsername . $counter;
                    $counter++;
                }
            }
        });
    }
    // Implémentation des méthodes requises par UserContract
    public function getId(): string
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Pour l'instant, tous les utilisateurs peuvent accéder au panel admin par défaut.
        // Vous pouvez ajouter une logique plus fine ici (ex: vérifier un rôle 'admin')
        // return $this->hasRole('admin');
        // ou vérifier une adresse email spécifique
        // return str_ends_with($this->email, '@yourdomain.com') && $this->hasVerifiedEmail();
        return true;
    }
    
    /**
     * Get the name of the user for Filament.
     * 
     * @return string
     */
    public function getFilamentName(): string
    {
        // Utiliser le nom s'il existe, sinon le nom d'utilisateur, sinon une chaîne par défaut
        return $this->name ?? $this->username ?? 'Utilisateur ' . substr($this->id, 0, 8);
    }
}

