<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate($record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Handle category assignment if eBay category ID is provided
        if (isset($data['ebay_category_id']) && isset($data['ebay_category_name'])) {
            $categoryService = app(\App\Services\CategoryService::class);
            $category = $categoryService->assignCategoryToProduct(
                $record, 
                $data['ebay_category_id'], 
                $data['ebay_category_name']
            );
            $data['category_id'] = $category->id;
        }
        
        // Process specifics data
        $aspects = [];
        if (isset($data['specifics'])) {
            foreach ($data['specifics'] as $name => $value) {
                if (!empty($value)) {
                    $aspects[] = [
                        'name' => $name,
                        'value' => $value
                    ];
                }
            }
        }
        $data['aspects'] = $aspects;
        
        $record->update($data);
        return $record;
    }
}
