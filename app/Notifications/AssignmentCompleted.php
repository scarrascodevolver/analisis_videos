<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\VideoAssignment;

class AssignmentCompleted extends Notification implements ShouldQueue
{
    use Queueable;

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
                    ->subject('Análisis de Video Completado - Los Troncos')
                    ->greeting('¡Hola ' . $notifiable->name . '!')
                    ->line('El jugador ' . $this->assignment->player->name . ' ha completado el análisis del video asignado.')
                    ->line('**Video:** ' . $this->assignment->video->title)
                    ->line('**Jugador:** ' . $this->assignment->player->name)
                    ->line('**Autoevaluación:** ' . $this->assignment->self_evaluation . '/10')
                    ->line('**Fecha de completado:** ' . $this->assignment->completed_at->format('d/m/Y H:i'))
                    ->when($this->assignment->player_notes, function ($message) {
                        return $message->line('**Notas del jugador:** ' . substr($this->assignment->player_notes, 0, 100) . '...');
                    })
                    ->action('Ver Análisis Completo', url('/analyst/assignments/' . $this->assignment->id))
                    ->line('Puedes revisar el análisis detallado y proporcionar retroalimentación.');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'assignment_completed',
            'assignment_id' => $this->assignment->id,
            'video_id' => $this->assignment->video->id,
            'video_title' => $this->assignment->video->title,
            'player_name' => $this->assignment->player->name,
            'player_id' => $this->assignment->player->id,
            'self_evaluation' => $this->assignment->self_evaluation,
            'completed_at' => $this->assignment->completed_at->format('Y-m-d H:i:s'),
            'message' => $this->assignment->player->name . ' completó el análisis de "' . $this->assignment->video->title . '"'
        ];
    }
}