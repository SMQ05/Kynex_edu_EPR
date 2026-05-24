<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Support;

use App\Services\Ai\AiAvailability;
use App\Services\Ai\AiDraftService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

/**
 * Reusable Filament "✨ AI" field actions. Attach to any text/textarea/
 * rich-editor field so admins can generate, refine, or translate the
 * field's content. Advisory-first: the result is inserted into the form
 * for a human to review before saving.
 *
 * Usage:
 *   Textarea::make('body')
 *       ->hintActions([
 *           AiActions::draftInto('body', [
 *               'instruction'   => 'a school notice about the upcoming sports day',
 *               'contextFields' => ['title' => 'Title'],
 *               'feature'       => 'draft_notice',
 *               'channel'       => 'notice',
 *           ]),
 *           AiActions::refineInto('body'),
 *       ])
 *
 * Buttons auto-hide when AI is disabled / out of budget for the tenant.
 */
class AiActions
{
    /**
     * "Draft with AI" — generate fresh content into $targetField.
     *
     * @param  array{instruction?:string,contextFields?:array<string,string>,feature?:string,channel?:string,format?:string}  $opts
     */
    public static function draftInto(string $targetField, array $opts = []): Action
    {
        $defaultInstruction = (string) ($opts['instruction'] ?? '');
        $contextFields      = $opts['contextFields'] ?? [];
        $feature            = (string) ($opts['feature'] ?? 'ai_draft');
        $channel            = $opts['channel'] ?? null;
        $format             = $opts['format'] ?? null;

        return Action::make('aiDraft_' . self::slug($targetField))
            ->label('Draft with AI')
            ->icon('heroicon-o-sparkles')
            ->color('primary')
            ->visible(fn (): bool => AiAvailability::enabled())
            ->modalHeading('Draft with AI')
            ->modalSubmitActionLabel('Generate')
            ->modalWidth('xl')
            ->form([
                Textarea::make('instruction')
                    ->label('What should it say?')
                    ->placeholder('e.g. a friendly reminder that fees are due on the 10th')
                    ->default($defaultInstruction)
                    ->rows(3)
                    ->required(),
                Select::make('tone')
                    ->label('Tone')
                    ->options(self::toneOptions())
                    ->default('professional and warm')
                    ->native(false),
                Select::make('language')
                    ->label('Language')
                    ->options(self::languageOptions())
                    ->default('English')
                    ->native(false),
            ])
            ->action(function (array $data, Get $get, Set $set) use ($targetField, $contextFields, $feature, $channel, $format) {
                $context = [];
                foreach ($contextFields as $field => $label) {
                    $val = $get($field);
                    if ($val !== null && $val !== '') {
                        $context[$label] = is_scalar($val) ? (string) $val : json_encode($val);
                    }
                }

                try {
                    $text = app(AiDraftService::class)->draft(
                        instruction: (string) $data['instruction'],
                        context: $context,
                        feature: $feature,
                        options: [
                            'tone'     => $data['tone'] ?? null,
                            'language' => $data['language'] ?? 'English',
                            'channel'  => $channel,
                            'format'   => $format,
                        ],
                    );

                    $set($targetField, $text);

                    Notification::make()
                        ->title('Draft inserted')
                        ->body('Review and edit before saving.')
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('AI draft failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * "Improve with AI" — rewrite the field's current content for clarity,
     * grammar and tone without changing meaning.
     */
    public static function refineInto(string $targetField, array $opts = []): Action
    {
        $feature = (string) ($opts['feature'] ?? 'ai_refine');

        return Action::make('aiRefine_' . self::slug($targetField))
            ->label('Improve with AI')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->visible(fn (): bool => AiAvailability::enabled())
            ->modalHeading('Improve with AI')
            ->modalSubmitActionLabel('Improve')
            ->modalWidth('lg')
            ->form([
                Select::make('instruction')
                    ->label('How should I improve it?')
                    ->options([
                        'Fix grammar and spelling, keep wording close to the original' => 'Fix grammar & spelling',
                        'Make it clearer and more concise'                              => 'Make it clearer & shorter',
                        'Make it more formal and professional'                          => 'More formal',
                        'Make it warmer and friendlier'                                 => 'Warmer & friendlier',
                    ])
                    ->default('Fix grammar and spelling, keep wording close to the original')
                    ->native(false)
                    ->required(),
                Select::make('language')
                    ->label('Language')
                    ->options(self::languageOptions())
                    ->default('English')
                    ->native(false),
            ])
            ->action(function (array $data, Get $get, Set $set) use ($targetField, $feature) {
                $current = (string) ($get($targetField) ?? '');
                if (trim($current) === '') {
                    Notification::make()->title('Nothing to improve')->body('Write something first.')->warning()->send();

                    return;
                }

                try {
                    $text = app(AiDraftService::class)->refine(
                        text: $current,
                        instruction: (string) $data['instruction'],
                        feature: $feature,
                        options: ['language' => $data['language'] ?? 'English'],
                    );
                    $set($targetField, $text);
                    Notification::make()->title('Updated — review before saving')->success()->send();
                } catch (\Throwable $e) {
                    Notification::make()->title('AI improve failed')->body($e->getMessage())->danger()->send();
                }
            });
    }

    /** @return array<string,string> */
    private static function toneOptions(): array
    {
        return [
            'professional and warm' => 'Professional & warm',
            'formal and official'   => 'Formal & official',
            'friendly and casual'   => 'Friendly & casual',
            'firm but polite'       => 'Firm but polite',
            'encouraging'           => 'Encouraging',
        ];
    }

    /** @return array<string,string> */
    private static function languageOptions(): array
    {
        return [
            'English'        => 'English',
            'Urdu'           => 'Urdu (اردو)',
            'Roman Urdu'     => 'Roman Urdu',
            'Arabic'         => 'Arabic (العربية)',
        ];
    }

    private static function slug(string $field): string
    {
        return preg_replace('/[^A-Za-z0-9]+/', '_', $field) ?? 'field';
    }
}
