<?php

namespace App\Filament\Resources;

use App\Models\Credential;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Session;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextInputColumn;

class CredentialResource extends Resource
{
    protected static ?string $model = Credential::class;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'eBay Stores';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('environment')->disabled(),
            Forms\Components\TextInput::make('refresh_token')->disabled(),
            Forms\Components\TextInput::make('access_token')->disabled(),
            Forms\Components\Toggle::make('is_active')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('store_name')
                    ->label('Store Name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active Store'),
            ])
            ->actions([
                Action::make('editStoreName')
                    ->label('Edit Store Name')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('store_name')
                            ->label('Store Name')
                            ->required(),
                    ])
                    ->action(function ($record, $data) {
                        $record->store_name = $data['store_name'];
                        $record->save();
                    }),
                Action::make('setActive')
                    ->label('Set as Active')
                    ->visible(fn ($record) => !$record->is_active)
                    ->action(fn ($record) => \App\Models\Credential::setActiveStore($record->id)),
            ])
            ->headerActions([
                Action::make('toggleEnv')
                    ->label(fn () => Session::get('ebay_env', 'production') === 'sandbox' ? 'Switch to Production' : 'Switch to Sandbox')
                    ->action(function () {
                        $current = Session::get('ebay_env', 'production');
                        Session::put('ebay_env', $current === 'sandbox' ? 'production' : 'sandbox');
                        Notification::make()
                            ->title('Environment switched!')
                            ->success()
                            ->body('Please refresh the page to see the changes.')
                            ->send();
                    }),
                Action::make('authorizeNewStore')
                    ->label('Authorize New Store')
                    ->url(route('ebay.oauth.start')), // You may need to adjust this route
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\CredentialResource\Pages\ListCredentials::route('/'),
        ];
    }
} 