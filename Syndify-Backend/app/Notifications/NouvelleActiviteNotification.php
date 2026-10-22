<?php

namespace App\Http\Controllers\Mobile;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NouvelleActiviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $titre;
    public $messageText;
    public $type;

    public function __construct($titre, $messageText, $type)
    {
        $this->titre = $titre;
        $this->messageText = $messageText;
        $this->type = $type;
    }

    // 1. Fin kat-stocker (Base de données) bash tban f Ecran Notifications
    public function via($notifiable)
    {
        return ['database']; // T9dri tzidi m3aha FCM la bghiti
    }

    public function toArray($notifiable)
    {
        return [
            'titre' => $this->titre,
            'message' => $this->messageText,
            'type' => $this->type,
        ];
    }
}