<?php

namespace App\Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * Email xác thực — link signed URL có expire.
 *
 * Khi MAIL_MAILER=log (mặc định dev), mail sẽ ghi vào storage/logs/laravel.log
 * thay vì gửi thật → dev test được flow mà không cần SMTP.
 */
class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiresAt = Carbon::now()->addHours(24);
        $signedUrl = URL::temporarySignedRoute(
            'auth.verify-email',
            $expiresAt,
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->email)]
        );

        // FE URL: gửi link FE để render trang đẹp; FE sẽ POST signed URL về API.
        $feBase = rtrim((string) (config('app.frontend_url') ?: env('FRONTEND_URL', 'http://localhost:3000')), '/');
        $feLink = $feBase.'/verify-email?'.parse_url($signedUrl, PHP_URL_QUERY).'&path='.urlencode(parse_url($signedUrl, PHP_URL_PATH) ?? '');

        return (new MailMessage)
            ->subject('Xác thực email YukiMart')
            ->greeting('Xin chào '.($notifiable->name ?? 'bạn'))
            ->line('Vui lòng xác thực email để hoàn tất đăng ký tài khoản.')
            ->action('Xác thực email', $feLink)
            ->line('Link sẽ hết hạn sau 24 giờ.')
            ->line('Nếu bạn không đăng ký, bỏ qua email này.');
    }
}
