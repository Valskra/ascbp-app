<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class FileTestController extends Controller
{
    // 1) Afficher la page de test
    public function index()
    {
        // On récupère tous les fichiers en base (juste pour la démo).
        // Dans la vraie vie, on filtrerait peut-être par "fileable_type" ou autre.
        $files = File::all();

        // On passe les fichiers à la vue Inertia
        return Inertia::render('FileTest', [
            'files' => $files
        ]);
    }

    // 2) Gérer l’upload
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:2048|mimes:png,jpg,jpeg,gif,pdf,docx'
        ]);

        $uploadedFile = $request->file('file');

        // Stockage sur le disque public, dans un dossier "test" (modifiable).
        // => physical path: storage/app/public/test/xx
        $path = $uploadedFile->store('test', 'public');

        // On crée l’enregistrement dans la table "files".
        // (On ne lie pas ce fichier à un "fileable" particulier, donc fileable_id = null).
        $file = File::create([
            'fileable_id'   => null,
            'fileable_type' => null,

            'name'          => pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME),
            'extension'     => $uploadedFile->getClientOriginalExtension(),
            'mimetype'      => $uploadedFile->getMimeType(),
            'size'          => $uploadedFile->getSize(),
            'path'          => $path,
            'disk'          => 'public',
        ]);

        // Rediriger vers la page d’index pour afficher la liste des fichiers
        return redirect()->route('file-test.index')->with('success', 'Fichier uploadé !');
    }
}
