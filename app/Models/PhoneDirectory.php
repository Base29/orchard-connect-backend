<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class PhoneDirectory extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'phone_number'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $table = 'phone_directories';

    protected $fillable = [
        'name',
        'phone_number',
        'description',
        'category',
        'order',
    ];
}
