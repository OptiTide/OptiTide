<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Visit;

class VisitorController extends Controller
{
    public function index(Request $request): Response
    {
        // Aggregate in PHP (recent window) — the query builder has no GROUP BY.
        $visits = Visit::query()->orderBy('id', 'desc')->limit(5000)->get();

        $uniq = [];
        $pages = [];
        $refs = [];
        $sevenDaysAgo = date('Y-m-d 00:00:00', strtotime('-7 days'));
        $last7 = 0;
        foreach ($visits as $v) {
            $uniq[$v['visitor_id']] = true;
            $pages[$v['path']] = ($pages[$v['path']] ?? 0) + 1;
            $host = $this->refHost($v['referrer'] ?? '');
            if ($host !== '') {
                $refs[$host] = ($refs[$host] ?? 0) + 1;
            }
            if (($v['created_at'] ?? '') >= $sevenDaysAgo) {
                $last7++;
            }
        }
        arsort($pages);
        arsort($refs);

        return $this->view('admin.visitors.index', [
            'title'          => 'Visitors',
            'total'          => count($visits),
            'unique'         => count($uniq),
            'last7'          => $last7,
            'top_pages'      => array_slice($pages, 0, 10, true),
            'top_referrers'  => array_slice($refs, 0, 10, true),
            'recent'         => array_slice($visits, 0, 15),
            'chat_convos'    => count(ChatConversation::query()->get()),
            'chat_open'      => count(ChatConversation::query()->where('status', 'open')->get()),
            'chat_messages'  => count(ChatMessage::query()->get()),
        ]);
    }

    private function refHost(string $referrer): string
    {
        $referrer = trim($referrer);
        if ($referrer === '') {
            return 'Direct / none';
        }
        $host = parse_url($referrer, PHP_URL_HOST);

        return $host ? preg_replace('/^www\./', '', $host) : 'Other';
    }
}
