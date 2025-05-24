<?php

declare(strict_types=1);

namespace Gbairai\Core\Enums;

enum SpaceStatus: string
{
    case SCHEDULED = 'scheduled';
    case LIVE = 'live';
    case ENDED = 'ended';
    case CANCELLED = 'cancelled';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Programmé',
            self::LIVE => 'En Direct',
            self::ENDED => 'Terminé',
            self::CANCELLED => 'Annulé',
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
}
