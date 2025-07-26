<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\ImportAction;
use App\Filament\Imports\ProductImporter;
use App\Filament\Imports\CustomProductImporter;
use Filament\Forms;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ImportAction::make()
                ->importer(CustomProductImporter::class),
            Actions\Action::make('uploadImages')
                ->label('Upload Images')
                ->icon('heroicon-o-photo')
                ->form([
                    Forms\Components\FileUpload::make('images')
                        ->label('Select Images')
                        ->multiple()
                        ->directory('products')
                        ->image()
                        ->preserveFilenames()
                        ->required()
                        ->helperText('Upload images to local storage. Files will keep their original names.')
                ])
                ->action(function (array $data): void {
                    $uploadedFiles = $data['images'] ?? [];
                    $existingFiles = [];
                    $newFiles = [];
                    
                    foreach ($uploadedFiles as $filename) {
                        // $filename is already a string (the filename) when using preserveFilenames()
                        $filePath = public_path('storage/products/' . $filename);
                        
                        if (file_exists($filePath)) {
                            $existingFiles[] = $filename;
                        } else {
                            $newFiles[] = $filename;
                        }
                    }
                    
                    // Show warning if files already exist
                    if (!empty($existingFiles)) {
                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('Some files already exist')
                            ->body('The following files already exist and were not uploaded: ' . implode(', ', $existingFiles))
                            ->send();
                    }
                    
                    // Show success for new files
                    if (!empty($newFiles)) {
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Files uploaded successfully')
                            ->body('The following files were uploaded: ' . implode(', ', $newFiles))
                            ->send();
                    }
                })
                ->successNotification(
                    notification: \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Images uploaded successfully')
                        ->body('Images have been uploaded to local storage with their original filenames.')
                ),
        ];
    }
}
