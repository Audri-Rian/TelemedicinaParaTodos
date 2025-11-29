<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Services\MessageService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class PatientMessagesController extends Controller
{
    public function __construct(
        protected MessageService $messageService
    ) {}

    /**
     * Display the patient's messages page.
     */
    public function index(): Response
    {
        try {
            $conversations = $this->messageService->getConversations();
            
            return Inertia::render('Patient/Messages', [
                'conversations' => $conversations,
            ]);
        } catch (\Exception $e) {
            // Em caso de erro, retornar array vazio
            return Inertia::render('Patient/Messages', [
                'conversations' => [],
            ]);
        }
    }
}
