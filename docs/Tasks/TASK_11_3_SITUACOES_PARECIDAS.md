# Situações parecidas à T 11.3 (autorização em Services/Controllers)

Documento gerado a partir da varredura em Services e Controllers para padrões que deveriam usar Policies/Gates em vez de `abort(403)` ou checagens dentro de Services.

**Status:** As alterações listadas abaixo foram implementadas (Gate `manageDoctorSchedule`, uso de Policies nos controllers, MessagePolicy, ConversationPolicy::sendMessageInAppointment, MessageService e StoreMessageRequest).

---

## 1. Controllers com `abort(403)` que podem usar Policies/Gates

| Controller | Método(s) | Situação | Ação |
|------------|-----------|----------|------|
| **DoctorAvailabilitySlotController** | store, update, destroy | `auth()->user()->doctor->id !== $doctor->id` ou checagem no slot | Usar `$this->authorize('create', AvailabilitySlot::class)` + Gate `manageDoctorSchedule` para store; `$this->authorize('update'/'delete', $slot)` para update/destroy |
| **DoctorServiceLocationController** | store, update, destroy | Idem (doctor + location) | `authorize('create', ServiceLocation::class)` + Gate para store; `authorize('update'/'delete', $location)` para update/destroy |
| **DoctorBlockedDateController** | store, destroy | Idem (doctor + blockedDate) | Gate para store; `authorize('delete', $blockedDate)` para destroy |
| **DoctorScheduleController** | show, save | Só doctor | Gate `manageDoctorSchedule` |
| **DoctorConsultationDetailController** | show, start, saveDraft, finalize, complement, generatePdf | Vários `if ($appointment->doctor_id !== $user->doctor->id) abort(403)` | `authorize('view', $appointment)`, `authorize('start', $appointment)`, etc. + métodos na AppointmentPolicy (saveDraft, complement) |
| **PatientConsultationDetailsController** | show | Já tem `authorize('view', $appointment)`; depois há `abort(403)` redundante (patient) | Remover o segundo `abort` (Policy já garante) |

---

## 2. Services que fazem checagem de permissão (deveria ser antes do Service)

| Service | Método / trecho | Situação | Ação |
|---------|------------------|----------|------|
| **MessageService** | `validateAppointmentAccess()` | Lança "Você não tem permissão para enviar mensagens relacionadas a esta consulta" | Mover autorização para FormRequest/Controller (ex.: Gate `sendMessageInAppointment`); no Service manter só validação de negócio (appointment existe e envolve os dois usuários) ou remover o throw de permissão |

---

## 3. Controller com checagem manual que pode usar Policy

| Controller | Método | Situação | Ação |
|------------|--------|----------|------|
| **MessageController** | markAsDelivered | `if ($message->receiver_id !== auth()->id()) return 403` | Criar MessagePolicy::markAsDelivered e usar `$this->authorize('markAsDelivered', $message)` |

---

## 4. O que NÃO é autorização (sem mudança)

- **MedicalRecordService** — `throw new \RuntimeException('Paciente não está associado à consulta.')` em `issuePrescription`: regra de negócio (consistência paciente/consulta), não controle de acesso. Manter.
- **MedicalRecordService** — `resolveDoctorUser` lança "Usuário do médico não encontrado": erro interno, não autorização.
- **AvatarService** — `throw new \RuntimeException` sobre GD/Imagick: requisito de ambiente, não autorização.
- **ScheduleService::ensureDefaultAvailability** — Garantir disponibilidade padrão; não é guard de acesso.
- **AppointmentsObserver** — Exceção ao gerar access_code: erro de processo, não autorização.
- **Middleware** (EnsureUserIsDoctor, EnsureUserIsPatient) — Uso de `abort(403)` é adequado em middleware.
- **DoctorPatientMedicalRecordController** — `abort(403, 'Apenas médicos podem...')` antes de `authorize('view', $patient)`: reforça que a rota é só para médico; pode ficar ou ser substituída por middleware na rota.
- **Patient*** / **MedicalRecordDocumentController** — `abort(403, 'Perfil de paciente não encontrado')`: checagem de perfil; pode ser middleware na rota ou manter.

---

## 5. Resumo das implementações sugeridas

1. **Gate `manageDoctorSchedule`** — `(User $user, Doctor $doctor) => $user->doctor && (string)$user->doctor->id === (string)$doctor->id` — usar em rotas que recebem `Doctor $doctor` e exigem “sou este médico”.
2. **DoctorAvailabilitySlotController** — Usar Gate para store; `authorize('update'/'delete', $slot)` para update/destroy.
3. **DoctorServiceLocationController** — Idem (Gate + authorize no model).
4. **DoctorBlockedDateController** — Idem.
5. **DoctorScheduleController** — Só Gate `manageDoctorSchedule`.
6. **DoctorConsultationDetailController** — Substituir todos os `abort(403)` por `authorize(...)` usando AppointmentPolicy (view, start, end); adicionar métodos `saveDraft` e `complement` na AppointmentPolicy.
7. **PatientConsultationDetailsController** — Remover `abort(403)` redundante após `authorize('view', $appointment)`.
8. **MessagePolicy** — Criar e registrar; método `markAsDelivered(User, Message)`. Usar em MessageController::markAsDelivered.
9. **MessageService** — Em `validateAppointmentAccess`, deixar apenas validação de negócio (appointment existe, doctor_id e patient_id corretos); autorização “pode enviar mensagem nesta consulta” no FormRequest (Gate `sendMessageInAppointment` ou similar) quando houver `appointment_id`.

---

## Referência

- [TASK_11_GOVERNANCA_BACKEND.md](./TASK_11_GOVERNANCA_BACKEND.md) — T 11.3 Policies + Broadcast
