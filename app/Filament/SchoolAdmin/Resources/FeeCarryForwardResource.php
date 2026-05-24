<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\FeeCarryForwardResource\Pages;
use App\Models\Tenant\FeeCarryForward;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Fee Carry-Forward log. Each row records a student's unpaid balance moved
 * from one academic year into the current one (as a new StudentFee). New
 * carry-forwards are created via the "Carry forward outstanding" action on
 * the list page (which computes balances per student).
 */
class FeeCarryForwardResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'create_fee_structure';

    protected static ?string $model = FeeCarryForward::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-right-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Fees';

    protected static ?string $navigationLabel = 'Carry Forward';

    protected static ?int $navigationSort = 8;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->date('d M Y')->sortable(),
                TextColumn::make('student')
                    ->label('Student')
                    ->getStateUsing(fn (FeeCarryForward $r) => trim(($r->student->first_name ?? '') . ' ' . ($r->student->last_name ?? '')))
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('fromYear.name')->label('From year')->placeholder('—'),
                TextColumn::make('toYear.name')->label('To year')->placeholder('—'),
                TextColumn::make('amount_paisas')
                    ->label('Amount carried')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 2))
                    ->sortable(),
                TextColumn::make('note')->placeholder('—')->limit(40)->toggleable(),
            ])
            ->filters([
                SelectFilter::make('to_academic_year_id')
                    ->label('To year')
                    ->relationship('toYear', 'name'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeeCarryForwards::route('/'),
        ];
    }
}
