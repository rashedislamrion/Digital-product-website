<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Confirmation #{{ $order->order_number }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0f172a; color: #e2e8f0; margin: 0; padding: 24px; line-height: 1.5; }
        .container { max-width: 600px; margin: 0 auto; background-color: #1e293b; border-radius: 12px; border: 1px solid #334155; overflow: hidden; }
        .header { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); padding: 32px 24px; border-bottom: 1px solid #334155; text-align: center; }
        .header h1 { margin: 0 0 8px; font-size: 22px; color: #ffffff; font-weight: 700; }
        .header p { margin: 0; font-size: 14px; color: #94a3b8; }
        .content { padding: 24px; }
        .order-meta { background-color: #0f172a; border-radius: 8px; padding: 16px; margin-bottom: 24px; border: 1px solid #334155; font-size: 13px; font-family: monospace; }
        .meta-row { display: flex; justify-content: space-between; margin-bottom: 6px; }
        .meta-row:last-child { margin-bottom: 0; }
        .meta-label { color: #64748b; }
        .meta-val { color: #38bdf8; font-weight: bold; }
        .item-card { background-color: #0f172a; border-radius: 8px; border: 1px solid #334155; padding: 16px; margin-bottom: 16px; }
        .item-title { font-size: 15px; font-weight: bold; color: #ffffff; margin-bottom: 4px; }
        .item-tier { font-size: 12px; color: #10b981; font-family: monospace; margin-bottom: 12px; }
        .license-box { background-color: #1e1b4b; border: 1px solid #6366f1; border-radius: 6px; padding: 12px; margin-top: 12px; }
        .license-label { font-size: 11px; text-transform: uppercase; color: #a5b4fc; letter-spacing: 0.05em; margin-bottom: 4px; }
        .license-key { font-family: 'JetBrains Mono', monospace; font-size: 14px; font-weight: bold; color: #38bdf8; letter-spacing: 0.05em; word-break: break-all; user-select: all; }
        .btn-download { display: inline-block; background-color: #10b981; color: #022c22; font-weight: 700; font-size: 13px; text-decoration: none; padding: 10px 18px; border-radius: 6px; margin-top: 10px; }
        .summary-box { border-top: 1px solid #334155; padding-top: 16px; margin-top: 24px; }
        .summary-row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px; color: #94a3b8; }
        .summary-total { font-size: 16px; font-weight: bold; color: #10b981; margin-top: 8px; border-top: 1px dashed #334155; padding-top: 8px; }
        .footer { padding: 24px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #334155; background-color: #0f172a; }
        .footer a { color: #38bdf8; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Thank You for Your Order!</h1>
            <p>Your payment has been verified and your digital licenses are active.</p>
        </div>

        <div class="content">
            <div class="order-meta">
                <div class="meta-row">
                    <span class="meta-label">ORDER NUMBER:</span>
                    <span class="meta-val">{{ $order->order_number }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">DATE:</span>
                    <span class="meta-val" style="color: #cbd5e1;">{{ $order->created_at->format('M d, Y H:i') }} UTC</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">PAYMENT:</span>
                    <span class="meta-val" style="color: #cbd5e1;">{{ strtoupper($order->payment_gateway ?? 'SSLCOMMERZ') }} (PAID)</span>
                </div>
            </div>

            <h2 style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin: 0 0 12px;">Your Digital Entitlements</h2>

            @foreach($order->items as $item)
                <div class="item-card">
                    <div class="item-title">{{ $item->historical_product_title }}</div>
                    <div class="item-tier">Tier: {{ $item->historical_tier_name }}</div>

                    @if(isset($rawLicenses[$item->id]))
                        <div class="license-box">
                            <div class="license-label">Your Software License Key (Copy & Save Safely):</div>
                            <div class="license-key">{{ $rawLicenses[$item->id]['raw_key'] }}</div>
                            <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">
                                Use this key to activate your software instance. Never share this key publicly.
                            </div>
                        </div>
                    @endif

                    @if($item->downloadGrants->isNotEmpty())
                        @foreach($item->downloadGrants as $grant)
                            <div style="margin-top: 12px;">
                                <a href="{{ route('library.download', ['download_grant' => $grant->id]) }}" class="btn-download">
                                    Download Software Package &rarr;
                                </a>
                                <div style="font-size: 11px; color: #64748b; margin-top: 4px;">
                                    Max {{ $grant->max_download_attempts }} downloads allowed. Valid for 1 year.
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            @endforeach

            <div class="summary-box">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span style="color: #f1f5f9;">{{ $order->subtotal_formatted }}</span>
                </div>
                @if($order->discount_minor > 0)
                    <div class="summary-row" style="color: #10b981;">
                        <span>Discount {{ $order->coupon_code ? "({$order->coupon_code})" : '' }}:</span>
                        <span>-{{ $order->discount_formatted }}</span>
                    </div>
                @endif
                <div class="summary-row summary-total">
                    <span style="color: #f1f5f9;">Total Paid:</span>
                    <span>{{ $order->total_formatted }}</span>
                </div>
            </div>

            <div style="margin-top: 24px; text-align: center;">
                <a href="{{ route('customer.orders') }}" style="color: #38bdf8; font-size: 13px; font-weight: 600; text-decoration: underline;">
                    Access Your Full Customer Library & Order History
                </a>
            </div>
        </div>

        <div class="footer">
            <p>Need support? Reply to this email or visit our <a href="{{ route('home') }}">support center</a>.</p>
            <p style="margin-top: 4px;">{{ config('app.name', 'Digital Storefront') }} &bull; High-performance digital goods.</p>
        </div>
    </div>
</body>
</html>
