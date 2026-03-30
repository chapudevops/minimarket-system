<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the dashboard index page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Aquí puedes pasar datos a la vista
        $data = [
            'pageTitle' => 'Dashboard',
            'user' => auth()->user(),
            // Agrega más datos que necesites
        ];
        
        return view('dashboard.index', $data);
    }
    
    /**
     * Show the dashboard with additional data if needed
     *
     * @return \Illuminate\View\View
     */
    public function home()
    {
        // Puedes crear diferentes métodos para diferentes secciones del dashboard
        return view('dashboard.index');
    }
}