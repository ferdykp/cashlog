<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'currency', 'theme', 'avatar', 'google_id'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['email_verified_at' => 'datetime', 'password' => 'hashed'];

    // Append URL Avatar
    protected $appends = ['avatar_url', 'google_linked'];

    public function getGoogleLinkedAttribute(): bool
    {
        return filled($this->google_id);
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
                return $this->avatar;
            }

            return asset('storage/'.$this->avatar);
        }

        // Fallback Avatar Default (UI Avatars)
        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=6C4CF1&color=fff';
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    public function goals()
    {
        return $this->hasMany(Goal::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function emailConnections()
    {
        return $this->hasMany(EmailConnection::class);
    }
}
