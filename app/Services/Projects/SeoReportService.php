<?php

namespace App\Services\Projects;

use App\Models\Board;
use App\Models\BoardCard;
use App\Models\BoardColumn;
use App\Models\ClientService;
use App\Models\Service;
use App\Models\ServiceCategory;

/**
 * SEO reports are always due at the end of the month. This creates one
 * "SEO Report — {Month}" card per active SEO client on the SEO board, due the
 * last day of the target month. Idempotent per client + month.
 */
final class SeoReportService
{
    /** @param string|null $month 'Y-m'; defaults to the current month. */
    public function generateForMonth(?string $month = null): int
    {
        $month = $month ?: date('Y-m');
        $firstOfMonth = $month . '-01';
        $dueDate = date('Y-m-t', strtotime($firstOfMonth)); // last day of the month
        $label = 'SEO Report — ' . date('F Y', strtotime($firstOfMonth));

        $board = Board::byKey('seo');
        if (! $board) {
            return 0;
        }

        $column = BoardColumn::query()->where('board_id', $board['id'])->where('name', 'Reporting')->first()
            ?? BoardColumn::query()->where('board_id', $board['id'])->orderBy('position')->first();
        if (! $column) {
            return 0;
        }

        $seoServiceIds = $this->seoServiceIds();
        if ($seoServiceIds === []) {
            return 0;
        }

        $count = 0;
        $seenClients = [];
        foreach (ClientService::query()->where('status', ClientService::STATUS_ACTIVE)->get() as $cs) {
            $clientId = $cs['client_id'] ?? null;
            if (! $clientId || ! in_array((string) $cs['service_id'], $seoServiceIds, true)) {
                continue;
            }
            if (isset($seenClients[$clientId])) {
                continue; // one report per client per month
            }
            $seenClients[$clientId] = true;

            // Idempotent: skip if this month's report card already exists for the client.
            $exists = BoardCard::query()
                ->where('board_id', $board['id'])
                ->where('client_id', $clientId)
                ->where('title', $label)
                ->exists();
            if ($exists) {
                continue;
            }

            BoardCard::create([
                'board_id'  => $board['id'],
                'column_id' => $column['id'],
                'client_id' => $clientId,
                'title'     => $label,
                'notes'     => 'Auto-created — SEO reports are due at the end of each month.',
                'due_date'  => $dueDate,
                'position'  => BoardCard::nextPosition($column['id']),
            ]);
            $count++;
        }

        return $count;
    }

    /** @return string[] service ids in the SEO service line */
    private function seoServiceIds(): array
    {
        $category = ServiceCategory::firstWhere('slug', 'seo');
        if (! $category) {
            return [];
        }

        $ids = [];
        foreach (Service::query()->where('category_id', $category['id'])->get() as $s) {
            $ids[] = (string) $s['id'];
        }

        return $ids;
    }
}
