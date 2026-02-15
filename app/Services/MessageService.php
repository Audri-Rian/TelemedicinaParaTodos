<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Appointments;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MessageService
{
    /**
     * Enviar uma mensagem
     */
    public function sendMessage(string $receiverId, string $content, ?string $appointmentId = null): Message
    {
        $senderId = Auth::id();

        // Validar se há appointment entre os usuários (se appointment_id for fornecido)
        if ($appointmentId) {
            $this->validateAppointmentAccess($senderId, $receiverId, $appointmentId);
        } else {
            // Validar se há pelo menos um appointment entre os usuários
            $this->validateUsersCanMessage($senderId, $receiverId);
        }

        $message = Message::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'content' => $content,
            'appointment_id' => $appointmentId,
            'status' => Message::STATUS_SENT, // Status inicial: sent (aguardando confirmação de entrega)
        ]);

        $message->load(['sender', 'receiver']);

        // Disparar evento de broadcasting
        event(new \App\Events\MessageSent($message));

        return $message;
    }

    /**
     * Buscar mensagens entre dois usuários
     * Usa paginação reversa otimizada (padrão Slack)
     */
    public function getMessagesBetweenUsers(string $otherUserId, ?int $limit = null, ?string $beforeMessageId = null)
    {
        $limit = $limit ?? (int) config('telemedicine.messages.default_page_limit', 50);
        $currentUserId = Auth::id();

        $query = Message::betweenUsers($currentUserId, $otherUserId)
            ->with(['sender:id,name,avatar_path', 'receiver:id,name,avatar_path'])
            ->orderBy('created_at', 'desc'); // Ordenar DESC para pegar mais recentes primeiro

        if ($beforeMessageId) {
            // Para paginação: buscar mensagens anteriores a esta
            $beforeMessage = Message::find($beforeMessageId);
            if ($beforeMessage) {
                $query->where('created_at', '<', $beforeMessage->created_at)
                      ->orWhere(function ($q) use ($beforeMessage) {
                          $q->where('created_at', '=', $beforeMessage->created_at)
                            ->where('id', '<', $beforeMessage->id);
                      });
            }
        }

        // Buscar limit + 1 para verificar se há mais mensagens
        $messages = $query->limit($limit + 1)->get();
        
        // Reverter ordem para exibir do mais antigo ao mais recente no frontend
        return $messages->reverse()->values();
    }

    /**
     * Buscar conversas do usuário atual
     * Inclui todos os médicos/pacientes com appointments, mesmo sem mensagens
     */
    public function getConversations()
    {
        $userId = Auth::id();
        $user = User::with(['doctor', 'patient'])->find($userId);

        if (!$user) {
            return [];
        }

        // Determinar se é médico ou paciente
        $isDoctor = $user->doctor !== null;
        $isPatient = $user->patient !== null;

        if (!$isDoctor && !$isPatient) {
            return [];
        }

        // Buscar appointments do usuário
        $appointments = Appointments::where(function ($query) use ($user, $isDoctor) {
                if ($isDoctor) {
                    $query->where('doctor_id', $user->doctor->id);
                } else {
                    $query->where('patient_id', $user->patient->id);
                }
            })
            ->with([
                'doctor' => function ($query) {
                    $query->with('user:id,name,avatar_path');
                },
                'patient' => function ($query) {
                    $query->with('user:id,name,avatar_path');
                }
            ])
            ->get();

        // Criar mapa de conversas baseado em appointments
        $conversationsMap = [];
        foreach ($appointments as $appointment) {
            $otherUser = $isDoctor 
                ? $appointment->patient->user 
                : $appointment->doctor->user;

            if (!$otherUser) {
                continue;
            }

            $otherUserId = $otherUser->id;

            // Se já existe conversa com este usuário, pular (vamos usar o appointment mais recente)
            if (isset($conversationsMap[$otherUserId])) {
                // Manter o appointment mais recente
                $existingAppointment = $conversationsMap[$otherUserId]['appointment'];
                if ($appointment->created_at > $existingAppointment->created_at) {
                    $conversationsMap[$otherUserId]['appointment'] = $appointment;
                }
                continue;
            }

            $conversationsMap[$otherUserId] = [
                'user' => $otherUser,
                'appointment' => $appointment,
            ];
        }

        // Buscar última mensagem de cada conversa (se houver)
        $allMessages = Message::where(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                      ->orWhere('receiver_id', $userId);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($message) use ($userId) {
                return $message->sender_id === $userId 
                    ? $message->receiver_id 
                    : $message->sender_id;
            });

        // Montar resultado final
        $result = [];
        foreach ($conversationsMap as $otherUserId => $data) {
            $otherUser = $data['user'];
            
            // Buscar última mensagem (se houver)
            $lastMessage = $allMessages->get($otherUserId)?->first();
            
            // Contar mensagens não lidas
            $unreadCount = Message::unreadFor($userId)
                ->where('sender_id', $otherUserId)
                ->where('receiver_id', $userId)
                ->count();

            $result[] = [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'avatar' => $otherUser->avatar_path,
                'lastMessage' => $lastMessage ? $lastMessage->content : 'Nenhuma mensagem ainda',
                'lastMessageTime' => $lastMessage 
                    ? $lastMessage->created_at->format('c') 
                    : $data['appointment']->created_at->format('c'),
                'unread' => $unreadCount,
            ];
        }

        // Ordenar por última mensagem ou appointment (mais recente primeiro)
        usort($result, function ($a, $b) {
            $timeA = strtotime($a['lastMessageTime']);
            $timeB = strtotime($b['lastMessageTime']);
            return $timeB - $timeA;
        });

        return $result;
    }

    /**
     * Marcar mensagens como lidas
     */
    public function markMessagesAsRead(string $otherUserId): int
    {
        $userId = Auth::id();

        return Message::where('sender_id', $otherUserId)
            ->where('receiver_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);
    }

    /**
     * Contar mensagens não lidas
     */
    public function getUnreadCount(): int
    {
        return Message::unreadFor(Auth::id())->count();
    }

    /**
     * Validar se os usuários podem trocar mensagens (devem ter pelo menos um appointment)
     */
    protected function validateUsersCanMessage(string $userId1, string $userId2): void
    {
        // Buscar se há appointment entre os dois usuários
        $user1 = User::with(['doctor', 'patient'])->find($userId1);
        $user2 = User::with(['doctor', 'patient'])->find($userId2);

        if (!$user1 || !$user2) {
            throw new \Exception('Usuário não encontrado');
        }

        // Determinar qual é médico e qual é paciente
        $doctor = null;
        $patient = null;

        if ($user1->doctor) {
            $doctor = $user1->doctor;
            $patient = $user2->patient;
        } elseif ($user2->doctor) {
            $doctor = $user2->doctor;
            $patient = $user1->patient;
        }

        if (!$doctor || !$patient) {
            throw new \Exception('Apenas médicos e pacientes podem trocar mensagens');
        }

        // Verificar se há pelo menos um appointment (qualquer status - apenas precisa ter relação)
        $hasAppointment = Appointments::where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->exists();

        if (!$hasAppointment) {
            throw new \Exception('Você só pode enviar mensagens para médicos/pacientes com quem teve ou tem consultas');
        }
    }

    /**
     * Validar acesso ao appointment
     */
    protected function validateAppointmentAccess(string $userId1, string $userId2, string $appointmentId): void
    {
        $appointment = Appointments::find($appointmentId);

        if (!$appointment) {
            throw new \Exception('Consulta não encontrada');
        }

        $user1 = User::with(['doctor', 'patient'])->find($userId1);
        $user2 = User::with(['doctor', 'patient'])->find($userId2);

        if (!$user1 || !$user2) {
            throw new \Exception('Usuário não encontrado');
        }

        // Verificar se os usuários estão relacionados ao appointment
        $user1IsDoctor = $user1->doctor && $user1->doctor->id === $appointment->doctor_id;
        $user1IsPatient = $user1->patient && $user1->patient->id === $appointment->patient_id;
        $user2IsDoctor = $user2->doctor && $user2->doctor->id === $appointment->doctor_id;
        $user2IsPatient = $user2->patient && $user2->patient->id === $appointment->patient_id;

        if (!(($user1IsDoctor && $user2IsPatient) || ($user1IsPatient && $user2IsDoctor))) {
            throw new \Exception('Consulta inválida ou não relacionada aos participantes da mensagem.');
        }
    }
}

