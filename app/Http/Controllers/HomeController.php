<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Redirect to videos list instead of dashboard.
     */
    public function index()
    {
        // Redirigir directamente a la lista de videos
        return redirect()->route('videos.index');
    }
}
