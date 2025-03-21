<?php


namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class FileController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:2048|mimes:png,jpg,jpeg,gif,pdf,docx'
        ]);

        $uploadedFile = $request->file('file');

        $path = $uploadedFile->store('user_profile_picture', 's3');

        $file = File::create([
            'fileable_id'   => null,
            'fileable_type' => null,

            'name'          => pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME),
            'extension'     => $uploadedFile->getClientOriginalExtension(),
            'mimetype'      => $uploadedFile->getMimeType(),
            'size'          => $uploadedFile->getSize(),
            'path'          => $path,
            'disk'          => 's3',
        ]);

        return back()->with('success', 'Fichier uploadé !');
    }

    public function storeUserProfilePicture(Request $request)
    {
        // (1) Récupérer l’utilisateur connecté
        //     ou un $user via la route si tu veux uploader pour un user particulier
        $user = $request->user();
        //  ou $user = User::findOrFail($request->user_id); // en fonction de ton usage

        // (2) Validation
        $request->validate([
            'photo' => 'required|file|mimes:jpg,jpeg,png,gif,svg|max:2048',
        ]);

        // (3) Récupérer le fichier
        $uploadedFile = $request->file('photo');

        // (4) Supprimer l’ancienne photo si elle existe
        if ($user->profilePicture) {
            Storage::disk($user->profilePicture->disk)
                ->delete($user->profilePicture->path);
            $user->profilePicture->delete();
        }

        // (5) Construire le chemin. Exemple pour S3/OVH :
        $path = "user_profile_pictures/{$user->id}." . $uploadedFile->getClientOriginalExtension();

        // (6) Envoyer sur S3 / OVH
        Storage::disk('s3')->put($path, file_get_contents($uploadedFile), 'public');

        // (7) Créer l’enregistrement dans la table "files"
        $user->profilePicture()->create([
            'name'      => "profile_picture_{$user->id}",
            'extension' => $uploadedFile->getClientOriginalExtension(),
            'mimetype'  => $uploadedFile->getMimeType(),
            'size'      => $uploadedFile->getSize(),
            'path'      => $path,
            'disk'      => 's3',
        ]);

        // (8) Réponse Inertia ou JSON
        return back()->with('success', 'Photo de profil mise à jour (via FileController) !');
    }

    public function destroy(File $file)
    {
        // Vérifier si l'utilisateur a le droit de supprimer ce fichier
        if (auth()->id() !== $file->fileable_id) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        // Supprimer le fichier du stockage
        Storage::disk($file->disk)->delete($file->path);

        // Supprimer l'entrée de la base de données
        $file->delete();

        return response()->json(['success' => 'Fichier supprimé avec succès'], 200);
    }
}
