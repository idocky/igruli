<?php

namespace App\Http\Controllers;

use App\Models\Lobby;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'lobbies' => Lobby::query()->latest()->limit(20)->get(['id', 'title', 'code', 'created_at']),
        ]);
    }
}
