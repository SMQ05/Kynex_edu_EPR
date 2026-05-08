<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources;

use App\Filament\SaasAdmin\Resources\AuditFindingResource\Pages;
use App\Models\AuditFinding;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AuditFindingResource extends Resource
{
    protected static ?string $model = AuditFinding::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Findings';

    protected static ?string $modelLabel = 'Finding';

    protected static ?string $pluralModelLabel = 'Findings';

    protected static string|\UnitEnum|null $navigationGroup = 'Audit';

    protected static ?int $navigationSort = 2;

    // Read-only — findings are written only by the audit:run command.
    public static function canCreate(): bool { return false; }

    // ── View schema ────────────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Finding Details')
                ->columns(2)
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('severity')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'critical' => 'danger',
                            'high'     => 'warning',
                            'medium'   => 'primary',
                            default    => 'gray',
                        }),
                    \Filament\Infolists\Components\TextEntry::make('finding_type')->badge(),
                    \Filament\Infolists\Components\TextEntry::make('tenant_slug')->label('Tenant'),
                    \Filament\Infolists\Components\TextEntry::make('role')->badge(),
                    \Filament\Infolists\Components\TextEntry::make('url')
                        ->columnSpanFull()
                        ->copyable(),
                    \Filament\Infolists\Components\TextEntry::make('title')->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('http_status')->label('HTTP Status'),
                    \Filament\Infolists\Components\TextEntry::make('detected_at')->dateTime(),
                    \Filament\Infolists\Components\TextEntry::make('fixed_at')
                        ->dateTime()
                        ->placeholder('Not fixed'),
                    \Filament\Infolists\Components\TextEntry::make('fixed_by_commit')
                        ->label('Fixed in commit')
                        ->placeholder('—'),
                ]),

            Section::make('Details JSON')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('details')
                        ->label('')
                        ->formatStateUsing(fn ($state) =>
                            json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                        ->columnSpanFull(),
                ]),

            Section::make('Screenshot')
                ->hidden(fn ($record) => ! $record->screenshot_path)
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('screenshot_path')
                        ->label('File path')
                        ->copyable(),
                ]),
        ]);
    }

    // ── Table ──────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('severity')
                    ->colors([
                        'danger'  => 'critical',
                        'warning' => 'high',
                        'primary' => 'medium',
                        'gray'    => ['low', 'info', 'ok'],
                    ]),

                Tables\Columns\TextColumn::make('finding_type')
                    ->label('Type')
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('tenant_slug')
                    ->label('Tenant')
                    ->searchable(),

                Tables\Columns\TextColumn::make('role')
                    ->badge(),

                Tables\Columns\TextColumn::make('url')
                    ->limit(50)
                    ->tooltip(fn ($state) => $state)
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->limit(80)
                    ->tooltip(fn ($state) => $state)
                    ->searchable(),

                Tables\Columns\TextColumn::make('http_status')
                    ->label('HTTP')
                    ->placeholder('—')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 500 => 'danger',
                        $state >= 400 => 'warning',
                        default       => 'gray',
                    }),

                Tables\Columns\TextColumn::make('detected_at')
                    ->dateTime('M j, H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fixed_at')
                    ->dateTime('M j, H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('detected_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('severity')
                    ->options([
                        'critical' => 'Critical',
                        'high'     => 'High',
                        'medium'   => 'Medium',
                        'low'      => 'Low',
                        'info'     => 'Info',
                        'ok'       => 'OK',
                    ]),

                Tables\Filters\SelectFilter::make('finding_type')
                    ->label('Type')
                    ->options([
                        '5xx'                    => '5xx Server Error',
                        '4xx'                    => '4xx Client Error',
                        'js_error'               => 'JS Error',
                        'network_failure'        => 'Network Failure',
                        'broken_image'           => 'Broken Image',
                        'malformed_image_url'    => 'Malformed Image URL',
                        'empty_image_src'        => 'Empty Image Src',
                        'filament_error_toast'   => 'Filament Error Toast',
                        'form_500_on_validation' => 'Form 500 / Silent Redirect',
                        'slow_page'              => 'Slow Page',
                        'blank_page'             => 'Blank Page',
                        'panel_chrome_missing'   => 'Panel Chrome Missing',
                        'security_violation'     => 'Security Violation',
                        'access_denied_ok'       => 'Access Denied (OK)',
                    ]),

                Tables\Filters\SelectFilter::make('tenant_slug')
                    ->label('Tenant')
                    ->options([
                        'haji-qamar-public-school-BEb3S9' => 'Haji Qamar (aqmdigital.com)',
                        'saas'                            => 'SaaS Admin',
                    ]),

                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'SCHOOL_ADMIN'   => 'School Admin',
                        'INSTITUTE_HEAD' => 'Institute Head',
                        'REGISTRAR'      => 'Registrar',
                        'ACCOUNTANT'     => 'Accountant',
                        'TEACHER'        => 'Teacher',
                        'PARENT'         => 'Parent',
                        'STUDENT'        => 'Student',
                        'saas_admin'     => 'SaaS Admin',
                    ]),

                // Date range filter for detected_at
                Tables\Filters\Filter::make('detected_at')
                    ->form([
                        DatePicker::make('from')->label('Detected from'),
                        DatePicker::make('until')->label('Detected until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'],  fn ($q) => $q->whereDate('detected_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('detected_at', '<=', $data['until']));
                    }),

                // Fixed / unfixed toggle
                Tables\Filters\TernaryFilter::make('fixed_at')
                    ->label('Fixed')
                    ->nullable()
                    ->trueLabel('Fixed only')
                    ->falseLabel('Unfixed only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // "Mark as fixed" (sets fixed_at + fixed_by_commit) — Phase 2B follow-up
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditFindings::route('/'),
            'view'  => Pages\ViewAuditFinding::route('/{record}'),
        ];
    }
}
