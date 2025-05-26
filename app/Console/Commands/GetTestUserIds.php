<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GetTestUserIds extends Command
{
    protected $signature = 'users:test-ids';
    protected $description = 'Affiche les IDs des utilisateurs de test';

    public function handle()
    {
        $users = User::whereIn('username', ['testuser1', 'testuser2', 'testuser3', 'donationtester', 'adminuser'])
            ->get(['id', 'username', 'email']);

        $this->info('Utilisateurs de test disponibles:');
        $this->table(
            ['ID', 'Username', 'Email'],
            $users->map(function ($user) {
                return [
                    'ID' => $user->id,
                    'Username' => $user->username,
                    'Email' => $user->email,
                ];
            })
        );

        return Command::SUCCESS;
    }
}
