<?php

namespace App\Filament\Admin\Resources\BlogPosts;

use App\Filament\Admin\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Filament\Admin\Resources\BlogPosts\Pages\EditBlogPost;
use App\Filament\Admin\Resources\BlogPosts\Pages\ListBlogPosts;
use App\Models\BlogPost;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('Blog');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Blog Posts');
    }

    public static function getModelLabel(): string
    {
        return __('Blog Post');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    TextInput::make('title')
                        ->required()
                        ->label(__('Title'))
                        ->maxLength(1000),
                    Textarea::make('description')
                        ->maxLength(1000)
                        ->helperText(__('A short description of the post (will be used in meta tags).'))
                        ->label('Description')
                        ->rows(2),
                    RichEditor::make('body')
                        ->columns(10)
                        ->required()
                        ->label(__('Content'))
                        ->floatingToolbars([
                            'paragraph' => [
                                'bold', 'italic', 'underline', 'strike', 'subscript', 'superscript',
                            ],
                            'heading' => [
                                'h1', 'h2', 'h3',
                            ],
                            'table' => [
                                'tableAddColumnBefore', 'tableAddColumnAfter', 'tableDeleteColumn',
                                'tableAddRowBefore', 'tableAddRowAfter', 'tableDeleteRow',
                                'tableMergeCells', 'tableSplitCell',
                                'tableToggleHeaderRow',
                                'tableDelete',
                            ],
                        ])
                        ->fileAttachmentsDirectory('blog-images')
                        ->columnSpanFull(),
                ])->columnSpan(2),
                Section::make([
                    TextInput::make('slug')
                        ->label(__('Slug'))
                        ->helperText(__('Will be used in the URL of the post. Leave empty to generate slug automatically from title.'))
                        ->dehydrateStateUsing(function ($state, Get $get) {
                            if (empty($state)) {
                                $title = $get('title');

                                return Str::slug($title);
                            }

                            return Str::slug($state);
                        })
                        ->maxLength(255),
                    Select::make('blog_post_category_id')
                        ->relationship('blogPostCategory', 'name'),
                    Select::make('author_id')
                        ->label(__('Author'))
                        ->default(auth()->user()->id)
                        ->required()
                        ->options(
                            User::admin()->get()->sortBy('name')
                                ->mapWithKeys(function ($user) {
                                    return [$user->id => $user->getPublicName()];
                                })
                                ->toArray()
                        ),
                    SpatieMediaLibraryFileUpload::make('image')
                        ->collection('blog-images')
                        ->label(__('Images'))
                        ->acceptedFileTypes(['image/webp', 'image/jpeg', 'image/png']),
                    Toggle::make('is_published')
                        ->label(__('Is Published'))
                        ->required(),
                    DateTimePicker::make('published_at')
                        ->label(__('Published At'))
                        ->required(function ($state, Get $get) {
                            return $get('is_published');
                        }),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable(),
                TextColumn::make('author_id')
                    ->label(__('Author'))
                    ->formatStateUsing(function ($state, $record) {
                        return $record->author->getPublicName();
                    })
                    ->searchable(),
                IconColumn::make('is_published')
                    ->label(__('Published'))
                    ->boolean(),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'author',
                'user',
            ]))
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlogPosts::route('/'),
            'create' => CreateBlogPost::route('/create'),
            'edit' => EditBlogPost::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('Blog Posts');
    }
}
