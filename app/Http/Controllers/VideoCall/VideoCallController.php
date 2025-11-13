<?php

namespace App\Http\Controllers\VideoCall;

use App\Http\Controllers\Controller;
use App\Events\RequestVideoCall;
use App\Events\RequestVideoCallStatus;
use App\Models\Appointments;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class VideoCallController extends Controller
{
    /**
     * Solicita uma videochamada para um usuário
     */
    public function requestVideoCall(Request $request, User $user)
    {
        try {
            // Validar requisição
            $validator = Validator::make($request->all(), [
                'peerId' => ['required', 'string', 'min:1', 'max:255'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $currentUser = Auth::user();

            // Verificar se o usuário não está tentando chamar a si mesmo
            if ($currentUser->id === $user->id) {
                return response()->json([
                    'message' => 'Você não pode iniciar uma chamada para si mesmo.',
                ], 400);
            }

            // Verificar se o usuário alvo existe
            if (!$user) {
                return response()->json([
                    'message' => 'Usuário não encontrado.',
                ], 404);
            }

            // Regra de negócio: apenas Patient pode chamar Doctor e vice-versa
            $currentUserIsDoctor = $currentUser->isDoctor();
            $currentUserIsPatient = $currentUser->isPatient();
            $targetUserIsDoctor = $user->isDoctor();
            $targetUserIsPatient = $user->isPatient();

            // Verificar se o usuário atual é Patient ou Doctor
            if (!$currentUserIsDoctor && !$currentUserIsPatient) {
                return response()->json([
                    'message' => 'Apenas pacientes e médicos podem realizar videochamadas.',
                ], 403);
            }

            // Verificar se o usuário alvo é Patient ou Doctor
            if (!$targetUserIsDoctor && !$targetUserIsPatient) {
                return response()->json([
                    'message' => 'Você só pode chamar pacientes ou médicos.',
                ], 403);
            }

            // Verificar se não são do mesmo tipo (Patient-Patient ou Doctor-Doctor não permitido)
            if (($currentUserIsDoctor && $targetUserIsDoctor) || 
                ($currentUserIsPatient && $targetUserIsPatient)) {
                return response()->json([
                    'message' => 'Videochamadas são permitidas apenas entre pacientes e médicos.',
                ], 403);
            }

            $appointmentValidation = $this->ensureActiveAppointment($currentUser, $user);
            if ($appointmentValidation instanceof JsonResponse) {
                return $appointmentValidation;
            }

            $user->peerId = $request->peerId;
            $user->fromUser = $currentUser;

            broadcast(new RequestVideoCall($user));

            return response()->json([
                'message' => 'Chamada solicitada com sucesso',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao processar solicitação de chamada',
                'error' => config('app.debug') ? $e->getMessage() : 'Erro interno do servidor',
            ], 500);
        }
    }

    /**
     * Envia o status de uma videochamada (aceita, rejeitada, etc.)
     */
    public function requestVideoCallStatus(Request $request, User $user)
    {
        try {
            // Validar requisição
            $validator = Validator::make($request->all(), [
                'peerId' => ['required', 'string', 'min:1', 'max:255'],
                'status' => ['sometimes', 'string', 'in:accept,reject'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $currentUser = Auth::user();

            // Verificar se o usuário não está tentando enviar status para si mesmo
            if ($currentUser->id === $user->id) {
                return response()->json([
                    'message' => 'Operação inválida.',
                ], 400);
            }

            // Verificar se o usuário alvo existe
            if (!$user) {
                return response()->json([
                    'message' => 'Usuário não encontrado.',
                ], 404);
            }

            // Regra de negócio: apenas Patient pode chamar Doctor e vice-versa
            $currentUserIsDoctor = $currentUser->isDoctor();
            $currentUserIsPatient = $currentUser->isPatient();
            $targetUserIsDoctor = $user->isDoctor();
            $targetUserIsPatient = $user->isPatient();

            // Verificar se o usuário atual é Patient ou Doctor
            if (!$currentUserIsDoctor && !$currentUserIsPatient) {
                return response()->json([
                    'message' => 'Apenas pacientes e médicos podem realizar videochamadas.',
                ], 403);
            }

            // Verificar se o usuário alvo é Patient ou Doctor
            if (!$targetUserIsDoctor && !$targetUserIsPatient) {
                return response()->json([
                    'message' => 'Você só pode chamar pacientes ou médicos.',
                ], 403);
            }

            // Verificar se não são do mesmo tipo (Patient-Patient ou Doctor-Doctor não permitido)
            if (($currentUserIsDoctor && $targetUserIsDoctor) || 
                ($currentUserIsPatient && $targetUserIsPatient)) {
                return response()->json([
                    'message' => 'Videochamadas são permitidas apenas entre pacientes e médicos.',
                ], 403);
            }

            $appointmentValidation = $this->ensureActiveAppointment($currentUser, $user);
            if ($appointmentValidation instanceof JsonResponse) {
                return $appointmentValidation;
            }

            $user->peerId = $request->peerId;
            $user->fromUser = $currentUser;

            broadcast(new RequestVideoCallStatus($user));

            return response()->json([
                'message' => 'Status da chamada enviado com sucesso',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao processar status da chamada',
                'error' => config('app.debug') ? $e->getMessage() : 'Erro interno do servidor',
            ], 500);
        }
    }

    private function ensureActiveAppointment(User $currentUser, User $targetUser): Appointments|JsonResponse
    {
        $currentUser->loadMissing('doctor', 'patient');
        $targetUser->loadMissing('doctor', 'patient');

        $currentUserIsDoctor = $currentUser->isDoctor();
        $currentUserIsPatient = $currentUser->isPatient();
        $targetUserIsDoctor = $targetUser->isDoctor();
        $targetUserIsPatient = $targetUser->isPatient();

        $doctorId = $currentUserIsDoctor
            ? optional($currentUser->doctor)->id
            : optional($targetUser->doctor)->id;

        $patientId = $currentUserIsPatient
            ? optional($currentUser->patient)->id
            : optional($targetUser->patient)->id;

        if (!$doctorId || !$patientId) {
            return response()->json([
                'message' => 'Não foi possível identificar o relacionamento médico/paciente.',
            ], 403);
        }

        $appointments = Appointments::where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->where('status', '!=', Appointments::STATUS_CANCELLED)
            ->orderByDesc('scheduled_at')
            ->get();

        if ($appointments->isEmpty()) {
            return response()->json([
                'message' => 'Nenhuma consulta encontrada entre os usuários.',
            ], 403);
        }

        $activeAppointment = $appointments->first(function (Appointments $appointment) {
            return $appointment->status === Appointments::STATUS_IN_PROGRESS;
        });

        if ($activeAppointment) {
            return $activeAppointment;
        }

        $leadMinutes = (int) config('telemedicine.appointment.lead_minutes', 10);
        $now = Carbon::now();

        $pendingAppointment = $appointments->first(function (Appointments $appointment) use ($currentUser, $now, $leadMinutes) {
            if (!in_array($appointment->status, [
                Appointments::STATUS_SCHEDULED,
                Appointments::STATUS_RESCHEDULED,
            ])) {
                return false;
            }

            $startWindow = $appointment->scheduled_at->copy()->subMinutes($leadMinutes);
            $endWindow = $appointment->scheduled_at->copy()->addMinutes($leadMinutes);

            if (!Gate::forUser($currentUser)->allows('start', $appointment)) {
                return false;
            }

            return $now->between($startWindow, $endWindow);
        });

        if ($pendingAppointment) {
            return response()->json([
                'message' => 'Inicie a consulta antes de iniciar a videochamada.',
                'appointment_id' => $pendingAppointment->id,
            ], 409);
        }

        return response()->json([
            'message' => 'Não há consultas ativas disponíveis para videochamada.',
        ], 403);
    }
}

