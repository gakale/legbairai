<?php

declare(strict_types=1);

namespace Gbairai\Core\Enums;

enum SpaceParticipantRole: string
{
    case LISTENER = 'listener';
    case SPEAKER = 'speaker';
    case CO_HOST = 'co_host';
    // L'hôte principal (créateur du Space) est identifié par `spaces.host_user_id`,
    // donc pas besoin d'un rôle 'host' ici, sauf si on veut le dupliquer pour simplifier certaines requêtes.
    // Pour l'instant, on le garde ainsi.

    /**
     * Get the human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::LISTENER => 'Auditeur',
            self::SPEAKER => 'Intervenant',
            self::CO_HOST => 'Co-hôte',
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
