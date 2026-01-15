<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class JugadasController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the plays editor.
     */
    public function index()
    {
        return view('jugadas.index');
    }
}
