@extends('email.layouts.master')

@section('content')
    <div style="text-align: center;">
        <div style="background-color: #28a745; width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
            <span style="color: white; font-size: 40px;">✓</span>
        </div>
        <h2 style="color: #28a745; margin-bottom: 10px;">Settlement Successful</h2>
    </div>

    <p>Hello <strong>{{ $company_name }}</strong>,</p>

    <p>Your settlement has been processed successfully and credited to your wallet.</p>

    <div style="background-color: #f8f9fa; border-left: 4px solid #28a745; padding: 20px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #28a745;">Settlement Details</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #666;">Amount Settled:</td>
                <td style="padding: 8px 0; font-weight: bold; text-align: right;">₦{{ number_format($amount, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;">Previous Balance:</td>
                <td style="padding: 8px 0; text-align: right;">₦{{ number_format($balance_before, 2) }}</td>
            </tr>
            <tr style="border-top: 2px solid #28a745;">
                <td style="padding: 8px 0; color: #666; font-weight: bold;">New Balance:</td>
                <td style="padding: 8px 0; font-weight: bold; text-align: right; color: #28a745;">₦{{ number_format($balance_after, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;">Reference:</td>
                <td style="padding: 8px 0; text-align: right; font-family: monospace;">{{ $reference }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;">Settlement Date:</td>
                <td style="padding: 8px 0; text-align: right;">{{ $settlement_date }}</td>
            </tr>
        </table>
    </div>

    <p>The funds are now available in your wallet and can be withdrawn to your settlement account.</p>

    <div style="text-align: center; margin-top: 30px;">
        <a href="{{ config('app.app_url') }}/dashboard/wallet" class="btn">View Wallet</a>
    </div>

    <p style="margin-top: 30px; font-size: 14px; color: #666;">
        <strong>Note:</strong> Settlements are processed automatically based on your configured settlement schedule.
    </p>
@endsection
