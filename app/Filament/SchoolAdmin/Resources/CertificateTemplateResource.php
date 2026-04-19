<?php

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\CertificateTemplateResource\Pages;
use App\Models\Tenant\CertificateTemplate;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CertificateTemplateResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'issue_certificates';

    protected static ?string $model = CertificateTemplate::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-check';

    protected static string | \UnitEnum | null $navigationGroup = 'Certificates & ID Cards';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Select::make('template_type')
                            ->options([
                                'leaving'     => 'Leaving Certificate',
                                'character'   => 'Character Certificate',
                                'completion'  => 'Completion Certificate',
                                'achievement' => 'Achievement Certificate',
                                'custom'      => 'Custom',
                            ])
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(3),

                Section::make('HTML Template')
                    ->description('Use placeholders like {{student_name}}, {{class_name}}, {{certificate_number}}, {{issued_date}}, etc.')
                    ->schema([
                        Textarea::make('html_template')
                            ->required()
                            ->rows(20)
                            ->columnSpanFull()
                            ->helperText('Available: {{student_name}}, {{first_name}}, {{last_name}}, {{admission_number}}, {{class_name}}, {{section_name}}, {{academic_year}}, {{father_name}}, {{mother_name}}, {{date_of_birth}}, {{certificate_number}}, {{issued_date}}, {{school_name}}, {{current_date}}'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('template_type')
                    ->badge()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                TextColumn::make('certificates_count')
                    ->counts('certificates')
                    ->label('Certificates Issued')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('template_type')
                    ->options([
                        'leaving'     => 'Leaving',
                        'character'   => 'Character',
                        'completion'  => 'Completion',
                        'achievement' => 'Achievement',
                        'custom'      => 'Custom',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificateTemplates::route('/'),
            'create' => Pages\CreateCertificateTemplate::route('/create'),
            'edit' => Pages\EditCertificateTemplate::route('/{record}/edit'),
        ];
    }
}
