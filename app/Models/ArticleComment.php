<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'article_id',
        'user_id',
        'parent_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec l'article
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Relation avec l'auteur du commentaire
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relation avec le commentaire parent
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ArticleComment::class, 'parent_id');
    }

    /**
     * Relation avec les réponses
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ArticleComment::class, 'parent_id')->with(['author', 'likes']);
    }

    /**
     * Relation avec les likes
     */
    public function likes(): HasMany
    {
        return $this->hasMany(CommentLike::class, 'comment_id');
    }

    /**
     * Vérifier si le commentaire peut être modifié par l'utilisateur
     */
    public function canBeEditedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        // L'auteur peut modifier son commentaire
        if ($this->user_id === $user->id) {
            return true;
        }

        // Les admins peuvent modifier tous les commentaires
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Vérifier si le commentaire peut être supprimé par l'utilisateur
     */
    public function canBeDeletedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        // L'auteur peut supprimer son commentaire
        if ($this->user_id === $user->id) {
            return true;
        }

        // Les admins peuvent supprimer tous les commentaires
        if ($user->isAdmin()) {
            return true;
        }

        // L'auteur de l'article peut supprimer les commentaires sur son article
        if ($this->article->author_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Vérifier si le commentaire est liké par l'utilisateur
     */
    public function isLikedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }

    /**
     * Scope pour les commentaires principaux (sans parent)
     */
    public function scopeMain($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope pour les réponses (avec parent)
     */
    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_id');
    }
}
