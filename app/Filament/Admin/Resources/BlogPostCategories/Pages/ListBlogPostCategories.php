<?php

namespace App\Filament\Admin\Resources\BlogPostCategories\Pages;

use App\Filament\Admin\Resources\BlogPostCategories\BlogPostCategoryResource;
use App\Filament\ListDefaults;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBlogPostCategories extends ListRecords
{
    use ListDefaults;

    protected static string $resource = BlogPostCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
