<?php

namespace App\Notifications;

use App\Models\Video;
use App\Models\VideoComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VideoCommentMention extends Notification implements ShouldQueue
{
    use Queueable;

    public $video;
    public $comment;
    public $mentionedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(Video $video, VideoComment $comment, User $mentionedBy)
    {
        $this->video = $video;
        $this->comment = $comment;
        $this->mentionedBy = $mentionedBy;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $roleLabel = $this->mentionedBy->role === 'analista' ? 'Analista' :
                    ($this->mentionedBy->role === 'entrenador' ? 'Entrenador' : 'Jugador');

        return (new MailMessage)
            ->subject('Te mencionaron en un comentario de video')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line("El {$roleLabel} **{$this->mentionedBy->name}** te mencionó en un comentario del video:")
            ->line('**' . $this->video->title . '**')
            ->line('Comentario:')
            ->line('"' . $this->comment->comment . '"')
            ->action('Ver Video', route('videos.show', $this->video->id))
            ->line('Gracias por usar el Sistema de Análisis Rugby Los Troncos.');
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'video_id' => $this->video->id,
            'video_title' => $this->video->title,
            'comment_id' => $this->comment->id,
            'comment_text' => $this->comment->comment,
            'mentioned_by_id' => $this->mentionedBy->id,
            'mentioned_by_name' => $this->mentionedBy->name,
            'mentioned_by_role' => $this->mentionedBy->role,
            'timestamp_seconds' => $this->comment->timestamp_seconds,
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
