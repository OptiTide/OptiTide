<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\BlacklistTarget;
use App\Models\Client;
use App\Models\HostingAccount;
use App\Services\Audit\AuditLog;
use App\Services\Seo\BlacklistService;

/** Manage what gets watched; the listings themselves land as cards on the boards. */
class BlacklistController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin.blacklists.index', [
            'title'        => 'Blacklist Monitoring',
            'targets'      => BlacklistTarget::query()->orderBy('value')->get(),
            'clients'      => Client::query()->orderBy('business_name')->get(),
            'client_names' => array_column(Client::all(), 'business_name', 'id'),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request, [
            'value'     => 'required|max:180',
            'type'      => 'required|in:domain,ip',
            'board'     => 'required|in:seo,hosting',
            'client_id' => 'nullable|exists:clients,id',
            'label'     => 'nullable|max:160',
        ]);

        $value = strtolower(trim($data['value']));
        $value = preg_replace('#^https?://#', '', $value);
        $value = rtrim(explode('/', $value)[0], '.');

        if ($data['type'] === 'ip' && ! filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            Session::flash('error', "'{$value}' is not a valid IPv4 address. (DNS blacklists check mail server IPv4s.)");

            return $this->redirect(route('admin.blacklists.index'));
        }
        if ($data['type'] === 'domain' && ! preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $value)) {
            Session::flash('error', "'{$value}' doesn't look like a domain.");

            return $this->redirect(route('admin.blacklists.index'));
        }

        if (BlacklistTarget::query()->where('type', $data['type'])->where('value', $value)->first()) {
            Session::flash('error', "'{$value}' is already being monitored.");

            return $this->redirect(route('admin.blacklists.index'));
        }

        $target = BlacklistTarget::create([
            'client_id' => $data['client_id'] ?: null,
            'type'      => $data['type'],
            'value'     => $value,
            'label'     => $data['label'] ?: null,
            'board'     => $data['board'],
            'status'    => BlacklistTarget::STATUS_UNKNOWN,
        ]);

        AuditLog::record('blacklist.target_added', 'blacklist_target', $target['id'], ['value' => $value]);
        Session::flash('success', "Now monitoring {$value}. It'll be checked on the next run — or check now.");

        return $this->redirect(route('admin.blacklists.index'));
    }

    /** Pull every hosting domain + server IP in as targets on the hosting board. */
    public function seed(Request $request): Response
    {
        $added = 0;

        foreach (HostingAccount::all() as $account) {
            if (($account['status'] ?? '') === 'terminated') {
                continue;
            }

            $candidates = array_filter([
                ['type' => BlacklistTarget::TYPE_DOMAIN, 'value' => strtolower((string) $account['domain'])],
                $account['ip_address'] && filter_var($account['ip_address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                    ? ['type' => BlacklistTarget::TYPE_IP, 'value' => (string) $account['ip_address']]
                    : null,
            ]);

            foreach ($candidates as $c) {
                if ($c['value'] === '' || BlacklistTarget::query()->where('type', $c['type'])->where('value', $c['value'])->first()) {
                    continue;
                }
                BlacklistTarget::create([
                    'client_id' => $account['client_id'] ?: null,
                    'type'      => $c['type'],
                    'value'     => $c['value'],
                    'label'     => $account['domain'],
                    'board'     => 'hosting',
                    'status'    => BlacklistTarget::STATUS_UNKNOWN,
                ]);
                $added++;
            }
        }

        Session::flash('success', $added > 0
            ? "Added {$added} target(s) from your hosting accounts."
            : 'Nothing new to add — every hosting domain and IP is already monitored.');

        return $this->redirect(route('admin.blacklists.index'));
    }

    /** Run all checks right now (the scheduler runs the same thing daily). */
    public function check(Request $request): Response
    {
        $stats = (new BlacklistService())->run();

        Session::flash(
            $stats['listed'] > 0 ? 'error' : 'success',
            sprintf(
                'Checked %d: %d listed%s, %d clean, %d unreachable.',
                $stats['checked'],
                $stats['listed'],
                $stats['new_cards'] > 0 ? " ({$stats['new_cards']} new board card(s))" : '',
                $stats['checked'] - $stats['listed'] - $stats['unavailable'],
                $stats['unavailable']
            )
        );

        return $this->redirect(route('admin.blacklists.index'));
    }

    public function destroy(Request $request, string $id): Response
    {
        $target = BlacklistTarget::findOrFail($id);
        BlacklistTarget::deleteById($id);
        AuditLog::record('blacklist.target_removed', 'blacklist_target', $id, ['value' => $target['value']]);
        Session::flash('success', $target['value'] . ' is no longer monitored.');

        return $this->redirect(route('admin.blacklists.index'));
    }
}
