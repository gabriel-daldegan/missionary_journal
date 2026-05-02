<?php

namespace App\Filament\Admin\Resources\OneTimeProducts\Pages;

use App\Filament\Admin\Resources\OneTimeProducts\OneTimeProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOneTimeProduct extends CreateRecord
{
    protected static string $resource = OneTimeProductResource::class;
}
