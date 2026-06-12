<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserAudioProgressResource\Pages;
use App\Models\UserAudioProgress;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class UserAudioProgressResource extends Resource
{
    protected static ?string $model = UserAudioProgress::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string | UnitEnum | null $navigationGroup = 'Áudio';

    protected static ?string $navigationLabel = 'Progresso de áudio';

    protected static ?string $modelLabel = 'progresso de áudio';

    protected static ?string $pluralModelLabel = 'progressos de áudio';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.email')->label('Usuário')->searchable(),
                TextColumn::make('audioTrack.title')->label('Áudio')->searchable(),
                TextColumn::make('progress_seconds')->label('Segundos'),
                IconColumn::make('completed')->label('Concluído')->boolean(),
                TextColumn::make('last_played_at')->label('Última reprodução')->dateTime('d/m/Y H:i'),
            ])
            ->defaultSort('last_played_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserAudioProgress::route('/'),
        ];
    }
}
