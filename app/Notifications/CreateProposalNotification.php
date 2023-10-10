<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CreateProposalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $proposal;

    /**
     * Create a new notification instance.
     */
    public function __construct($proposal)
    {
        $this->proposal = $proposal;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database',];
    }

    // /**
    //  * Get the mail representation of the notification.
    //  */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {

        return [
            'proposal' => $this->proposal->id,
            'artist' => $this->proposal->artist->profile->business_name,
            'organizer' => $this->proposal->event->organizer->profile->id,
        ];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => 'You have been added to the studio'
        ];
    }
}
