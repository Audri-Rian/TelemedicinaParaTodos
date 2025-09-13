<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User;
use Illuminate\Http\Request;

class ConsultationsController extends Controller
{
    public function index()
    {
        // Buscar todos os usuários exceto o usuário logado
        $users = User::where('id', '!=', auth()->id())->get(['id', 'name', 'email']);
        
        return Inertia::render('Consultations', [
            'users' => $users
        ]);
    }
}
