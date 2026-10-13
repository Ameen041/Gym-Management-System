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
}
