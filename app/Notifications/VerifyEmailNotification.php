<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;

class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable, SerializesModels;
    
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;
    
    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 60;
    
    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        // Set queue name for email notifications
        $this->onQueue('emails');
        // Small delay to ensure transaction is committed
        $this->delay(now()->addSeconds(5));
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verifikasi Email - Global Photo Rental')
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Terima kasih telah mendaftar di Global Photo Rental.')
            ->line('Silakan klik tombol di bawah untuk memverifikasi email Anda:')
            ->action('Verifikasi Email', $verificationUrl)
            ->line('Jika Anda tidak membuat akun, abaikan email ini.')
            ->line('Link verifikasi ini akan kedaluwarsa dalam 60 menit.')
            ->salutation('Salam,\nTim Global Photo Rental');
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
    
    /**
     * Determine which connections the job should run on.
     *
     * @return array
     */
    public function viaConnections(): array
    {
        return [
            'mail' => 'database',
        ];
    }
    
    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Email verification notification failed', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
