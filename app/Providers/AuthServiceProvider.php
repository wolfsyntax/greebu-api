<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\HtmlString;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        // Passport::personalAccessTokensExpireIn(now()->addMonths(6));
        Passport::personalAccessTokensExpireIn(now()->addDays(1));
        // Passport::personalAccessTokensExpireIn(now()->addMinutes(10));

        // $salutation = Lang::get("Thank You,<br/>Geebu Support");

        // VerifyEmail::toMailUsing(function (object $notifiable, string $url) use ($salutation) {

        //     return (new MailMessage)
        //         ->subject(Lang::get('Email Verification'))
        //         ->line(Lang::get("Thank you for registering with Geebu. To ensure the security of your account and access to all our services, we kindly request you to verify your email address."))
        //         ->line(Lang::get("Click on the verification link below or copy and paste it into your web browser"))
        //         ->action(Lang::get('Verify Account'), $url)
        //         ->line(Lang::get("If you did not create an account on our platform, please ignore this email."))
        //         ->salutation(new HtmlString($salutation));
        // });
    }
}
