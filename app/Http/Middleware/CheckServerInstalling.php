<?php

namespace App\Http\Middleware;

use App\Models\Server;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class CheckServerInstalling
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $server = $request->route()->parameter('server');

        if (!$server instanceof Server)
        {
            throw new NotFoundHttpException('Server not found');
        }

        if ($server->is_installing)
        {
            if ($request->wantsJson())
            {
                throw new UnauthorizedHttpException('Server is installing');
            } else {
                return redirect()->route('servers.show.installing.index', $server->id);
            }
        }

        return $next($request);
    }
}
