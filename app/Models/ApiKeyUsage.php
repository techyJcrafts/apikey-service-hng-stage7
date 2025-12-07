<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKeyUsage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'api_key_id',
        'method',
        'endpoint',
        'ip_address',
        'status_code',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class);
    }
}
