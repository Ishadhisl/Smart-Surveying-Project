<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('lang')) {
            $lang = $request->get('lang');
            if (in_array($lang, ['en', 'si', 'ta'], true)) {
                session(['locale' => $lang]);
                if (auth()->check()) {
                    auth()->user()->update(['language' => $lang]);
                }
            }
        }

        $locale = session('locale', auth()->user()->language ?? config('app.locale'));
        app()->setLocale($locale);

        return $next($request);
    }
}
