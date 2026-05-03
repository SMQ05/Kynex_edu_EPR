<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets\FeeReports;

use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\FeePayment;
use App\Models\Tenant\StudentFee;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Top-row KPIs for the Fee Reports page: billed, collected, collection
 * rate, average days-to-pay. Scoped to the current academic year.
 */
class FeeKpiWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $yearId = AcademicYear::query()->where('is_current', true)->value('id');

        $billed = (int) StudentFee::query()
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->sum('amount_paisas');
        $collected = (int) StudentFee::query()
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->sum('paid_paisas');
        $fines = (int) StudentFee::query()
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->sum('fine_paisas');
        $discounts = (int) StudentFee::query()
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->sum('discount_paisas');
        $outstanding = max(0, $billed + $fines - $collected - $discounts);

        $rate = $billed > 0 ? round($collected * 100 / $billed, 1) : 0.0;

        // Average days to pay: across all paid fees in this year, the
        // mean of (paid_at - due_date). Negative = paid before due.
        // Postgres date subtraction returns INTEGER days directly,
        // not an interval — extract(epoch from int) is invalid here.
        $avgDays = (int) round((float) FeePayment::query()
            ->join('fee_payment_items', 'fee_payment_items.payment_id', '=', 'fee_payments.id')
            ->join('student_fees', 'student_fees.id', '=', 'fee_payment_items.student_fee_id')
            ->when($yearId, fn ($q) => $q->where('student_fees.academic_year_id', $yearId))
            ->selectRaw('avg(fee_payments.payment_date - student_fees.due_date) as d')
            ->value('d') ?? 0);

        $rateColor = $rate >= 80 ? 'success' : ($rate >= 60 ? 'warning' : 'danger');

        return [
            Stat::make('Billed (year)', 'PKR ' . number_format($billed / 100, 2))
                ->description('Total invoices issued')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('primary'),

            Stat::make('Collected', 'PKR ' . number_format($collected / 100, 2))
                ->description('Payments received')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Outstanding', 'PKR ' . number_format($outstanding / 100, 2))
                ->description('Pending + partial + fines')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),

            Stat::make('Collection rate', $rate . '%')
                ->description($avgDays === 0
                    ? 'avg days to pay: n/a'
                    : ($avgDays > 0 ? "avg paid {$avgDays}d after due" : 'avg paid ' . abs($avgDays) . 'd before due'))
                ->descriptionIcon($avgDays > 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-arrow-trending-up')
                ->color($rateColor),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
