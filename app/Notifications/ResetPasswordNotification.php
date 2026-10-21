<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = url('/password/reset?token='.$this->token);

        return (new MailMessage)
            ->subject('Permintaan Reset Kata Sandi — SISURAT Disdikbudpora Kab. Semarang')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Kami menerima permintaan pemulihan kata sandi untuk akun SISURAT Anda.')
            ->line('Silakan klik tombol di bawah ini untuk mengatur kata sandi baru akun Anda:')
            ->action('Atur Kata Sandi Baru', $resetUrl)
            ->line('Tautan pemulihan kata sandi ini hanya berlaku selama 24 jam.')
            ->line('Jika Anda tidak merasa mengajukan permintaan ini, abaikan email ini atau segera hubungi Administrator SIM Disdikbudpora Kab. Semarang.')
            ->salutation("Salam hangat,\nTim Pengelola SIM Disdikbudpora Kab. Semarang");
    }
}
