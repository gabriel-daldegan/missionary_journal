<?php

namespace App\Filament\Dashboard\Resources\Roles\Pages;

use App\Filament\CrudDefaults;
use App\Filament\Dashboard\Resources\Roles\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditRole extends EditRecord
{
    use CrudDefaults;

    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(function () {
                    $models = DB::table(config('permission.table_names.model_has_roles'))
                        ->where('role_id', $this->record->id)
                        ->get();

                    return $models->count() > 0;
                }),
        ];
    }
}
