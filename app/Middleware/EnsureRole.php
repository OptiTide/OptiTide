<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Exceptions\HttpException;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Closure;

/** Usage: ->middleware('role:admin') or 'role:admin,staff'. */
class EnsureRole implements Middleware
{
    /** @var string[] */
    protected array $roles;

    public function __construct(string $roles = '')
    {
        $this->roles = array_filter(explode(',', $roles));
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guest()) {
            Session::put('_intended', $request->path());

            return Response::redirect(route('login'));
        }

        if ($this->roles !== [] && ! Auth::is(...$this->roles)) {
            // Send a mis-routed user to their own area rather than a dead 403.
            if (Auth::isClient() && in_array('admin', $this->roles, true)) {
                return Response::redirect(route('portal.dashboard'));
            }

            throw new HttpException(403, 'You do not have access to this area.');
        }

        return $next($request);
    }
}
