<?php

namespace App\Services;

use App\Models\LibraryLoan;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LibraryClearanceService
{
    /**
     * Attach library_clearance_hold / library_clearance_message (and counts)
     * to each model in the collection, batched in a single query.
     *
     * @param  Collection  $models  Each model needs an `id`, and (unless $usersOnly) a `user_id`.
     * @param  bool  $usersOnly  True when $models are User records keyed directly by user id.
     */
    public function attachTo(Collection $models, bool $usersOnly = false): void
    {
        if ($models->isEmpty()) {
            return;
        }

        $studentIds = $usersOnly ? collect() : $models->pluck('id')->filter()->map(fn ($id) => (int) $id);
        $userIds = $usersOnly
            ? $models->pluck('id')->filter()->map(fn ($id) => (int) $id)
            : $models->pluck('user_id')->filter()->map(fn ($id) => (int) $id);

        $loans = LibraryLoan::query()
            ->where(function ($query) use ($studentIds, $userIds) {
                if ($studentIds->isNotEmpty()) {
                    $query->whereIn('student_id', $studentIds);
                }
                if ($userIds->isNotEmpty()) {
                    $method = $studentIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('user_id', $userIds);
                }
            })
            ->where(function ($query) {
                $query->whereIn('status', ['issued', 'lost'])
                    ->orWhereRaw('fine_amount > fine_paid');
            })
            ->get(['id', 'student_id', 'user_id', 'status', 'fine_amount', 'fine_paid']);

        foreach ($models as $model) {
            $matching = $loans->filter(function (LibraryLoan $loan) use ($model, $usersOnly) {
                if ($usersOnly) {
                    return (int) $loan->user_id === (int) $model->id;
                }

                return (int) $loan->student_id === (int) $model->id
                    || ($model->user_id && (int) $loan->user_id === (int) $model->user_id);
            });
            $summary = $this->summarize($matching);

            $model->setAttribute('library_clearance_hold', $summary['has_hold']);
            $model->setAttribute('library_unreturned_count', $summary['unreturned_count']);
            $model->setAttribute('library_fine_due', $summary['fine_due']);
            $model->setAttribute('library_clearance_message', $summary['message']);
        }
    }

    public function summaryFor(?int $studentId, ?int $userId): array
    {
        if (! $studentId && ! $userId) {
            return $this->summarize(collect());
        }

        $loans = LibraryLoan::query()
            ->where(function ($query) use ($studentId, $userId) {
                if ($studentId) {
                    $query->where('student_id', $studentId);
                }
                if ($userId) {
                    $method = $studentId ? 'orWhere' : 'where';
                    $query->{$method}('user_id', $userId);
                }
            })
            ->where(function ($query) {
                $query->whereIn('status', ['issued', 'lost'])
                    ->orWhereRaw('fine_amount > fine_paid');
            })
            ->get(['status', 'fine_amount', 'fine_paid']);

        return $this->summarize($loans);
    }

    private function summarize(Collection $loans): array
    {
        $unreturnedCount = $loans->whereIn('status', ['issued', 'lost'])->count();
        $fineDue = round($loans->sum(fn (LibraryLoan $loan) => max(
            0,
            (float) $loan->fine_amount - (float) $loan->fine_paid
        )), 2);

        $parts = [];
        if ($unreturnedCount > 0) {
            $parts[] = $unreturnedCount.' unreturned '.Str::plural('book', $unreturnedCount);
        }
        if ($fineDue > 0) {
            $parts[] = 'Rs. '.number_format($fineDue, 2).' fine due';
        }

        return [
            'has_hold' => $unreturnedCount > 0 || $fineDue > 0,
            'unreturned_count' => $unreturnedCount,
            'fine_due' => $fineDue,
            'message' => $parts ? implode(' and ', $parts).'.' : 'Library clearance complete.',
        ];
    }
}
