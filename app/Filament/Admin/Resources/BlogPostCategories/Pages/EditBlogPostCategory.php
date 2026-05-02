<?php

namespace App\Filament\Admin\Resources\BlogPostCategories\Pages;

use App\Filament\Admin\Resources\BlogPostCategories\BlogPostCategoryResource;
use App\Filament\CrudDefaults;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlogPostCategory extends EditRecord
{
    use CrudDefaults;

    protected static string $resource = BlogPostCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
