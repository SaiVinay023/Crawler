<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()->schema([
                    // FIXED: name instead of title
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('price')->numeric()->prefix('€')->required(),
                    Forms\Components\TextInput::make('category')->required(),
                    // FIXED: attributes.description because we store it in a JSON column
                    Forms\Components\Textarea::make('attributes.description')
                        ->label('Description')
                        ->rows(5)
                        ->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Requirement ✅: Show preview image in product list
                Tables\Columns\ImageColumn::make('images.url')
                    ->label('Preview')
                    ->circular()
                    ->stacked()
                    ->limit(1),
                
                // FIXED: name instead of title
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('price')->money('EUR')->sortable(),
                Tables\Columns\TextColumn::make('category')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(fn () => Product::whereNotNull('category')->distinct()->pluck('category', 'category')->toArray()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}