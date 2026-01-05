<?php

namespace App\Filament\Dashboard\Pages;

use Filament\Pages\Page;

class CreateWorkspace extends Page
{
    protected string $view = 'filament.dashboard.pages.create-workspace';

    protected static ?string $slug = 'create-workspace';

    public function mount(): void
    {
        if (! config('app.allow_user_to_create_tenants_from_dashboard', false)) {
            abort(403);
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function getHeading(): string
    {
        return __('Create New Workspace');
    }
}
