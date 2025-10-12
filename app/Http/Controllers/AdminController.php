<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Team;
use App\Models\RugbySituation;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Dashboard principal del Mantenedor
     */
    public function index()
    {
        // Contadores simples para las tarjetas
        $stats = [
            'categories' => Category::count(),
            'teams' => Team::count(),
            'situations' => RugbySituation::count(),
            'users' => User::count(),
        ];

        return view('admin.index', compact('stats'));
    }
}
