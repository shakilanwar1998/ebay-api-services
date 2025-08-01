<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Actions\Imports\Importer;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('category_search')
                    ->label('Search eBay Category')
                    ->placeholder('Enter product title to search categories...')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state) {
                            $suggestions = app(\App\Services\CategoryService::class)->getCategorySuggestions($state);
                            $set('category_suggestions', $suggestions);
                        }
                    }),
                Forms\Components\Select::make('category_id')
                    ->label('eBay Category')
                    ->options(function ($get) {
                        $suggestions = $get('category_suggestions') ?? [];
                        $options = [];
                        foreach ($suggestions as $suggestion) {
                            $options[$suggestion['category']['categoryId']] = $suggestion['category']['categoryName'];
                        }
                        return $options;
                    })
                    ->getOptionLabelUsing(fn ($value) => \App\Models\Category::find($value)?->name ?? $value)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, $set, $get) {
                        if ($state) {
                            // Get the category name from the suggestions
                            $suggestions = $get('category_suggestions') ?? [];
                            $categoryName = '';
                            foreach ($suggestions as $suggestion) {
                                if ($suggestion['category']['categoryId'] == $state) {
                                    $categoryName = $suggestion['category']['categoryName'];
                                    break;
                                }
                            }
                            
                            // Store the eBay category ID and name for later processing
                            $set('ebay_category_id', $state);
                            $set('ebay_category_name', $categoryName);
                        }
                    }),
                Forms\Components\Section::make('Category Specifics')
                    ->schema(function ($get) {
                        $categoryId = $get('category_id');
                        if (!$categoryId) return [];
                        $category = \App\Models\Category::find($categoryId);
                        if (!$category || empty($category->specifics)) return [];
                        return collect($category->specifics)
                            ->map(function ($aspect) {
                                if (!empty($aspect['values'])) {
                                    return Forms\Components\Select::make('specifics.' . $aspect['name'])
                                        ->label($aspect['name'])
                                        ->options(collect($aspect['values'])->pluck('value', 'value')->toArray())
                                        ->required($aspect['required'] ?? false);
                                } else {
                                    return Forms\Components\TextInput::make('specifics.' . $aspect['name'])
                                        ->label($aspect['name'])
                                        ->required($aspect['required'] ?? false);
                                }
                            })->toArray();
                    })
                    ->visible(function ($get) {
                        return !empty($get('category_id'));
                    }),
                Forms\Components\TextInput::make('brand')
                    ->maxLength(255),
                Forms\Components\TextInput::make('model')
                    ->maxLength(255),
                Forms\Components\FileUpload::make('images')
                    ->multiple()
                    ->directory('products')
                    ->image()
                    ->imageEditor()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('images')
                    ->label('Image')
                    ->size(50)
                    ->getStateUsing(function ($record) {
                        $images = $record->images;
                        if (is_array($images) && !empty($images)) {
                            $imagePath = 'storage/products/' . $images[0];
                            if (file_exists(public_path($imagePath))) {
                                return url($imagePath);
                            }
                            return null;
                        }
                        return null;
                    }),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('eBay Category')
                    ->searchable(),
                Tables\Columns\TextColumn::make('aspects')
                    ->label('Aspects')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'No aspects';
                        return collect($state)->map(function ($aspect) {
                            return $aspect['name'] . ': ' . $aspect['value'];
                        })->join(', ');
                    })
                    ->wrap(),
                Tables\Columns\TextColumn::make('brand')
                    ->searchable(),
                Tables\Columns\TextColumn::make('model')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('selectCategory')
                    ->label('Select Category')
                    ->icon('heroicon-o-tag')
                    ->action(function ($record) {
                        if ($record->title) {
                            $categories = (new \App\Services\CategoryService)->getCategorySuggestions($record->title);
                            if (!empty($categories)) {
                                $categoryId = $categories[0]['category']['categoryId'];
                                $categoryName = $categories[0]['category']['categoryName'];
                                
                                // Use the assignCategoryToProduct method to properly create/update the category
                                app(\App\Services\CategoryService::class)
                                    ->assignCategoryToProduct($record, $categoryId, $categoryName);
                            }
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
