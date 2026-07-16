<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Board;
use App\Models\BoardCard;
use App\Models\BoardCardChecklistItem;
use App\Models\BoardCardComment;
use App\Models\BoardColumn;
use App\Models\Client;
use App\Models\User;

/**
 * Trello-style delivery boards, one per service line (Web Design / SEO / Social).
 * Cards move between columns via drag-and-drop persisted through moveCard (AJAX).
 *
 * The detail of a card (checklist, comments) lives on its own page rather than in
 * a modal: a checklist needs three endpoints of its own and comments a fourth,
 * and forms cannot nest inside the card's edit form. This mirrors the helpdesk,
 * where a thing with a thread is a page.
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

        $cards = Board::cards($board['id']);
        $cardIds = array_column($cards, 'id');

        $cardsByColumn = [];
        foreach ($cards as $card) {
            $cardsByColumn[$card['column_id']][] = $card;
        }

        return $this->view('admin.boards.show', [
            'title'         => $board['name'],
            'board'         => $board,
            'boards'        => Board::ordered(),
            'columns'       => $columns,
            'cardsByColumn' => $cardsByColumn,
            'clients'       => Client::query()->orderBy('business_name')->get(),
            'progress'      => Board::checklistProgressMap($cardIds),
            'commentCounts' => BoardCardComment::countsForCards($cardIds),
            'staffNames'    => $this->staffNames(),
        ]);
    }

    /** Full detail of a single card: fields, checklist and the comment thread. */
    public function card(Request $request, string $id): Response
    {
        $card = BoardCard::findOrFail($id);
        $board = $this->boardById($card['board_id']);

        return $this->view('admin.boards.card', [
            'title'     => $card['title'],
            'board'     => $board,
            'card'      => $card,
            'column'    => BoardColumn::find($card['column_id']),
            'columns'   => Board::columns($board['id']),
            'clients'   => Client::query()->orderBy('business_name')->get(),
            'staff'     => $this->staff(),
            'checklist' => BoardCardChecklistItem::forCard($card['id']),
            'progress'  => Board::checklistProgress($card['id']),
            'comments'  => BoardCardComment::forCard($card['id']),
            'authors'   => array_column(User::all(), 'name', 'id'),
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
            'board_id'       => $board['id'],
            'column_id'      => $column['id'],
            'client_id'      => $request->input('client_id') ? (int) $request->input('client_id') : null,
            'title'          => $data['title'],
            'notes'          => null,
            'due_date'       => null,
            'assigned_to'    => null,
            'priority'       => BoardCard::PRIORITY_NORMAL,
            'client_visible' => 1,
            'position'       => BoardCard::nextPosition($column['id']),
        ]);

        Session::flash('success', 'Card added.');

        return $this->redirect(route('admin.boards.show', ['key' => $board['key']]));
    }

    public function updateCard(Request $request, string $id): Response
    {
        $card = BoardCard::findOrFail($id);

        $data = $this->validate($request, [
            'title'    => 'required|max:200',
            'notes'    => 'nullable|max:2000',
            'due_date' => 'nullable',
            'priority' => 'required|in:low,normal,high,urgent',
        ]);

        // completed_at is a timestamp, not a flag — keep the original moment when
        // the card was already complete so re-saving doesn't reset it.
        $complete = $request->boolean('is_complete');
        $completedAt = $complete ? ($card['completed_at'] ?: now()) : null;

        BoardCard::updateById($id, [
            'title'          => $data['title'],
            'notes'          => $data['notes'] ?: null,
            'due_date'       => trim((string) ($data['due_date'] ?? '')) ?: null,
            'client_id'      => $request->input('client_id') ? (int) $request->input('client_id') : null,
            'assigned_to'    => $this->assigneeOrFail($request),
            'priority'       => $data['priority'],
            'client_visible' => $request->boolean('client_visible') ? 1 : 0,
            'completed_at'   => $completedAt,
        ]);

        Session::flash('success', 'Card updated.');

        return $this->redirect(route('admin.cards.show', ['id' => $card['id']]));
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

    public function storeChecklistItem(Request $request, string $id): Response
    {
        $card = BoardCard::findOrFail($id);
        $data = $this->validate($request, ['text' => 'required|max:300']);

        BoardCardChecklistItem::create([
            'card_id'  => $card['id'],
            'text'     => $data['text'],
            'done'     => 0,
            'position' => BoardCardChecklistItem::nextPosition($card['id']),
        ]);

        return $this->redirect(route('admin.cards.show', ['id' => $card['id']]));
    }

    public function toggleChecklistItem(Request $request, string $id): Response
    {
        $item = BoardCardChecklistItem::findOrFail($id);

        BoardCardChecklistItem::updateById($item['id'], ['done' => empty($item['done']) ? 1 : 0]);

        return $this->redirect(route('admin.cards.show', ['id' => $item['card_id']]));
    }

    public function destroyChecklistItem(Request $request, string $id): Response
    {
        $item = BoardCardChecklistItem::findOrFail($id);
        BoardCardChecklistItem::deleteById($item['id']);

        return $this->redirect(route('admin.cards.show', ['id' => $item['card_id']]));
    }

    public function storeComment(Request $request, string $id): Response
    {
        $card = BoardCard::findOrFail($id);
        $data = $this->validate($request, ['body' => 'required|max:5000']);

        BoardCardComment::create([
            'card_id'     => $card['id'],
            'user_id'     => Auth::id(),
            'body'        => $data['body'],
            'is_internal' => $request->boolean('is_internal') ? 1 : 0,
        ]);

        Session::flash('success', 'Comment posted.');

        return $this->redirect(route('admin.cards.show', ['id' => $card['id']]));
    }

    /**
     * Only staff can own delivery work, so an assignee outside the staff list is
     * a tampered id rather than a typo — reject it instead of storing it.
     */
    protected function assigneeOrFail(Request $request): ?int
    {
        if (! $request->filled('assigned_to')) {
            return null;
        }

        $id = (int) $request->input('assigned_to');
        $this->authorize(isset($this->staffNames()[$id]), 'That assignee is not a staff member.');

        return $id;
    }

    /** @return array<int,array<string,mixed>> */
    protected function staff(): array
    {
        return User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF])
            ->orderBy('name')
            ->get();
    }

    /** @return array<int|string,string> */
    protected function staffNames(): array
    {
        return array_column($this->staff(), 'name', 'id');
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
