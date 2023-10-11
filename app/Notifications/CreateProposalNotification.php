<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Libraries\AwsService;

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
            'event'     => $this->proposal->event,
            'proposal' => $this->proposal->id,
            'artist' => $this->proposal->artist->profile->business_name,
            'organizer' => $this->proposal->event->organizer->profile->id,
        ];
    }

    public function toDatabase($notifiable)
    {
        $event = $this->proposal->event;
        $artist_profile = $this->proposal->artist->profile;
        $avatar = $artist_profile->avatar;
        if (!$avatar) {
            $avatar = 'https://ui-avatars.com/api/?name=' . substr($artist_profile->business_name, '', 0, 1) . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        } else {
            $service = new AwsService();
            $avatar_host = parse_url($avatar);
            if (!array_key_exists('host', $avatar_host)) {
                $avatar = $service->get_aws_object($avatar);
            }
        }
        return [
            'header' => 'has submitted a proposal for your event',
            'sender_name' => $artist_profile->business_name,
            'sender_avatar' => $avatar,
            'sender_id' => $artist_profile->id,
            'time' => $this->proposal->created_at,
            'body' => 'Click below to review the details',
            'notification_type' => 'artist-proposal',
            'can_view' => true,
            'misc' => [
                'id'            => $this->proposal->id,
                'event_name' => $event->event_name,
            ]
        ];
    }
}
