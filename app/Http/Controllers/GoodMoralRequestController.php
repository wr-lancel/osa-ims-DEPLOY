<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGoodMoralCertificateRequest;
use App\Models\GoodMoralRequest;
use Inertia\Inertia;
use Inertia\Response;

class GoodMoralRequestController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('GoodMoral/Request');
    }

    public function store(StoreGoodMoralCertificateRequest $request)
    {
        GoodMoralRequest::create($request->validated());

        return redirect()->route('good-moral.create')
            ->with('success', 'Your request has been submitted successfully. Please proceed to the CHCC Cashier to pay the processing fee, then pick up your certificate at the OSA Office.');
    }
}
