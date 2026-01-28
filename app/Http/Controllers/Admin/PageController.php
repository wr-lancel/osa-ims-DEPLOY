<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function discipline(): Response
    {
        return Inertia::render('Admin/Discipline/Index');
    }

    public function organizations(): Response
    {
        return Inertia::render('Admin/Organizations/Index');
    }

    public function sports(): Response
    {
        return Inertia::render('Admin/Sports/Index');
    }
}
