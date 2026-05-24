<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\CmsTestimonialResource\Pages;
use App\Filament\SchoolAdmin\Support\AiActions;
use App\Models\Tenant\CmsTestimonial;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CmsTestimonialResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static ?string $model = CmsTestimonial::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = 'Website CMS';

    protected static ?string $navigationLabel = 'Testimonials';

    protected static ?int $navigationSort = 21;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Person')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('role')
                        ->label('Role / Relationship')
                        ->maxLength(255)
                        ->placeholder('Parent / Alumnus / Teacher'),
                    FileUpload::make('photo_path')
                        ->label('Photo')
                        ->image()
                        ->directory('cms/testimonials')
                        ->maxSize(1024),
                    Select::make('rating')
                        ->options([1 => '1 ★', 2 => '2 ★', 3 => '3 ★', 4 => '4 ★', 5 => '5 ★'])
                        ->native(false)
                        ->placeholder('No rating'),
                ]),

            Section::make('Testimonial')
                ->schema([
                    Textarea::make('quote')
                        ->required()
                        ->rows(4)
                        ->maxLength(1000)
                        ->columnSpanFull()
                        ->hintActions([
                            AiActions::draftInto('quote', [
                                'instruction'   => 'a warm, authentic-sounding short testimonial from this person praising the school',
                                'contextFields' => ['name' => 'Person', 'role' => 'Role'],
                                'feature'       => 'cms_testimonial_draft',
                            ]),
                            AiActions::refineInto('quote', ['feature' => 'cms_testimonial_refine']),
                        ]),
                    TextInput::make('sort')->numeric()->default(0)->label('Sort order'),
                    Toggle::make('is_active')->label('Active')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')->label('')->circular(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('role')->placeholder('—')->toggleable(),
                TextColumn::make('quote')->limit(60)->wrap(),
                TextColumn::make('rating')
                    ->formatStateUsing(fn (?int $state): string => $state ? str_repeat('★', $state) : '—')
                    ->color('warning'),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('sort')->sortable()->toggleable(),
            ])
            ->defaultSort('sort')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCmsTestimonials::route('/'),
            'create' => Pages\CreateCmsTestimonial::route('/create'),
            'edit'   => Pages\EditCmsTestimonial::route('/{record}/edit'),
        ];
    }
}
