<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_id',
        'registration_date',
        'amount',
        'metadata',
    ];

    protected $casts = [
        'registration_date' => 'date',
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Accesseur pour le status depuis metadata
     */
    public function getStatusAttribute(): ?string
    {
        return $this->metadata['status'] ?? 'pending';
    }

    /**
     * Accesseur pour payment_method depuis metadata
     */
    public function getPaymentMethodAttribute(): ?string
    {
        return $this->metadata['payment_method'] ?? null;
    }

    /**
     * Mutateur pour status dans metadata
     */
    public function setStatusAttribute($value): void
    {
        $metadata = $this->metadata ?? [];
        $metadata['status'] = $value;
        $this->metadata = $metadata;
    }

    /**
     * Mutateur pour payment_method dans metadata
     */
    public function setPaymentMethodAttribute($value): void
    {
        $metadata = $this->metadata ?? [];
        $metadata['payment_method'] = $value;
        $this->metadata = $metadata;
    }

    /**
     * Vérifier si l'inscription est confirmée
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Vérifier si l'inscription est en attente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Vérifier si l'inscription est annulée
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Scope pour les inscriptions confirmées
     */
    public function scopeConfirmed($query)
    {
        return $query->whereJsonContains('metadata->status', 'confirmed');
    }

    /**
     * Scope pour les inscriptions en attente
     */
    public function scopePending($query)
    {
        return $query->whereJsonContains('metadata->status', 'pending');
    }
}
