<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of customer reviews.
     */
    public function index(Request $request): View
    {
        $query = Review::with(['user', 'request.service'])->latest();

        if ($request->filled('rating')) {
            $query->where('rating', $request->integer('rating'));
        }

        $reviews = $query->paginate(15)->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }
}
