<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\Rapport;
use App\Models\Validation;

class RapportAssignedForValidation extends Notification
{
    use Queueable;

    protected Rapport $rapport;
    protected Validation $validation;

    public function __construct(Rapport $rapport, Validation $validation)
    {
        $this->rapport = $rapport;
        $this->validation = $validation;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => 'Un nouveau rapport vous a été assigné pour validation.',
            'rapport_id' => $this->rapport->id,
            'validation_id' => $this->validation->id,
            'type_rapport' => $this->rapport->typeRapport?->name,
            'mission_id' => $this->rapport->mission_id,
        ];
    }
}
