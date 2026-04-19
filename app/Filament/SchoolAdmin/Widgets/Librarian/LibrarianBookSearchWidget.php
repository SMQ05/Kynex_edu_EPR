<?php

namespace App\Filament\SchoolAdmin\Widgets\Librarian;

use Filament\Widgets\Widget;

class LibrarianBookSearchWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.school-admin.widgets.librarian-book-search';
}
