<?php

namespace App\Filament\Admin\Resources\BlogPosts\Pages;

use App\Filament\Admin\Resources\BlogPosts\BlogPostResource;
use App\Filament\CrudDefaults;
use App\Models\BlogPost;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlogPost extends EditRecord
{
    //    use CrudDefaults;
    protected static string $resource = BlogPostResource::class;

    public static bool $formActionsAreSticky = true;

    protected function getHeaderActions(): array
    {
        return [
            // view the post
            Action::make('view')
                ->label(__('View Post'))
                ->color('success')
                ->url(
                    fn (BlogPost $resource) => route('blog.view', $resource->slug),
                    true
                )
                ->icon('heroicon-o-eye'),
            ActionGroup::make([
                DeleteAction::make(),
            ]),
        ];
    }

    protected function getFormActions(): array
    {
        return array_merge(parent::getFormActions(), [
            Action::make('view')
                ->label(__('View Post'))
                ->color('success')
                ->url(
                    fn (BlogPost $resource) => route('blog.view', $resource->slug),
                    true
                )
                ->icon('heroicon-o-eye'),
        ]);
    }
}
