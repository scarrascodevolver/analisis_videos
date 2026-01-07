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
        // Obtener organización actual del usuario
        $currentOrg = auth()->user()->currentOrganization();

        // Contadores simples para las tarjetas (filtrados por organización)
        $stats = [
            'categories' => Category::count(),
            'teams' => Team::count(),
            'situations' => RugbySituation::count(),
            'users' => $currentOrg ? $currentOrg->users()->count() : 0,
        ];

        return view('admin.index', compact('stats'));
    }
}
