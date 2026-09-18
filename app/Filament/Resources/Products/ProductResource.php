<?php

namespace App\Filament\Resources\Products;

use App\Enums\ProductType;
use App\Enums\ProductVisibility;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\PricesRelationManager;
use App\Filament\Resources\Products\RelationManagers\VersionsRelationManager;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog Management';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('catalog.create_update') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Section::make('Core Product Information')
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                        if ($operation === 'create') {
                                            $set('slug', Str::slug($state));
                                        }
                                    }),

                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),

                                Textarea::make('summary')
                                    ->rows(3)
                                    ->placeholder('Short high-conversion elevator pitch for catalog cards.')
                                    ->required()
                                    ->columnSpanFull(),

                                RichEditor::make('description_html')
                                    ->label('Detailed Description')
                                    ->required()
                                    ->columnSpanFull(),

                                KeyValue::make('compatibility_metadata')
                                    ->label('Runtime & Framework Compatibility')
                                    ->keyLabel('Dependency / Environment')
                                    ->valueLabel('Supported Versions')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(2),

                        Section::make('Organization & Media')
                            ->schema([
                                Select::make('category_id')
                                    ->label('Catalog Category')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('product_type')
                                    ->options(ProductType::class)
                                    ->default(ProductType::Software)
                                    ->required(),

                                Select::make('visibility')
                                    ->options(ProductVisibility::class)
                                    ->default(ProductVisibility::Draft)
                                    ->required(),

                                Toggle::make('is_featured')
                                    ->label('Featured on Storefront')
                                    ->default(false),

                                FileUpload::make('thumbnail_path')
                                    ->label('Primary Thumbnail')
                                    ->disk('public')
                                    ->directory('products/thumbnails')
                                    ->image()
                                    ->imageCropAspectRatio('16:9')
                                    ->helperText('Main preview image displayed across storefront and admin tables.'),

                                Repeater::make('media')
                                    ->relationship('media')
                                    ->schema([
                                        FileUpload::make('file_path')
                                            ->label('Screenshot')
                                            ->disk('public')
                                            ->directory('products/gallery')
                                            ->image()
                                            ->required(),

                                        Toggle::make('is_thumbnail')
                                            ->label('Is Cover Image')
                                            ->default(false),
                                    ])
                                    ->collapsible()
                                    ->defaultItems(0)
                                    ->label('Marketing Gallery & Screenshots'),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail_path')
                    ->label('Thumbnail')
                    ->disk('public')
                    ->defaultImageUrl(fn ($record) => $record->thumbnail_url ?? null)
                    ->square()
                    ->size(48),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('visibility')
                    ->badge()
                    ->color(fn (ProductVisibility $state): string => match ($state) {
                        ProductVisibility::Published => 'success',
                        ProductVisibility::Draft => 'gray',
                        ProductVisibility::Unlisted => 'warning',
                        ProductVisibility::Archived => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('price_range')
                    ->label('Pricing')
                    ->weight('semibold'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),

                SelectFilter::make('visibility')
                    ->options(ProductVisibility::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['visibility' => ProductVisibility::Published])),

                    BulkAction::make('archive')
                        ->label('Archive Selected')
                        ->icon('heroicon-o-archive-box')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['visibility' => ProductVisibility::Archived])),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PricesRelationManager::class,
            VersionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
