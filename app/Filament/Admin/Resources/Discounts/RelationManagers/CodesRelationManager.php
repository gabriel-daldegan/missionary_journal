<?php

namespace App\Filament\Admin\Resources\Discounts\RelationManagers;

use App\Constants\DiscountConstants;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CodesRelationManager extends RelationManager
{
    protected static string $relationship = 'codes';

    protected static ?string $recordTitleAttribute = 'code';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    TextInput::make('code')
                        ->label(__('Code'))
                        ->helperText(__('The code that will be used to redeem the discount.'))
                        ->required()
                        ->unique()
                        ->maxLength(255),
                ])->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('Code'))
                    ->copyable()
                    ->copyableState(fn ($record) => config('app.url').'?'.DiscountConstants::COUPON_QUERY_PARAMETER.'='.urlencode($record->code))
                    ->copyMessage(__('URL Copied!')),
                TextColumn::make('redemptions_count')
                    ->label(__('Redemptions'))
                    ->counts('redemptions'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                Action::make(__('add_bulk_codes'))
                    ->label(__('New Bulk Codes'))
                    ->color('gray')
                    ->button()
                    ->schema([
                        TextInput::make('prefix')
                            ->helperText(__('The prefix will be added to the beginning of each code.'))
                            ->label(__('Prefix')),
                        TextInput::make('count')
                            ->label(__('Count'))
                            ->helperText(__('The number of codes to generate.'))
                            ->type('number')
                            ->integer()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                    ])
                    ->action(function (array $data, $livewire) {
                        $record = $livewire->getOwnerRecord();
                        $prefix = $data['prefix'] ?? '';
                        $count = $data['count'] ?? 1;

                        $codes = collect(range(1, $count))
                            ->map(fn () => $prefix.'-'.strtoupper(Str::random(8)))
                            ->map(fn ($code) => ['code' => $code]);

                        $record->codes()->createMany($codes);
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
