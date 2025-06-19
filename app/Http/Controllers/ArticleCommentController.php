<?php

namespace App\Http\Controllers;

use App\Models\{Article, ArticleComment, CommentLike, User};
use Illuminate\Http\Request;
use Inertia\Inertia;

class ArticleCommentController extends Controller
{
    /**
     * Créer un nouveau commentaire
     */
    public function store(Request $request, Article $article)
    {
        // Vérifier que l'utilisateur est authentifié
        if (!$request->user()) {
            abort(401, 'Vous devez être connecté pour commenter.');
        }

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:article_comments,id',
        ]);

        // Vérifier que le commentaire parent appartient bien à cet article
        if ($validated['parent_id']) {
            $parentComment = ArticleComment::findOrFail($validated['parent_id']);
            if ($parentComment->article_id !== $article->id) {
                abort(422, 'Le commentaire parent n\'appartient pas à cet article.');
            }
        }

        $comment = ArticleComment::create([
            'content' => $validated['content'],
            'article_id' => $article->id,
            'user_id' => $request->user()->id,
            'parent_id' => $validated['parent_id'],
        ]);

        $comment->load(['author', 'likes']);

        // Préparer les données du commentaire pour la réponse
        $commentData = [
            'id' => $comment->id,
            'content' => $comment->content,
            'created_at' => $comment->created_at,
            'parent_id' => $comment->parent_id,
            'author' => [
                'id' => $comment->author->id,
                'firstname' => $comment->author->firstname,
                'lastname' => $comment->author->lastname,
            ],
            'likes_count' => 0,
            'is_liked' => false,
            'can_edit' => $comment->canBeEditedBy($request->user()),
            'can_delete' => $comment->canBeDeletedBy($request->user()),
            'replies' => [],
        ];

        return redirect()->back()->with('flash', [
            'comment' => $commentData,
            'message' => 'Commentaire ajouté avec succès !'
        ]);
    }

    /**
     * Modifier un commentaire
     */
    public function update(Request $request, ArticleComment $comment)
    {
        if (!$comment->canBeEditedBy($request->user())) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier ce commentaire.');
        }

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $comment->update([
            'content' => $validated['content'],
        ]);

        return redirect()->back()->with('flash', [
            'message' => 'Commentaire modifié avec succès !'
        ]);
    }

    /**
     * Supprimer un commentaire
     */
    public function destroy(Request $request, ArticleComment $comment)
    {
        if (!$comment->canBeDeletedBy($request->user())) {
            abort(403, 'Vous n\'êtes pas autorisé à supprimer ce commentaire.');
        }

        $comment->delete();

        return redirect()->back()->with('flash', [
            'message' => 'Commentaire supprimé avec succès !'
        ]);
    }

    /**
     * Liker/déliker un commentaire
     */
    public function toggleLike(Request $request, ArticleComment $comment)
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Vous devez être connecté pour liker un commentaire.');
        }

        $existingLike = CommentLike::where('comment_id', $comment->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingLike) {
            $existingLike->delete();
            $liked = false;
        } else {
            CommentLike::create([
                'comment_id' => $comment->id,
                'user_id' => $user->id,
            ]);
            $liked = true;
        }

        $likesCount = $comment->likes()->count();

        return redirect()->back()->with('flash', [
            'comment' => [
                'id' => $comment->id,
                'is_liked' => $liked,
                'likes_count' => $likesCount,
            ],
            'message' => $liked ? 'Commentaire liké !' : 'Like retiré !'
        ]);
    }
}
