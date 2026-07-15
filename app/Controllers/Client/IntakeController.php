<?php

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\ClientService;
use App\Models\ProjectIntake;
use App\Models\Service;
use App\Models\ServiceCategory;

/**
 * Per-service project brief, collected right after a client orders. Scoped to
 * the client's own engagement; questions come from config/intake.php.
 */
class IntakeController extends Controller
{
    public function show(Request $request, string $engagement): Response
    {
        $eng = $this->ownedEngagement($engagement);
        $category = $this->categorySlug($eng);
        $set = ProjectIntake::questionsFor($category);
        if (! $set) {
            return $this->redirectRoute('portal.services');
        }

        $existing = ProjectIntake::firstWhere('reference', $eng['reference'] ?? '');

        return $this->view('client.intake.show', [
            'title'      => $set['label'],
            'engagement' => $eng,
            'set'        => $set,
            'invoiceId'  => (int) $request->query('invoice', 0),
            'answers'    => $existing ? (json_decode((string) $existing['data'], true) ?: []) : [],
        ]);
    }

    public function store(Request $request, string $engagement): Response
    {
        $eng = $this->ownedEngagement($engagement);
        $category = $this->categorySlug($eng);
        $set = ProjectIntake::questionsFor($category);
        if (! $set) {
            return $this->redirectRoute('portal.services');
        }

        $answers = [];
        foreach ($set['questions'] as $q) {
            $answers[$q['key']] = trim((string) $request->input('q_' . $q['key'], ''));
        }

        $existing = ProjectIntake::firstWhere('reference', $eng['reference'] ?? '');
        if ($existing) {
            ProjectIntake::updateById($existing['id'], ['data' => json_encode($answers)]);
        } else {
            ProjectIntake::create([
                'client_id'  => $eng['client_id'],
                'service_id' => $eng['service_id'] ?? null,
                'reference'  => $eng['reference'] ?? null,
                'category'   => $category,
                'data'       => json_encode($answers),
            ]);
        }

        $this->flash('success', 'Thanks! Your project brief has been sent to our team.');

        $invoiceId = (int) $request->input('invoice', 0);
        if ($invoiceId > 0) {
            return $this->redirectRoute('portal.invoices.show', ['id' => $invoiceId]);
        }

        return $this->redirectRoute('portal.services');
    }

    protected function ownedEngagement(string $id): array
    {
        $eng = ClientService::findOrFail($id);
        if ((string) $eng['client_id'] !== (string) Auth::clientId()) {
            $this->abort(404, 'Not found.');
        }

        return $eng;
    }

    protected function categorySlug(array $eng): ?string
    {
        if (empty($eng['service_id'])) {
            return null;
        }
        $svc = Service::find($eng['service_id']);
        if (! $svc || empty($svc['category_id'])) {
            return null;
        }
        $cat = ServiceCategory::find($svc['category_id']);

        return $cat['slug'] ?? null;
    }
}
