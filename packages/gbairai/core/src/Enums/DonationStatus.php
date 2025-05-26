<?php

declare(strict_types=1);

namespace Gbairai\Core\Enums;

enum DonationStatus: string
{
    case PENDING = 'pending';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';
    case ABANDONED = 'abandoned'; // Si l'utilisateur ferme la popup sans payer

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::SUCCESSFUL => 'Réussi',
            self::FAILED => 'Échoué',
            self::ABANDONED => 'Abandonné',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}