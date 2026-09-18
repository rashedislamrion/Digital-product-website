<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 30px;
            background-color: #ffffff;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .logo-title {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .logo-title span {
            color: #10b981;
        }
        .seller-info {
            font-size: 10px;
            color: #64748b;
            margin-top: 4px;
        }
        .invoice-title {
            font-size: 20px;
            font-weight: 700;
            text-align: right;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 25px;
        }
        .meta-col {
            vertical-align: top;
            width: 50%;
        }
        .section-label {
            font-size: 10px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .meta-value {
            font-size: 12px;
            font-weight: 600;
            color: #1e293b;
        }
        .badge-paid {
            display: inline-block;
            background-color: #d1fae5;
            color: #065f46;
            font-weight: 700;
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .items-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            text-align: left;
        }
        .items-table th.text-right {
            text-align: right;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
        }
        .items-table td.text-right {
            text-align: right;
        }
        .item-title {
            font-weight: 700;
            color: #0f172a;
            font-size: 12px;
        }
        .item-tier {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }
        .totals-table {
            width: 40%;
            margin-left: auto;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .totals-table td {
            padding: 6px 12px;
            font-size: 11px;
        }
        .totals-table td.total-label {
            color: #64748b;
            text-align: right;
        }
        .totals-table td.total-val {
            font-weight: 600;
            color: #1e293b;
            text-align: right;
            font-family: monospace;
        }
        .totals-table tr.grand-total td {
            border-top: 2px solid #0f172a;
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            padding-top: 10px;
        }
        .footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
        }
        .footer p {
            margin: 2px 0;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <table class="header">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <div class="logo-title">DevStore<span>.</span></div>
                <div class="seller-info">
                    <strong>DevStore Technologies Ltd.</strong><br>
                    Single-Seller Digital Product Storefront<br>
                    support@digitalstorefront.test &bull; https://devstore.test
                </div>
            </td>
            <td style="width: 40%; vertical-align: top; text-align: right;">
                <div class="invoice-title">Invoice / Receipt</div>
                <div style="font-size: 12px; font-family: monospace; font-weight: bold; color: #0f172a; margin-top: 4px;">
                    #{{ $order->order_number }}
                </div>
                <div style="margin-top: 6px;">
                    <span class="badge-paid">Status: {{ strtoupper($order->status->value) }}</span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Customer & Order Meta -->
    <table class="meta-table">
        <tr>
            <td class="meta-col">
                <div class="section-label">Billed To</div>
                <div class="meta-value">{{ $order->customer->name ?? 'Valued Customer' }}</div>
                <div style="color: #64748b; font-size: 11px;">{{ $order->customer->email }}</div>
                @if($order->customer->country_code)
                    <div style="color: #64748b; font-size: 11px;">Country: {{ strtoupper($order->customer->country_code) }}</div>
                @endif
                @if($order->customer->tax_identifier)
                    <div style="color: #64748b; font-size: 11px;">Tax ID: {{ $order->customer->tax_identifier }}</div>
                @endif
            </td>
            <td class="meta-col" style="text-align: right;">
                <div class="section-label">Order Details</div>
                <div><span style="color: #64748b;">Date:</span> <strong>{{ $order->created_at->format('F d, Y') }}</strong></div>
                <div><span style="color: #64748b;">Payment Method:</span> <strong>{{ strtoupper($order->payment_gateway ?? 'SSLCOMMERZ') }}</strong></div>
                <div><span style="color: #64748b;">Currency:</span> <strong>USD</strong></div>
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 55%;">Item & License Tier</th>
                <th style="width: 15%; text-align: center;">Qty</th>
                <th style="width: 15%;" class="text-right">Unit Price</th>
                <th style="width: 15%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>
                        <div class="item-title">{{ $item->historical_product_title }}</div>
                        <div class="item-tier">Tier: {{ $item->historical_tier_name }}</div>
                        @if($item->license)
                            <div style="font-size: 9px; color: #4f46e5; font-family: monospace; margin-top: 2px;">
                                License: {{ $item->license->license_key_masked }}
                            </div>
                        @endif
                    </td>
                    <td style="text-align: center; color: #64748b;">1</td>
                    <td class="text-right" style="font-family: monospace;">${{ number_format($item->unit_amount_minor / 100, 2) }}</td>
                    <td class="text-right" style="font-family: monospace; font-weight: bold;">${{ number_format($item->unit_amount_minor / 100, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Financial Totals -->
    <table class="totals-table">
        <tr>
            <td class="total-label">Subtotal:</td>
            <td class="total-val">{{ $order->subtotal_formatted }}</td>
        </tr>
        @if($order->discount_minor > 0)
            <tr>
                <td class="total-label" style="color: #10b981;">Discount {{ $order->coupon_code ? '('.$order->coupon_code.')' : '' }}:</td>
                <td class="total-val" style="color: #10b981;">-{{ $order->discount_formatted }}</td>
            </tr>
        @endif
        <tr>
            <td class="total-label">Tax / VAT:</td>
            <td class="total-val">$0.00</td>
        </tr>
        <tr class="grand-total">
            <td class="total-label" style="color: #0f172a;">Total Paid:</td>
            <td class="total-val" style="color: #10b981;">{{ $order->total_formatted }}</td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        <p><strong>DevStore Perpetual Commercial License</strong></p>
        <p>All software downloads, digital assets, and license keys are delivered digitally. This receipt serves as your official proof of purchase and software entitlement.</p>
        <p>For technical support or license inquiries, visit your customer library or email support@digitalstorefront.test.</p>
    </div>

</body>
</html>
