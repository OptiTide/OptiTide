<?php

namespace App\Services\Projects;

use App\Core\Database;
use App\Models\Board;
use App\Models\BoardCard;
use App\Models\BoardColumn;
use App\Models\ClientService;
use App\Models\Service;
use App\Models\ServiceCategory;

/**
 * Creates "jobs" (client engagements) with a JOB-000001 reference and keeps the
 * delivery boards in sync — a new order/engagement auto-drops a card onto the
 * matching service board so nothing slips through the cracks.
 */
final class ProjectService
{
    /** Service-line slug => board key. Lines without a board (hosting) are skipped. */
    private const CATEGORY_BOARD = [
        'web-design' => 'web-design',
        'seo'        => 'seo',
        'smm'        => 'smm',
    ];

    /**
     * Create an engagement, stamp its job reference, and auto-create a board card.
     * Re-entrant: safe to call inside an outer transaction (e.g. the order flow).
     */
    public function createEngagement(array $attrs, bool $createCard = true): array
    {
        return Database::instance()->transaction(function () use ($attrs, $createCard) {
            $engagement = ClientService::create($attrs);
            $reference = 'JOB-' . str_pad((string) $engagement['id'], 6, '0', STR_PAD_LEFT);
            ClientService::updateById($engagement['id'], ['reference' => $reference]);
            $engagement = ClientService::find($engagement['id']);

            if ($createCard) {
                $this->createBoardCard($engagement);
            }

            return $engagement;
        });
    }

    /** Drop a card for an engagement onto the first column of its service board. */
    public function createBoardCard(array $engagement): ?array
    {
        $boardKey = $this->boardKeyForEngagement($engagement);
        if ($boardKey === null) {
            return null;
        }

        $board = Board::byKey($boardKey);
        if (! $board) {
            return null;
        }

        $column = BoardColumn::query()->where('board_id', $board['id'])->orderBy('position')->orderBy('id')->first();
        if (! $column) {
            return null;
        }

        $ref = $engagement['reference'] ?? null;
        $title = ($ref ? $ref . ' · ' : '') . ($engagement['label'] ?? 'New job');

        return BoardCard::create([
            'board_id'  => $board['id'],
            'column_id' => $column['id'],
            'client_id' => $engagement['client_id'] ?? null,
            'title'     => $title,
            'notes'     => 'Auto-created from a new order.',
            'due_date'  => null,
            'position'  => BoardCard::nextPosition($column['id']),
        ]);
    }

    private function boardKeyForEngagement(array $engagement): ?string
    {
        if (empty($engagement['service_id'])) {
            return null;
        }

        $service = Service::find($engagement['service_id']);
        if (! $service || empty($service['category_id'])) {
            return null;
        }

        $category = ServiceCategory::find($service['category_id']);
        $slug = $category['slug'] ?? '';

        return self::CATEGORY_BOARD[$slug] ?? null;
    }
}
