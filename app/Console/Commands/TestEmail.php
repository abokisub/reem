<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\GeneralMail;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {type=welcome}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send test emails using the master template';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $type = $this->argument('type');
        $recipient = 'jamilubashira@gmail.com';

        $data = $this->getEmailData($type);

        try {
            Mail::to($recipient)->send(new GeneralMail($data));
            $this->info("✅ {$type} email sent successfully to {$recipient}");
            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Failed to send email: " . $e->getMessage());
            return 1;
        }
    }

    private function getEmailData($type)
    {
        switch ($type) {
            case 'welcome':
                return [
                    'subject' => 'Welcome to KoboPoint',
                    'title' => 'Welcome to KoboPoint',
                    'user_name' => 'Jamilu',
                    'message_body' => 'Thank you for joining KoboPoint Payment Gateway. We are excited to have you on board. Your account has been successfully created and you can now start using our secure payment infrastructure.',
                    'button_text' => 'Login to Dashboard',
                    'button_link' => 'https://kobopoint.com/auth/login',
                ];

            case 'otp':
                return [
                    'subject' => 'Your Secure OTP Code',
                    'title' => 'Your Secure OTP Code',
                    'user_name' => 'Jamilu',
                    'message_body' => 'Please use the following One-Time Password to complete your authentication. This code will expire in 5 minutes.',
                    'highlight_label' => 'One-Time Password',
                    'highlight_value' => '482913',
                ];

            case 'transaction':
                return [
                    'subject' => 'Credit Alert - ₦50,000',
                    'title' => 'Credit Alert',
                    'user_name' => 'Jamilu',
                    'message_body' => 'Your account has been credited successfully.',
                    'highlight_label' => 'Amount',
                    'highlight_value' => '₦50,000',
                    'details_rows' => '
                        <tr>
                            <td style="padding:6px 0; font-size:13px; color:#555;">Reference:</td>
                            <td align="right" style="padding:6px 0; font-size:13px; font-weight:600;">TXN-88493</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0; font-size:13px; color:#555;">Type:</td>
                            <td align="right" style="padding:6px 0; font-size:13px; font-weight:600;">Bank Transfer</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0; font-size:13px; color:#555;">Status:</td>
                            <td align="right" style="padding:6px 0; font-size:13px; font-weight:600; color:#10B981;">Successful</td>
                        </tr>
                    ',
                ];

            case 'kyc_rejected':
                return [
                    'subject' => 'KYC Document Rejected',
                    'title' => 'KYC Document Rejected',
                    'user_name' => 'Jamilu',
                    'message_body' => 'We regret to inform you that your KYC document has been rejected. Please review the reason below and re-upload the correct document.',
                    'highlight_label' => 'Document',
                    'highlight_value' => 'Utility Bill',
                    'details_rows' => '
                        <tr>
                            <td style="padding:6px 0; font-size:13px; color:#555;">Reason:</td>
                            <td align="right" style="padding:6px 0; font-size:13px; font-weight:600; color:#EF4444;">Document must not be older than 3 months</td>
                        </tr>
                    ',
                    'button_text' => 'Re-upload Document',
                    'button_link' => 'https://kobopoint.com/dashboard/fund/update-kyc',
                ];

            case 'api_approved':
                return [
                    'subject' => 'API Access Approved',
                    'title' => 'API Access Approved',
                    'user_name' => 'Jamilu',
                    'message_body' => 'Congratulations! Your company has been verified and your API access has been approved. You can now start integrating with our payment gateway.',
                    'highlight_label' => 'Environment',
                    'highlight_value' => 'Live',
                    'details_rows' => '
                        <tr>
                            <td style="padding:6px 0; font-size:13px; color:#555;">Status:</td>
                            <td align="right" style="padding:6px 0; font-size:13px; font-weight:600; color:#10B981;">Active</td>
                        </tr>
                    ',
                    'button_text' => 'View API Keys',
                    'button_link' => 'https://kobopoint.com/dashboard/developer',
                ];

            default:
                return [
                    'subject' => 'Test Email',
                    'title' => 'Test Email',
                    'user_name' => 'User',
                    'message_body' => 'This is a test email from the master template.',
                ];
        }
    }
}
