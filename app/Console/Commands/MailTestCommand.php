<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTestCommand extends Command
{
    protected $signature = 'mail:test {email : Address to send the test message to}';

    protected $description = 'Send a plain-text test email using the active mail configuration';

    public function handle(): int
    {
        $mailer = (string) config('mail.default');
        $to = (string) $this->argument('email');
        $fromName = config('mail.from.name');

        $fromAddress = config('mail.from.address');
        if (blank($fromAddress)) {
            $fromAddress = env('MAIL_USERNAME');
        }

        if (blank($fromAddress)) {
            $this->components->error('Set MAIL_FROM_ADDRESS and MAIL_USERNAME in .env to your Gmail address (the same account as your App Password), then run: php artisan config:clear');

            return self::FAILURE;
        }

        $body = implode("\n", [
            'CapTrack mail configuration test.',
            '',
            'Mailer: '.$mailer,
            'From: '.$fromName.' <'.$fromAddress.'>',
            'Sent at: '.now()->toIso8601String(),
            '',
            'If you received this, SMTP (or your configured transport) is working.',
        ]);

        try {
            Mail::raw($body, function ($message) use ($to, $fromAddress, $fromName) {
                $message->from($fromAddress, $fromName)
                    ->to($to)
                    ->subject(config('app.name').': mail test');
            });
        } catch (\Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Message sent to {$to} via [{$mailer}].");

        if ($mailer === 'log') {
            $this->components->warn('MAIL_MAILER is "log"; check storage/logs/laravel.log for the message body, not your inbox.');
        }

        return self::SUCCESS;
    }
}
