<?php

namespace App\Filament\Resources\CredentialResource\Pages;

use App\Filament\Resources\CredentialResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Session;
use Illuminate\Database\Eloquent\Builder;

class ListCredentials extends ListRecords
{
    protected static string $resource = CredentialResource::class;

    protected function getTableQuery(): Builder
    {
        $env = Session::get('ebay_env', 'production');
        return parent::getTableQuery()->where('environment', $env);
    }
} 