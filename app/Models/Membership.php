<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Membership extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'contribution_date',
        'amount',
        'metadata',
    ];

    protected $casts = [
        'contribution_date' => 'date',
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifie si l'adhésion est active (cotisation de moins d'un an)
     */
    public function isActive(): bool
    {
        return $this->contribution_date && $this->contribution_date->isAfter(now()->subYear());
    }

    /**
     * Scope pour récupérer les adhésions actives
     */
    public function scopeActive($query)
    {
        return $query->where('contribution_date', '>', now()->subYear());
    }

    /**
     * Accesseur pour le payment_method depuis metadata
     */
    public function getPaymentMethodAttribute(): ?string
    {
        return $this->metadata['payment_method'] ?? null;
    }

    /**
     * Accesseur pour les notes depuis metadata
     */
    public function getNotesAttribute(): ?string
    {
        return $this->metadata['notes'] ?? null;
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
     * Mutateur pour notes dans metadata
     */
    public function setNotesAttribute($value): void
    {
        $metadata = $this->metadata ?? [];
        $metadata['notes'] = $value;
        $this->metadata = $metadata;
    }
}
