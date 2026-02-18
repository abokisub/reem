# Email Testing Setup Guide

## Current Issue
Emails are being sent from Laravel but not reaching Gmail inbox. This is likely due to:
- Email authentication issues (SPF/DKIM)
- Gmail blocking the sender domain
- SMTP server configuration

## Solution: Use Mailtrap for Testing

Mailtrap is a free email testing service that captures all emails sent from your application.

### Setup Instructions:

1. **Create Mailtrap Account** (Free):
   - Go to https://mailtrap.io
   - Sign up for a free account
   - Navigate to "Email Testing" → "Inboxes"
   - Click on your inbox

2. **Get SMTP Credentials**:
   - In your Mailtrap inbox, click "Show Credentials"
   - Copy the SMTP settings

3. **Update `.env` file**:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=sandbox.smtp.mailtrap.io
   MAIL_PORT=2525
   MAIL_USERNAME=<your_mailtrap_username>
   MAIL_PASSWORD=<your_mailtrap_password>
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=support@pointwave.ng
   MAIL_FROM_NAME="KoboPoint"
   ```

4. **Clear Config Cache**:
   ```bash
   php artisan config:clear
   ```

5. **Test Email**:
   ```bash
   php artisan email:test welcome
   ```

6. **View in Mailtrap**:
   - Go to your Mailtrap inbox
   - You'll see the email with full HTML rendering
   - You can test spam score, validate HTML, etc.

## Alternative: Gmail SMTP (For Production)

If you want to use Gmail for testing:

1. **Enable 2-Factor Authentication** on your Gmail account
2. **Create App Password**:
   - Go to Google Account → Security → 2-Step Verification → App Passwords
   - Generate password for "Mail"
3. **Update `.env`**:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your-email@gmail.com
   MAIL_PASSWORD=<your-app-password>
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=your-email@gmail.com
   MAIL_FROM_NAME="KoboPoint"
   ```

## Quick Test Command

After updating configuration:
```bash
php artisan config:clear
php artisan email:test welcome
```

Check Mailtrap inbox or Gmail to verify delivery.
