<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookIssueStatus;
use App\Models\Tenant\Book;
use App\Models\Tenant\BookIssue;
use App\Models\Tenant\LibraryMember;
use Illuminate\Support\Facades\DB;

class LibraryService
{
    /**
     * Issue a book to a library member.
     */
    public function issueBook(
        string $bookId,
        string $libraryMemberId,
        string $issuedBy,
        ?string $dueDate = null,
    ): BookIssue {
        return DB::transaction(function () use ($bookId, $libraryMemberId, $issuedBy, $dueDate) {
            $book   = Book::findOrFail($bookId);
            $member = LibraryMember::findOrFail($libraryMemberId);

            // Validate availability
            if ($book->available_copies < 1) {
                throw new \RuntimeException("Book '{$book->title}' has no available copies.");
            }

            // Validate member can borrow
            if (! $member->can_borrow) {
                throw new \RuntimeException("Member '{$member->name}' has reached the borrowing limit or is inactive.");
            }

            // Check if member already has this book
            $alreadyIssued = BookIssue::where('book_id', $bookId)
                ->where('library_member_id', $libraryMemberId)
                ->currentlyIssued()
                ->exists();

            if ($alreadyIssued) {
                throw new \RuntimeException("This book is already issued to this member.");
            }

            // Create issue record
            $issue = BookIssue::create([
                'book_id'            => $bookId,
                'library_member_id'  => $libraryMemberId,
                'issue_date'         => now()->toDateString(),
                'due_date'           => $dueDate ?? now()->addDays(14)->toDateString(),
                'status'             => BookIssueStatus::Issued,
                'issued_by'          => $issuedBy,
            ]);

            // Decrement available copies
            $book->decrement('available_copies');

            return $issue;
        });
    }

    /**
     * Return a book.
     */
    public function returnBook(
        string $bookIssueId,
        string $returnedTo,
        int $finePerDayPaisas = 1000, // PKR 10/day default
    ): BookIssue {
        return DB::transaction(function () use ($bookIssueId, $returnedTo, $finePerDayPaisas) {
            $issue = BookIssue::findOrFail($bookIssueId);

            if (! in_array($issue->status, [BookIssueStatus::Issued, BookIssueStatus::Overdue])) {
                throw new \RuntimeException("This book is not currently issued (status: {$issue->status->label()}).");
            }

            // Calculate fine if overdue
            $fine = 0;
            if ($issue->due_date->isPast()) {
                $daysOverdue = (int) $issue->due_date->diffInDays(now());
                $fine = $daysOverdue * $finePerDayPaisas;
            }

            // Update issue
            $issue->update([
                'return_date'  => now()->toDateString(),
                'status'       => BookIssueStatus::Returned,
                'fine_paisas'  => $fine,
                'returned_to'  => $returnedTo,
            ]);

            // Increment available copies
            $issue->book->increment('available_copies');

            return $issue->fresh();
        });
    }

    /**
     * Mark a book as lost.
     */
    public function markLost(string $bookIssueId, int $replacementCostPaisas = 0): BookIssue
    {
        return DB::transaction(function () use ($bookIssueId, $replacementCostPaisas) {
            $issue = BookIssue::findOrFail($bookIssueId);

            if ($issue->status === BookIssueStatus::Returned) {
                throw new \RuntimeException("Cannot mark a returned book as lost.");
            }

            $fine = $replacementCostPaisas > 0
                ? $replacementCostPaisas
                : ($issue->book->price_paisas ?? 0);

            $issue->update([
                'status'      => BookIssueStatus::Lost,
                'fine_paisas' => $fine,
            ]);

            // Decrement total copies (book is permanently lost)
            $issue->book->decrement('total_copies');

            return $issue->fresh();
        });
    }

    /**
     * Calculate fine for an overdue book issue.
     */
    public function calculateFine(string $bookIssueId, int $finePerDayPaisas = 1000): int
    {
        $issue = BookIssue::findOrFail($bookIssueId);

        if (! $issue->is_overdue) {
            return 0;
        }

        return $issue->days_overdue * $finePerDayPaisas;
    }

    /**
     * Mark overdue books (bulk operation for scheduled job).
     */
    public function markOverdueBooks(): int
    {
        return BookIssue::where('status', BookIssueStatus::Issued)
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => BookIssueStatus::Overdue]);
    }

    /**
     * Get library statistics.
     */
    public function getStats(): array
    {
        return [
            'total_books'       => Book::sum('total_copies'),
            'available_books'   => Book::sum('available_copies'),
            'issued_books'      => BookIssue::currentlyIssued()->count(),
            'overdue_books'     => BookIssue::overdue()->count(),
            'total_members'     => LibraryMember::active()->count(),
            'total_categories'  => \App\Models\Tenant\BookCategory::active()->count(),
        ];
    }

    /**
     * Search books by title, author, or ISBN.
     */
    public function searchBooks(string $query, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return Book::active()
            ->where(function ($q) use ($query) {
                $q->where('title', 'ilike', "%{$query}%")
                    ->orWhere('author', 'ilike', "%{$query}%")
                    ->orWhere('isbn', 'ilike', "%{$query}%");
            })
            ->limit($limit)
            ->get();
    }
}
