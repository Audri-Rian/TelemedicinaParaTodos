<?php

namespace App\Observers;

use App\Models\Appointments;
use Carbon\Carbon;

class AppointmentsObserver
{
    /**
     * Handle the Appointments "creating" event.
     */
    public function creating(Appointments $appointment): void
    {
        if (!$appointment->access_code) {
            $appointment->access_code = self::generateUniqueAccessCode();
        }

        if (!$appointment->status) {
            $appointment->status = Appointments::STATUS_SCHEDULED;
        }
    }

    private static function generateUniqueAccessCode(): string
    {
        $code = strtoupper(substr(md5(uniqid()), 0, 8));
        while (Appointments::where('access_code', $code)->exists()) {
            $code = strtoupper(substr(md5(uniqid()), 0, 8));
        }
        return $code;
    }
}


