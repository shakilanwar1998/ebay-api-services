<?php

namespace App\Filament\Imports;

use App\Models\Product;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import as ImportModel;

class CustomProductImporter extends Importer
{
    public static function getModel(): string
    {
        return Product::class;
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('primary_category')->requiredMapping(),
            ImportColumn::make('brand')->requiredMapping(),
            ImportColumn::make('model')->requiredMapping(),
            ImportColumn::make('description')->requiredMapping(),
            ImportColumn::make('images')->requiredMapping(),
        ];
    }

    public function resolveRecord(): ?\App\Models\Product
    {
        $this->data['title'] = trim(($this->data['primary_category'] ?? '') . ' ' . ($this->data['brand'] ?? '') . ' ' . ($this->data['model'] ?? ''));
        $this->data['images'] = array_map('trim', explode(',', $this->data['images'] ?? ''));

        return new \App\Models\Product([
            'title' => $this->data['title'],
            'description' => $this->data['description'] ?? null,
            'primary_category' => $this->data['primary_category'] ?? null,
            'brand' => $this->data['brand'] ?? null,
            'model' => $this->data['model'] ?? null,
            'images' => $this->data['images'] ?? null,
        ]);
    }

    public static function getCompletedNotificationBody(ImportModel $import): string
    {
        $successful = $import->successful_rows_count;
        $failed = $import->failed_rows_count;
        return "Imported {$successful} products successfully." . ($failed ? " {$failed} failed." : "");
    }
}
