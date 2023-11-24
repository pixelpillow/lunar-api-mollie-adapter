<?php

namespace Pixelpillow\LunarApiMollieAdapter\Domain\PaymentMethods\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'image',
        'method_id',
    ];

    protected $casts = [
        'image' => 'array',
    ];
}
