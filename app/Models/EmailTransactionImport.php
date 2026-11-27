<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTransactionImport extends Model
{
    protected $fillable = [
        'user_id', 'email_connection_id', 'gmail_message_id', 'bank_code', 'bank_name',
        'amount', 'type', 'transaction_date', 'note',
        'status', 'transaction_id', 'review_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(EmailConnection::class, 'email_connection_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
