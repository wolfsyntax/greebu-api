<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\HtmlString;
use App\Models\User;

class ForgotPass extends Notification
{
    use Queueable;
    /**
     * @var string
     */
    protected $token;
    /** @var string */
    protected $url;
    /** @var \App\Models\User */
    protected $user;
    /**
     * Create a new notification instance.
     */
    public function __construct(string $token, User $user)
    {
        //
        $this->url = env('FRONTEND_URL', 'http://localhost:5173') . '/password/reset/' . $token;
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
            ->subject(Lang::get('Reset Password Notification'))
            ->greeting('Dear ' . $this->user->first_name)
            ->line(Lang::get("You've requested a password reset for your Geebu  account. Click the link below to set a new password securely:"))
            ->action(Lang::get('Reset Password'), $this->url)
            ->line(Lang::get("If you didn't make this request, please contact us immediately at [Support Email or Phone Number]."))
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
