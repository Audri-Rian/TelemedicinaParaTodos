<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Services\MessageService;
use Inertia\Inertia;
use Inertia\Response;

class DoctorMessagesController extends Controller
{
    public function __construct(
        protected MessageService $messageService
    ) {}

    /**
     * Display the doctor's messages page.
     */
    public function index(): Response
    {
        try {
            $conversations = $this->messageService->getConversations();
            
            return Inertia::render('Doctor/Messages', [
                'conversations' => $conversations,
            ]);
        } catch (\Exception $e) {
            // Em caso de erro, retornar array vazio
            return Inertia::render('Doctor/Messages', [
                'conversations' => [],
            ]);
        }
    }
}
