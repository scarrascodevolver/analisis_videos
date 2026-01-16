<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\VideoAssignment;

class VideoAssigned extends Notification
{
    protected $assignment;

    public function __construct(VideoAssignment $assignment)
    {
        $this->assignment = $assignment;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Nuevo Video Asignado para Análisis - Los Troncos')
                    ->greeting('¡Hola ' . $notifiable->name . '!')
                    ->line('Se te ha asignado un nuevo video para análisis.')
                    ->line('**Video:** ' . $this->assignment->video->title)
                    ->line('**Equipos:** ' . $this->assignment->video->analyzed_team_name .
                           ($this->assignment->video->rival_team_name ? ' vs ' . $this->assignment->video->rival_team_name : ''))
                    ->line('**Categoría:** ' . $this->assignment->video->category->name)
                    ->line('**Prioridad:** ' . ucfirst($this->assignment->priority))
                    ->line('**Fecha límite:** ' . $this->assignment->due_date->format('d/m/Y'))
                    ->when($this->assignment->instructions, function ($message) {
                        return $message->line('**Instrucciones:** ' . $this->assignment->instructions);
                    })
                    ->action('Ver Video', url('/videos/' . $this->assignment->video->id))
                    ->line('¡Gracias por formar parte del equipo Los Troncos!');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'video_assigned',
            'assignment_id' => $this->assignment->id,
            'video_id' => $this->assignment->video->id,
            'video_title' => $this->assignment->video->title,
            'analyst_name' => $this->assignment->analyst->name,
            'priority' => $this->assignment->priority,
            'due_date' => $this->assignment->due_date->format('Y-m-d'),
            'message' => 'Se te ha asignado el video "' . $this->assignment->video->title . '" para análisis'
        ];
    }
}