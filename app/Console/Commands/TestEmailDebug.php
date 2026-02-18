<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\GeneralMail;

class TestEmailDebug extends Command
{
    protected $signature = 'email:debug {email=jamilubashira@gmail.com}';
    protected $description = 'Test email with detailed debugging';

    public function handle()
    {
        $recipient = $this->argument('email');

        $this->info("Testing email to: {$recipient}");
        $this->info("SMTP Host: " . config('mail.mailers.smtp.host'));
        $this->info("SMTP Port: " . config('mail.mailers.smtp.port'));
        $this->info("SMTP User: " . config('mail.mailers.smtp.username'));
        $this->info("From Address: " . config('mail.from.address'));

        try {
            // Simple test email
            Mail::raw('This is a simple test email from KoboPoint. If you receive this, the email system is working!', function ($message) use ($recipient) {
                $message->to($recipient)
                    ->subject('Test Email from KoboPoint - ' . now());
            });

            $this->info("✅ Email sent successfully!");
            $this->info("Please check: {$recipient}");
            $this->info("Also check: Spam folder, Promotions tab");

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Failed to send email!");
            $this->error("Error: " . $e->getMessage());
            $this->error("\nFull trace:");
            $this->error($e->getTraceAsString());

            return 1;
        }
    }
}
