<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function store(Request $request, Article $article)
    {
        $request->validate([
            'file' => 'required|file|max:2048|mimes:png,jpg,jpeg,gif'
        ]);

        // On stocke sur le disque "public"
        // On peut aussi créer un sous-dossier “articles/{article->id}”
        $path = $request->file('file')->store("articles/{$article->id}", 'public');

        // Puis on crée l’enregistrement en DB
        $file = $article->files()->create([
            'name'      => pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME),
            'extension' => $request->file('file')->getClientOriginalExtension(),
            'mimetype'  => $request->file('file')->getMimeType(),
            'size'      => $request->file('file')->getSize(),
            'path'      => $path,   // ex: “articles/5/abc123.jpg”
            'disk'      => 'public'
        ]);

        // Redirect ou Inertia::redirect...
        return redirect()->back()->with('success', 'Fichier uploadé');
    }
}
