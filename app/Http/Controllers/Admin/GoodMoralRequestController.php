<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoodMoralRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GoodMoralRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $query = GoodMoralRequest::orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('student_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $requests = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/GoodMoral/Index', [
            'requests' => $requests,
            'filters'  => $request->only('status', 'search'),
            'counts'   => [
                'all'              => GoodMoralRequest::count(),
                'pending'          => GoodMoralRequest::where('status', 'pending')->count(),
                'payment_verified' => GoodMoralRequest::where('status', 'payment_verified')->count(),
                'ready_for_pickup' => GoodMoralRequest::where('status', 'ready_for_pickup')->count(),
                'released'         => GoodMoralRequest::where('status', 'released')->count(),
            ],
        ]);
    }

    public function update(Request $request, GoodMoralRequest $goodMoral)
    {
        $request->validate([
            'status'      => 'required|in:pending,payment_verified,ready_for_pickup,released',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $goodMoral->update($request->only('status', 'admin_notes'));

        return back()->with('success', 'Request updated successfully.');
    }
}
