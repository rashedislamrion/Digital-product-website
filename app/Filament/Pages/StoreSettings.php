<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class StoreSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'System & Security';

    protected static ?string $navigationLabel = 'Store Settings';

    protected static ?string $title = 'Store & Infrastructure Settings';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.store-settings';

    public ?string $store_name = 'DevStore';

    public ?string $support_email = 'support@digitalstorefront.test';

    public ?string $default_currency = 'USD';

    public bool $gateway_sslcommerz = true;

    public bool $gateway_bkash = false;

    public bool $gateway_stripe = false;

    public bool $gateway_paddle = false;

    // S3 Cloud Storage (Read-Only Telemetry)
    public string $s3_bucket = '';

    public string $s3_region = '';

    public string $s3_endpoint = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('system.manage_settings') ?? false;
    }

    public function mount(): void
    {
        $this->store_name = (string) Setting::get('store_name', 'DevStore');
        $this->support_email = (string) Setting::get('support_email', 'support@digitalstorefront.test');
        $this->default_currency = (string) Setting::get('default_currency', 'USD');

        $this->gateway_sslcommerz = (bool) Setting::get('gateway_sslcommerz', true);
        $this->gateway_bkash = (bool) Setting::get('gateway_bkash', false);
        $this->gateway_stripe = (bool) Setting::get('gateway_stripe', false);
        $this->gateway_paddle = (bool) Setting::get('gateway_paddle', false);

        $this->s3_bucket = (string) config('filesystems.disks.s3.bucket', env('AWS_BUCKET', 'digital-product-releases'));
        $this->s3_region = (string) config('filesystems.disks.s3.region', env('AWS_DEFAULT_REGION', 'us-east-1'));
        $this->s3_endpoint = (string) (config('filesystems.disks.s3.endpoint') ?: 'https://s3.' . $this->s3_region . '.amazonaws.com');
    }

    public function save(): void
    {
        Setting::set('store_name', $this->store_name);
        Setting::set('support_email', $this->support_email);
        Setting::set('default_currency', $this->default_currency);

        Setting::set('gateway_sslcommerz', $this->gateway_sslcommerz);
        Setting::set('gateway_bkash', $this->gateway_bkash);
        Setting::set('gateway_stripe', $this->gateway_stripe);
        Setting::set('gateway_paddle', $this->gateway_paddle);

        Notification::make()
            ->title('Settings Saved')
            ->body('Store configuration and payment gateway parameters updated.')
            ->success()
            ->send();
    }
}
