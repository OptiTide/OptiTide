<?php

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Board;
use App\Models\BoardCard;
use App\Models\BoardColumn;

/**
 * Read-only view of the client's own delivery cards across every board, so a
 * client can follow their project's progress. Scoped strictly to their own
 * client_id — they never see another client's cards or the full boards.
 */
class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $clientId = Auth::clientId();

        $cards = $clientId
            ? BoardCard::query()->where('client_id', $clientId)->orderBy('board_id')->orderBy('position')->orderBy('id')->get()
            : [];

        $boards = array_column(Board::all(), null, 'id');
        $columns = array_column(BoardColumn::all(), null, 'id');

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
            $grouped[$bid]['cards'][] = $card;
        }

        return $this->view('client.project.index', [
            'title'   => 'My Project',
            'grouped' => $grouped,
        ]);
    }
}
