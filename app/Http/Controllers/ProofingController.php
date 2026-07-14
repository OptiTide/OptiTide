<?php

namespace App\Http\Controllers;

use App\Enums\ArtifactType;
use App\Enums\OrderState;
use App\Models\Order;
use App\Services\AI\PipelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Stage 5 client visual proofing: the client reviews the AI mockup in a
 * sandboxed iframe, drops spatial annotation pins, then either approves it
 * (advancing to logic generation) or requests changes (regeneration).
 */
class ProofingController extends Controller
{
    public function __construct(protected PipelineService $pipeline) {}

    public function show(Order $order): View|RedirectResponse
    {
        $this->authorizeOwner($order);

        if ($order->state !== OrderState::ClientReview) {
            return redirect()->route('filament.client.resources.orders.index');
        }

        $artifact = $order->latestArtifact(ArtifactType::MockupHtml);

        if ($artifact === null || blank($artifact->content)) {
            return redirect()->route('filament.client.resources.orders.index');
        }

        return view('storefront.proof', [
            'order' => $order,
            'artifact' => $artifact,
            'annotations' => $artifact->annotations()->latest()->get(),
        ]);
    }

    public function approve(Order $order): RedirectResponse
    {
        $this->authorizeOwner($order);
        abort_unless($order->state === OrderState::ClientReview, 409);

        $this->pipeline->generateLogic($order, Auth::user());

        return redirect()
            ->route('filament.client.resources.orders.index')
            ->with('success', 'Thanks — your design is approved and now moving into development.');
    }

    public function requestChanges(Order $order): RedirectResponse
    {
        $this->authorizeOwner($order);
        abort_unless($order->state === OrderState::ClientReview, 409);

        $this->pipeline->regenerateMockup($order, Auth::user());

        return redirect()
            ->route('filament.client.resources.orders.index')
            ->with('success', 'Thanks — we\'ll revise the design based on your notes and send a new version.');
    }

    /**
     * Serve the latest mockup HTML for the sandboxed iframe, with a strict
     * CSP that blocks outbound network (connect-src 'none') so attacker-steered
     * script in the generated HTML can't beacon the viewer's IP/UA out. Owner
     * or staff only (staff review it in the admin QA modal).
     */
    public function preview(Order $order): Response
    {
        abort_unless($order->user_id === Auth::id() || Auth::user()?->isStaff(), 403);

        $artifact = $order->latestArtifact(ArtifactType::MockupHtml);
        abort_if($artifact === null || blank($artifact->content), 404);

        $csp = implode('; ', [
            "default-src 'none'",
            "script-src https://cdn.tailwindcss.com 'unsafe-inline'",
            "style-src 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net",
            "font-src https://fonts.gstatic.com https://fonts.bunny.net data:",
            "img-src data: https:",
            "connect-src 'none'",
            "base-uri 'none'",
            "form-action 'none'",
            "frame-ancestors 'self'",
        ]);

        return response($artifact->content)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Security-Policy', $csp)
            ->header('X-Content-Type-Options', 'nosniff');
    }

    /** Save a spatial annotation pin dropped on the mockup. */
    public function annotate(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOwner($order);
        abort_unless($order->state === OrderState::ClientReview, 409);

        $data = $request->validate([
            'x' => ['required', 'numeric', 'between:0,100'],
            'y' => ['required', 'numeric', 'between:0,100'],
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $artifact = $order->latestArtifact(ArtifactType::MockupHtml);
        abort_if($artifact === null, 404);

        $artifact->annotations()->create([
            'user_id' => Auth::id(),
            'x' => $data['x'],
            'y' => $data['y'],
            'comment' => $data['comment'],
        ]);

        return redirect()->route('proofing.show', $order)->with('success', 'Comment added.');
    }

    protected function authorizeOwner(Order $order): void
    {
        abort_unless($order->user_id === Auth::id(), 403);
    }
}
