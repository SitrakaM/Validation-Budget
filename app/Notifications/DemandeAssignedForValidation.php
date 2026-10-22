<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\Demande;
use App\Models\Validation;

class DemandeAssignedForValidation extends Notification
{
    use Queueable;

    protected Demande $demande;
    protected Validation $validation;

    public function __construct(Demande $demande, Validation $validation)
    {
        $this->demande = $demande;
        $this->validation = $validation;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => 'Une nouvelle demande vous a été assignée pour validation.',
            'demande_id' => $this->demande->id,
            'validation_id' => $this->validation->id,
            'type_demande' => $this->demande->typeDemande?->name,
            'mission_id' => $this->demande->mission_id,
        ];
    }
}
