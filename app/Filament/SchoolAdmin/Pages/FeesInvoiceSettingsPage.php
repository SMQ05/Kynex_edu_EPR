<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Support\SchoolSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Branding/format options applied to generated fee invoices. Stored via
 * SchoolSettings (group 'fees') so the invoice/PDF generator can read them.
 */
class FeesInvoiceSettingsPage extends Page implements HasForms
{
    use HasPermissionCheck;
    use InteractsWithForms;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Fees';

    protected static ?int $navigationSort = 90;

    protected static ?string $navigationLabel = 'Invoice Settings';

    protected static ?string $title = 'Fees Invoice Settings';

    protected string $view = 'filament.school-admin.pages.settings-form';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'prefix'      => SchoolSettings::get('fees_invoice.prefix', 'INV-'),
            'next_number' => SchoolSettings::get('fees_invoice.next_number', 1),
            'show_logo'   => (bool) SchoolSettings::get('fees_invoice.show_logo', true),
            'footer_note' => SchoolSettings::get('fees_invoice.footer_note', 'Thank you for your payment.'),
            'terms'       => SchoolSettings::get('fees_invoice.terms', ''),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invoice Numbering')
                    ->columns(2)
                    ->schema([
                        TextInput::make('prefix')
                            ->label('Invoice number prefix')
                            ->maxLength(20)
                            ->placeholder('INV-'),
                        TextInput::make('next_number')
                            ->label('Next invoice number')
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                    ]),

                Section::make('Branding & Content')
                    ->schema([
                        Toggle::make('show_logo')->label('Show school logo on invoices')->default(true),
                        Textarea::make('footer_note')
                            ->label('Footer note')
                            ->rows(2)
                            ->maxLength(500),
                        Textarea::make('terms')
                            ->label('Terms & conditions')
                            ->rows(4)
                            ->maxLength(2000),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        SchoolSettings::setMany([
            'fees_invoice.prefix'      => $state['prefix'] ?? 'INV-',
            'fees_invoice.next_number' => (int) ($state['next_number'] ?? 1),
            'fees_invoice.show_logo'   => (bool) ($state['show_logo'] ?? true),
            'fees_invoice.footer_note' => $state['footer_note'] ?? '',
            'fees_invoice.terms'       => $state['terms'] ?? '',
        ], group: 'fees');

        Notification::make()->title('Invoice settings saved')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->label('Save Settings')->icon('heroicon-o-check')->color('success')->action('save'),
        ];
    }
}
