<?php

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Board;
use App\Models\BoardCard;
use App\Models\BoardCardComment;
use App\Models\BoardColumn;

/**
 * Read-only view of the client's own delivery cards across every board, so a
 * client can follow their project's progress. Scoped strictly to their own
 * client_id — they never see another client's cards or the full boards.
 *
 * Internal delivery detail never reaches this page, and the filtering is done in
 * the QUERY (BoardCard::forClient, BoardCardComment::forCards with internal
 * excluded) rather than the template: a row we never load cannot leak.
 */
class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $clientId = Auth::clientId();

        $cards = $clientId ? BoardCard::forClient($clientId) : [];
        $cardIds = array_column($cards, 'id');

        $boards = array_column(Board::all(), null, 'id');
        $columns = array_column(BoardColumn::all(), null, 'id');
        $progress = Board::checklistProgressMap($cardIds);
        $comments = BoardCardComment::forCards($cardIds, false);

        $grouped = [];
        foreach ($cards as $card) {
            $bid = $card['board_id'];
            if (! isset($grouped[$bid])) {
                $grouped[$bid] = [
                    'board' => $boards[$bid]['name'] ?? 'Project',
                    'cards' => [],
                ];
            }
            $card['_status'] = $columns[$card['column_id']]['name'] ?? '—';
            $card['_progress'] = $progress[$card['id']] ?? ['done' => 0, 'total' => 0, 'pct' => 0];
            $card['_comments'] = $comments[$card['id']] ?? [];
            $grouped[$bid]['cards'][] = $card;
        }

        return $this->view('client.project.index', [
            'title'   => 'My Project',
            'grouped' => $grouped,
        ]);
    }
}
