<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\HtmlString;
use App\Models\User;

class EmailVerification extends Notification
{
    use Queueable;

    protected $user;
    protected $url;
    /**
     * Create a new notification instance.
     */
    public function __construct(User $user)
    {
        //
        $this->url = env('FRONTEND_URL', 'http://localhost:5173') . '/password/reset/';
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $salutation = Lang::get("Thank You,<br/>Geebu Support");
        return (new MailMessage)
            ->subject(Lang::get('Email Verification'))
            ->greeting('Dear ' . $this->user->first_name)
            ->line(Lang::get("Thank you for registering with Geebu. To ensure the security of your account and access to all our services, we kindly request you to verify your email address."))
            ->line(Lang::get("Click on the verification link below or copy and paste it into your web browser"))
            ->action(Lang::get('Verify Account'), $this->url)
            ->line(Lang::get("If you did not create an account on our platform, please ignore this email."))
            ->salutation(new HtmlString($salutation));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
