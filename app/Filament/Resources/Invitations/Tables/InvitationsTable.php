<?php

namespace App\Filament\Resources\Invitations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Tables\Table;

class InvitationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')
                    ->searchable()
                    ->copyable()
                    ->url(fn ($record) => route('invitation.show', $record->slug))
                    ->openUrlInNewTab(),
                TextColumn::make('template.name')
                    ->label('Template')
                    ->toggleable(),
                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'published',
                        'gray' => 'archived',
                    ]),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('public')
                    ->label('Public')
                    ->url(fn ($record) => route('invitation.show', $record->slug))
                    ->openUrlInNewTab(),
                Action::make('preview')
                    ->label('Preview')
                    ->url(fn ($record) => route('invitation.preview', $record->slug))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
