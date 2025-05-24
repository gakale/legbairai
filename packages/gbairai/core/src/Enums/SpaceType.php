<?php

declare(strict_types=1);

namespace Gbairai\Core\Enums;

enum SpaceType: string
{
    case PUBLIC_FREE = 'public_free';
    case PUBLIC_PAID = 'public_paid'; // Billetterie
    case PRIVATE_INVITE = 'private_invite';
    case PRIVATE_SUBSCRIBER = 'private_subscriber'; // Accès via abonnement au créateur

    /**
     * Get the human-readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::PUBLIC_FREE => 'Public (Gratuit)',
            self::PUBLIC_PAID => 'Public (Payant)',
            self::PRIVATE_INVITE => 'Privé (Sur Invitation)',
            self::PRIVATE_SUBSCRIBER => 'Privé (Abonnés)',
        };
    }

    /**
     * Get an array of all case values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if the space type is public.
     */
    
    public function isPublic(): bool
    {
        return in_array($this, [self::PUBLIC_FREE, self::PUBLIC_PAID], true);
    }
}
