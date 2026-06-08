<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneDirectory extends Model
{
    use HasFactory;

    protected $table = 'phone_directories';

    protected $fillable = [
        'name',
        'phone_number',
        'description',
        'category',
        'order',
    ];
}
