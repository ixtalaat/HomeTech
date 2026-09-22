<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Switch the interface locale (English/Arabic).
     *
     * Stored in the session and in a year-long cookie so the choice
     * survives logout and session invalidation.
     */
    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, SetLocale::LOCALES, true)) {
            abort(404);
        }

        $request->session()->put('locale', $locale);
        cookie()->queue(cookie('locale', $locale, 60 * 24 * 365));

        return back()->with('success', $locale === 'ar' ? 'تم التبديل إلى العربية.' : 'Switched to English.');
    }
}
