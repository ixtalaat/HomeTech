<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales.
     *
     * @var array<int, string>
     */
    public const LOCALES = ['en', 'ar'];

    /**
     * Handle an incoming request.
     *
     * Session wins when present; otherwise the persistent locale cookie
     * applies, so the choice survives logout and session invalidation.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', $request->cookie('locale', config('app.locale', 'en')));

        if (! in_array($locale, self::LOCALES, true)) {
            $locale = 'en';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
