<?php

namespace App\Http\Controllers;

use App\Enums\OrderState;
use App\Exceptions\InvalidStateTransition;
use App\Models\ClientSubmission;
use App\Models\Order;
use App\Services\SchemaFormBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The schema-driven project brief a client completes after paying (and
 * signing any required agreement). Submitting it advances the order into
 * the CRM pipeline (pending_intake -> admin_review) and captures the brand
 * assets the AI mockup stage will consume.
 */
class IntakeController extends Controller
{
    public function __construct(protected SchemaFormBuilder $builder) {}

    public function show(Order $order): View|RedirectResponse
    {
        $this->authorizeOwner($order);

        if ($redirect = $this->guard($order)) {
            return $redirect;
        }

        return view('storefront.brief', [
            'order' => $order,
            'schema' => $order->onboardingFormSchema(),
        ]);
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOwner($order);

        if ($redirect = $this->guard($order)) {
            return $redirect;
        }

        $schema = $order->onboardingFormSchema();
        $validated = $request->validate($this->builder->rules($schema));

        $data = [];
        $assets = [];

        foreach ($this->builder->fields($schema) as $field) {
            $name = $field['name'];

            if ($field['type'] === 'file') {
                $assets[$name] = $this->storeFiles($request, $order, $field);
            } elseif ($field['type'] === 'color') {
                $assets[$name] = $validated[$name] ?? null;
            } else {
                $data[$name] = $validated[$name] ?? null;
            }
        }

        try {
            DB::transaction(function () use ($order, $schema, $data, $assets) {
                ClientSubmission::create([
                    'order_id' => $order->id,
                    'form_schema_id' => $schema->id,
                    'user_id' => $order->user_id,
                    'data' => $data,
                    'brand_assets' => $assets,
                    'submitted_at' => now(),
                ]);

                // Hand the order to the VA for requirements review. The
                // compare-and-swap in transitionTo() makes a concurrent
                // double-submit lose the race rather than double-advance.
                $order->transitionTo(OrderState::AdminReview, Auth::user(), 'Client submitted project brief.');
            });
        } catch (InvalidStateTransition) {
            // Another request already advanced this order.
            return redirect()
                ->route('filament.client.resources.orders.index')
                ->with('error', 'This project brief has already been submitted.');
        }

        return redirect()
            ->route('filament.client.resources.orders.index')
            ->with('success', 'Thanks — your project brief has been submitted. Our team will review it shortly.');
    }

    /**
     * Stream a brand asset uploaded with the brief. Owner or staff (the VA
     * reviews briefs) only, and only paths actually recorded in the
     * submission — so a crafted `path` can't reach arbitrary files.
     */
    public function asset(Request $request, Order $order): StreamedResponse
    {
        abort_unless($order->user_id === Auth::id() || Auth::user()?->isStaff(), 403);

        $submission = $order->submission();
        abort_if($submission === null, 404);

        $requested = (string) $request->query('path');

        $allowed = collect($submission->brand_assets ?? [])
            ->flatMap(fn ($value) => is_array($value) ? $value : [$value])
            ->filter(fn ($value) => is_string($value))
            ->contains($requested);

        abort_unless($allowed, 404);

        $disk = config('filesystems.private_disk');
        abort_unless(Storage::disk($disk)->exists($requested), 404);

        return Storage::disk($disk)->download($requested, basename($requested));
    }

    /**
     * Store uploaded file(s) for a field on the private disk, returning the
     * stored path(s).
     *
     * @return string|array<int, string>|null
     */
    protected function storeFiles(Request $request, Order $order, array $field): string|array|null
    {
        $name = $field['name'];

        if (! $request->hasFile($name)) {
            return ($field['multiple'] ?? false) ? [] : null;
        }

        $dir = "intake_assets/{$order->id}";
        $disk = config('filesystems.private_disk');

        if ($field['multiple'] ?? false) {
            return collect($request->file($name))
                ->map(fn ($file) => $file->store($dir, $disk))
                ->all();
        }

        return $request->file($name)->store($dir, $disk);
    }

    protected function guard(Order $order): ?RedirectResponse
    {
        if ($order->needsIntake()) {
            return null;
        }

        // Already submitted, wrong stage, or blocked by an unsigned contract.
        return redirect()
            ->route('filament.client.resources.orders.index')
            ->with('error', $order->hasUnsignedContract()
                ? 'Please sign your service agreement before submitting your project brief.'
                : 'This project brief is no longer available.');
    }

    protected function authorizeOwner(Order $order): void
    {
        abort_unless($order->user_id === Auth::id(), 403);
    }
}
