<?php

namespace Pixelpillow\LunarApiMollieAdapter\Domain\PaymentIssuers\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentIssuer extends Model
{
    protected $keyType = 'string';

    protected $fillable = [
        'resource',
        'name',
        'image',
        'issuer_id',
    ];

    protected $casts = [
        'image' => 'array',
    ];
}
