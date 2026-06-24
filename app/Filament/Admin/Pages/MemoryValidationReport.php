<?php

namespace App\Filament\Admin\Pages;

use App\Models\User;
use App\Services\MemoryValidationReportService;
use Filament\Pages\Page;

class MemoryValidationReport extends Page
{
    protected string $view = 'filament.admin.pages.memory-validation-report';

    /**
     * @var array<string, mixed>
     */
    public array $report = [];

    public function mount(MemoryValidationReportService $reportService): void
    {
        abort_unless(static::canAccess(), 403);

        $this->report = $reportService->report();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Product Management');
    }

    public static function getNavigationLabel(): string
    {
        return __('Validation Report');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin();
    }
}
