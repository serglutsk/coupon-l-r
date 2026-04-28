<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'valid_from',
        'valid_to',
        'code',
        'rules',
        'actions',
        'discount_amount',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'bool',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'rules' => 'array',
        'actions' => 'array',
        'discount_amount' => 'decimal:2',
    ];
}
