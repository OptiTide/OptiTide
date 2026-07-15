<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Board;
use App\Models\BoardCard;
use App\Models\BoardColumn;
use App\Models\Client;

/**
 * Trello-style delivery boards, one per service line (Web Design / SEO / Social).
 * Cards move between columns via drag-and-drop persisted through moveCard (AJAX).
 */
class BoardController extends Controller
{
    public function index(Request $request): Response
    {
        $boards = Board::ordered();
        $counts = [];
        foreach ($boards as $b) {
            $counts[$b['id']] = count(BoardCard::query()->where('board_id', $b['id'])->get());
        }

        return $this->view('admin.boards.index', [
            'title'  => 'Project Boards',
            'boards' => $boards,
            'counts' => $counts,
        ]);
    }

    public function show(Request $request, string $key): Response
    {
        $board = $this->boardOrFail($key);
        $columns = Board::columns($board['id']);

        $cardsByColumn = [];
        foreach (Board::cards($board['id']) as $card) {
            $cardsByColumn[$card['column_id']][] = $card;
        }

        return $this->view('admin.boards.show', [
            'title'         => $board['name'],
            'board'         => $board,
            'boards'        => Board::ordered(),
            'columns'       => $columns,
            'cardsByColumn' => $cardsByColumn,
            'clients'       => Client::query()->orderBy('business_name')->get(),
        ]);
    }

    public function storeCard(Request $request, string $key): Response
    {
        $board = $this->boardOrFail($key);

        $data = $this->validate($request, [
            'title'     => 'required|max:200',
            'column_id' => 'required',
        ]);

        $column = BoardColumn::findOrFail($data['column_id']);
        $this->authorize((string) $column['board_id'] === (string) $board['id'], 'Column does not belong to this board.');

        BoardCard::create([
            'board_id'  => $board['id'],
            'column_id' => $column['id'],
            'client_id' => $request->input('client_id') ? (int) $request->input('client_id') : null,
            'title'     => $data['title'],
            'notes'     => null,
            'due_date'  => null,
            'position'  => BoardCard::nextPosition($column['id']),
        ]);

        Session::flash('success', 'Card added.');

        return $this->redirect(route('admin.boards.show', ['key' => $board['key']]));
    }

    public function updateCard(Request $request, string $id): Response
    {
        $card = BoardCard::findOrFail($id);
        $board = $this->boardById($card['board_id']);

        $data = $this->validate($request, [
            'title'    => 'required|max:200',
            'notes'    => 'nullable|max:2000',
            'due_date' => 'nullable',
        ]);

        BoardCard::updateById($id, [
            'title'     => $data['title'],
            'notes'     => $data['notes'] ?: null,
            'due_date'  => trim((string) ($data['due_date'] ?? '')) ?: null,
            'client_id' => $request->input('client_id') ? (int) $request->input('client_id') : null,
        ]);

        Session::flash('success', 'Card updated.');

        return $this->redirect(route('admin.boards.show', ['key' => $board['key']]));
    }

    public function destroyCard(Request $request, string $id): Response
    {
        $card = BoardCard::findOrFail($id);
        $board = $this->boardById($card['board_id']);
        BoardCard::deleteById($id);

        Session::flash('status', 'Card deleted.');

        return $this->redirect(route('admin.boards.show', ['key' => $board['key']]));
    }

    public function storeColumn(Request $request, string $key): Response
    {
        $board = $this->boardOrFail($key);
        $data = $this->validate($request, ['name' => 'required|max:80']);

        BoardColumn::create([
            'board_id' => $board['id'],
            'name'     => $data['name'],
            'position' => BoardColumn::nextPosition($board['id']),
        ]);

        Session::flash('success', 'Column added.');

        return $this->redirect(route('admin.boards.show', ['key' => $board['key']]));
    }

    /** AJAX: move a card to a column and re-order that column. Returns JSON. */
    public function moveCard(Request $request, string $id): Response
    {
        $card = BoardCard::findOrFail($id);
        $column = BoardColumn::findOrFail((int) $request->input('column_id'));

        if ((string) $column['board_id'] !== (string) $card['board_id']) {
            return $this->json(['ok' => false, 'error' => 'Cross-board move rejected.'], 422);
        }

        // Land the dragged card in the target column first (covers a missing order).
        BoardCard::updateById($card['id'], ['column_id' => $column['id']]);

        $order = $request->input('order', []);
        if (! is_array($order)) {
            $order = [];
        }

        $pos = 0;
        foreach ($order as $cardId) {
            $c = BoardCard::find($cardId);
            // Only touch cards that really belong to this board (tamper guard).
            if ($c && (string) $c['board_id'] === (string) $card['board_id']) {
                BoardCard::updateById($cardId, ['column_id' => $column['id'], 'position' => $pos++]);
            }
        }

        return $this->json(['ok' => true]);
    }

    protected function boardOrFail(string $key): array
    {
        $board = Board::byKey($key);
        if (! $board) {
            $this->abort(404, 'Board not found.');
        }

        return $board;
    }

    protected function boardById(int|string $id): array
    {
        $board = Board::find($id);
        if (! $board) {
            $this->abort(404, 'Board not found.');
        }

        return $board;
    }
}
