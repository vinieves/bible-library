<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Plan;
use App\Models\User;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            $this->bulkCreateAction(),
        ];
    }

    protected function bulkCreateAction(): Action
    {
        return Action::make('bulkCreate')
            ->label('Criar em massa')
            ->icon('heroicon-o-user-plus')
            ->color('gray')
            ->modalHeading('Criar usuários em massa')
            ->modalSubmitActionLabel('Criar usuários')
            ->form([
                Textarea::make('emails')
                    ->label('E-mails')
                    ->helperText('Cole um e-mail por linha. Duplicados e inválidos são ignorados.')
                    ->rows(12)
                    ->required(),
                Select::make('plans')
                    ->label('Planos a atribuir a todos')
                    ->options(fn () => Plan::query()->orderBy('name')->pluck('name', 'id'))
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ])
            ->action(fn (array $data) => $this->handleBulkCreate($data));
    }

    protected function handleBulkCreate(array $data): void
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) ($data['emails'] ?? ''));

        $valid = [];
        $invalid = 0;

        foreach ($lines as $line) {
            $email = strtolower(trim((string) $line));

            if ($email === '') {
                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalid++;

                continue;
            }

            $valid[$email] = true;
        }

        $emails = array_keys($valid);

        $existing = User::query()
            ->whereIn('email', $emails)
            ->pluck('email')
            ->map(fn (string $email) => strtolower($email))
            ->all();

        $toCreate = array_diff($emails, $existing);
        $planIds = array_filter((array) ($data['plans'] ?? []));

        DB::transaction(function () use ($toCreate, $planIds) {
            foreach ($toCreate as $email) {
                $name = Str::before($email, '@');

                $user = User::query()->create([
                    'name' => filled($name) ? $name : $email,
                    'email' => $email,
                    'password' => Hash::make(Str::random(48)),
                    'email_verified_at' => now(),
                ]);

                if ($planIds !== []) {
                    $pivot = [];

                    foreach ($planIds as $planId) {
                        $pivot[$planId] = [
                            'granted_by' => 'admin_bulk',
                            'expires_at' => null,
                        ];
                    }

                    $user->plans()->attach($pivot);
                }
            }
        });

        $created = count($toCreate);
        $duplicates = count($existing);

        Notification::make()
            ->title('Criação em massa concluída')
            ->body("{$created} criados · {$duplicates} já existiam · {$invalid} inválidos")
            ->success()
            ->send();
    }
}
