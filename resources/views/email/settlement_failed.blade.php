@extends('email.layouts.master')

@section('content')
    <div style="text-align: center;">
        <div style="background-color: #dc3545; width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
            <span style="color: white; font-size: 40px;">✗</span>
        </div>
        <h2 style="color: #dc3545; margin-bottom: 10px;">Settlement Failed</h2>
    </div>

    <p>Hello <strong>{{ $company_name }}</strong>,</p>

    <p>We encountered an issue while processing your settlement. The funds have not been credited to your wallet.</p>

    <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #856404;">Settlement Details</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #666;">Amount:</td>
                <td style="padding: 8px 0; font-weight: bold; text-align: right;">₦{{ number_format($amount, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;">Reference:</td>
                <td style="padding: 8px 0; text-align: right; font-family: monospace;">{{ $reference }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;">Attempted Date:</td>
                <td style="padding: 8px 0; text-align: right;">{{ $attempted_date }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;">Reason:</td>
                <td style="padding: 8px 0; text-align: right; color: #dc3545;">{{ $error_message }}</td>
            </tr>
        </table>
    </div>

    <div class="alert-warning">
        <strong>What happens next?</strong><br>
        Our team has been notified and will investigate this issue. The settlement will be retried automatically, or you can contact support for immediate assistance.
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <a href="{{ config('app.app_url') }}/contact" class="btn" style="background-color: #dc3545;">Contact Support</a>
    </div>

    <p style="margin-top: 30px; font-size: 14px; color: #666;">
        <strong>Reference Number:</strong> {{ $reference }}<br>
        Please quote this reference when contacting support.
    </p>
@endsection
