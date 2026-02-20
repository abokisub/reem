<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transaction Receipt</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
        .receipt-title { font-size: 24px; font-weight: bold; }
        .receipt-number { font-size: 14px; color: #666; margin-top: 10px; }
        .section { margin: 20px 0; }
        .section-title { font-size: 16px; font-weight: bold; background: #f5f5f5; padding: 10px; margin-bottom: 10px; }
        .row { display: flex; justify-content: space-between; padding: 8px 10px; }
        .label { font-weight: bold; }
        .value { text-align: right; }
        .amount-section { background: #f9f9f9; padding: 15px; margin: 20px 0; }
        .total { font-size: 18px; font-weight: bold; }
        .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="receipt-title">TRANSACTION RECEIPT</div>
        <div class="receipt-number">Receipt #: {{ $receipt_number }}</div>
        <div class="receipt-number">Generated: {{ $generated_at }}</div>
    </div>

    <div class="section">
        <div class="section-title">TRANSACTION DETAILS</div>
        <div class="row">
            <span class="label">Session ID:</span>
            <span class="value">{{ $transaction_id }}</span>
        </div>
        <div class="row">
            <span class="label">Date:</span>
            <span class="value">{{ $date }}</span>
        </div>
        <div class="row">
            <span class="label">Status:</span>
            <span class="value">{{ $status }}</span>
        </div>
        <div class="row">
            <span class="label">Type:</span>
            <span class="value">{{ $type }}</span>
        </div>
    </div>

    <div class="amount-section">
        <div class="section-title">AMOUNT BREAKDOWN</div>
        <div class="row">
            <span class="label">Amount:</span>
            <span class="value">₦{{ $amount }}</span>
        </div>
        <div class="row">
            <span class="label">Fee:</span>
            <span class="value">₦{{ $fee }}</span>
        </div>
        <div class="row total">
            <span class="label">Net Amount:</span>
            <span class="value">₦{{ $net_amount }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">CUSTOMER INFORMATION</div>
        <div class="row">
            <span class="label">Name:</span>
            <span class="value">{{ $customer['name'] }}</span>
        </div>
        <div class="row">
            <span class="label">Account:</span>
            <span class="value">{{ $customer['account'] }}</span>
        </div>
        <div class="row">
            <span class="label">Bank:</span>
            <span class="value">{{ $customer['bank'] }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">COMPANY INFORMATION</div>
        <div class="row">
            <span class="label">Company:</span>
            <span class="value">{{ $company['name'] }}</span>
        </div>
        <div class="row">
            <span class="label">Email:</span>
            <span class="value">{{ $company['email'] }}</span>
        </div>
    </div>

    @if($description !== 'N/A')
    <div class="section">
        <div class="section-title">DESCRIPTION</div>
        <div style="padding: 10px;">{{ $description }}</div>
    </div>
    @endif

    <div class="footer">
        <p>This is a computer-generated receipt and does not require a signature.</p>
        <p>For inquiries, please contact {{ $company['email'] }}</p>
    </div>
</body>
</html>
