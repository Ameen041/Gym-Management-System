<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Simple key/value settings edited by the admin (commission, Sham Cash account...).
 */
class Setting extends Model
{
    public const DEFAULTS = [
        'commission_rate'      => '15',
        'invite_commission_rate' => '5',   // on clients a coach brings with their invite link
        'payout_hold_days'     => '7',
        'package_min_monthly'  => '5',     // the price range coaches can choose, per 30 days
        'package_max_monthly'  => '500',
        'shamcash_name'        => '',
        'shamcash_account'     => '',
        'shamcash_qr'          => '',
        'payment_instructions' => '',
        'support_whatsapp'     => '',
    ];

    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    private static ?array $cache = null;

    public static function get(string $key, $default = null)
    {
        if (self::$cache === null) {
            self::$cache = self::query()->pluck('value', 'key')->all();
        }

        $value = self::$cache[$key] ?? null;

        return ($value === null || $value === '') ? ($default ?? self::DEFAULTS[$key] ?? null) : $value;
    }

    public static function put(string $key, $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        self::$cache = null;
    }

    public static function commissionRate(): float
    {
        return (float) self::get('commission_rate');
    }

    /**
     * Lowest and highest price a coach may set for a package of $days days: the monthly range
     * set by the admin, times the number of months (a 90-day package may cost three times more).
     */
    public static function packagePriceRange(int $days): array
    {
        $months = max(1, $days) / 30;

        return [
            round((float) self::get('package_min_monthly') * $months * 2) / 2,
            round((float) self::get('package_max_monthly') * $months * 2) / 2,
        ];
    }

    /** Commission on a coach's own clients (signed up from the coach's invite link). */
    public static function inviteCommissionRate(): float
    {
        return (float) self::get('invite_commission_rate');
    }
}
