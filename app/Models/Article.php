<?php
// app/Models/Article.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Article extends Model
{
    protected $fillable = [
        'title',
        'excerpt',
        'content',
        'publish_date',
        'event_id',
        'is_pinned',
        'status',
        'views_count',
        'file_id',
        'user_id',
    ];

    protected $casts = [
        'publish_date' => 'datetime',
        'is_pinned' => 'boolean',
    ];

    protected $with = ['author', 'featuredImage'];

    /**
     * L'auteur de l'article
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * L'événement lié (optionnel)
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * L'image mise en avant
     */
    public function featuredImage()
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    /**
     * Les commentaires de l'article
     */
    public function comments()
    {
        return $this->hasMany(ArticleComment::class)->whereNull('parent_id');
    }

    /**
     * Tous les commentaires (incluant les réponses)
     */
    public function allComments()
    {
        return $this->hasMany(ArticleComment::class);
    }

    /**
     * Les likes de l'article
     */
    public function likes()
    {
        return $this->hasMany(ArticleLike::class);
    }

    /**
     * Vérifier si un utilisateur a liké l'article
     */
    public function isLikedBy(User $user = null): bool
    {
        if (!$user) return false;

        return $this->likes()->where('user_id', $user->id)->exists();
    }

    /**
     * Incrémenter le nombre de vues
     */
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    /**
     * Scope pour les articles publiés
     */
    public function scopePublished(Builder $query)
    {
        return $query->where('status', 'published')
            ->where('publish_date', '<=', now());
    }

    /**
     * Scope pour les articles épinglés
     */
    public function scopePinned(Builder $query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope pour les articles d'un événement
     */
    public function scopeForEvent(Builder $query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    /**
     * Scope pour les articles généraux (non liés à un événement)
     */
    public function scopeGeneral(Builder $query)
    {
        return $query->whereNull('event_id');
    }

    /**
     * Vérifier si l'utilisateur peut modifier l'article
     */
    public function canBeEditedBy(User $user): bool
    {
        return $user->id === $this->user_id || $user->isAdmin();
    }

    /**
     * Vérifier si l'utilisateur peut supprimer l'article
     */
    public function canBeDeletedBy(User $user): bool
    {
        return $user->id === $this->user_id || $user->isAdmin();
    }

    /**
     * Obtenir l'extrait automatiquement du contenu si pas défini
     */
    public function getExcerptAttribute($value)
    {
        if ($value) {
            return $value;
        }

        return substr(strip_tags($this->content), 0, 200) . '...';
    }
}
