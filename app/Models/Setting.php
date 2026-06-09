<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a setting value.
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        $val = $setting->value;

        // Handle JSON values
        if ($val === 'true') return true;
        if ($val === 'false') return false;

        $decoded = json_decode($val, true);
        if (json_last_error() === JSON_ERROR_NONE && ($decoded !== null || $val === 'null')) {
            return $decoded;
        }

        return $val;
    }

    /**
     * Set a setting value.
     */
    public static function setValue(string $key, $value)
    {
        $serializedValue = is_array($value) || is_object($value) || is_bool($value)
            ? json_encode($value)
            : (string) $value;

        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $serializedValue]
        );
    }
}
