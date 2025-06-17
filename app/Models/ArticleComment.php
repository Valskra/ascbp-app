<?php
// app/Models/ArticleComment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ArticleComment extends Model
{
    use HasFactory;
    protected $fillable = [
        'content',
        'article_id',
        'user_id',
        'parent_id',
        'is_approved',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    protected $with = ['author'];

    /**
     * L'auteur du commentaire
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * L'article commenté
     */
    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Commentaire parent (pour les réponses)
     */
    public function parent()
    {
        return $this->belongsTo(ArticleComment::class, 'parent_id');
    }

    /**
     * Réponses au commentaire
     */
    public function replies()
    {
        return $this->hasMany(ArticleComment::class, 'parent_id');
    }

    /**
     * Les likes du commentaire
     */
    public function likes()
    {
        return $this->hasMany(CommentLike::class, 'comment_id');
    }

    /**
     * Vérifier si un utilisateur a liké le commentaire
     */
    public function isLikedBy(User $user = null): bool
    {
        if (!$user) return false;

        return $this->likes()->where('user_id', $user->id)->exists();
    }

    /**
     * Vérifier si l'utilisateur peut modifier le commentaire
     */
    public function canBeEditedBy(User $user): bool
    {
        return $user->id === $this->user_id || $user->isAdmin();
    }

    /**
     * Vérifier si l'utilisateur peut supprimer le commentaire
     */
    public function canBeDeletedBy(User $user): bool
    {
        return $user->id === $this->user_id || $user->isAdmin();
    }
}
