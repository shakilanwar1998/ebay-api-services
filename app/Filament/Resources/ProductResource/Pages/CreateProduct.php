<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Category;
use App\Services\CategoryService;
use Filament\Notifications\Notification;
use Filament\Forms;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Handle category assignment if eBay category ID is provided
        if (isset($data['ebay_category_id']) && isset($data['ebay_category_name'])) {
            $categoryService = app(CategoryService::class);
            $category = $categoryService->assignCategoryToProduct(
                new \App\Models\Product(), 
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
        
        return static::getModel()::create($data);
    }
}
