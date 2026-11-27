<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailConnection extends Model
{
    protected $fillable = [
        'user_id', 'provider', 'email', 'access_token', 'refresh_token',
        'access_token_expires_at', 'history_id', 'watch_expires_at',
        'status', 'last_sync_at', 'last_error',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'access_token_expires_at' => 'datetime',
        'watch_expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function imports(): HasMany
    {
        return $this->hasMany(EmailTransactionImport::class);
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(EmailAccountMapping::class);
    }
}
