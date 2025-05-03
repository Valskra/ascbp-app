<?php


namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class FileController extends Controller
{

    /**
     * List certificates of the authenticated user
     */
    public function listUserCertificates(Request $request)
    {
        $certificates = Document::with('file')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn($doc) => [
                'id'        => $doc->id,
                'title'     => $doc->title,
                'extension' => $doc->file->extension,
                'url'       => $doc->file->url,
            ]);


        return Inertia::render('Profile/UserCertificat', [
            'certificates' => $certificates,
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:2048|mimes:doc,docx,dotx,odt,svg,pdf,png,jpg,jpeg,gif,bmp,webp,heic'
        ]);

        $uploadedFile = $request->file('file');

        $path = $uploadedFile->store('user_profile_pictures', 's3');

        $hash = hash_file('sha256', $uploadedFile->getRealPath());

        if (File::where('fileable_id', $request->user()->id)
            ->where('hash', $hash)
            ->exists()
        ) {
            return back()->withErrors([
                'file' => 'Vous avez déjà importé ce fichier.',
            ])->withInput();
        }


        $file = File::create([
            'fileable_id'   => null,
            'fileable_type' => null,
            'name'          => pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME),
            'extension'     => $uploadedFile->getClientOriginalExtension(),
            'mimetype'      => $uploadedFile->getMimeType(),
            'size'          => $uploadedFile->getSize(),
            'path'          => $path,
            'disk'          => 's3',
            'hash'          => $hash,          // 🟢 ajouté
        ]);

        return back()->with('success', 'Fichier uploadé !');
    }

    public function storeCertificate(Request $request)
    {
        $user = $request->user();

        /* validation avec 10 Mo max */
        $request->validate([
            'title' => [
                'required',
                'string',
                'max:100',
                Rule::unique('documents', 'title')
                    ->where(fn($q) => $q->where('user_id', $user->id)),
            ],
            'file'  => 'required|file|mimes:doc,docx,dotx,odt,svg,pdf,png,jpg,jpeg,gif,bmp,webp|max:10240',
        ], [
            /* messages personnalisés … */]);

        $uploaded = $request->file('file');
        $hash     = hash_file('sha256', $uploaded->getRealPath());

        /* doublon identique pour ce user ? */
        if (File::where('fileable_id', $user->id)->where('hash', $hash)->exists()) {
            return back()->withErrors([
                'file' => 'Vous avez déjà importé un document identique.',
            ])->withInput();
        }

        /* upload S3 */
        $token     = Str::random(40);
        $expiresAt = Carbon::now()->addDays(30)->format('Ymd');
        $ext       = $uploaded->getClientOriginalExtension();
        $path      = "certificate/{$user->id}/{$token}_{$expiresAt}.{$ext}";

        Storage::disk('s3')->put($path, file_get_contents($uploaded), ['visibility' => 'public']);

        /* enregistrement File AVEC hash */
        $file = File::create([
            'fileable_id'   => $user->id,
            'fileable_type' => get_class($user),
            'name'          => pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME),
            'extension'     => $ext,
            'mimetype'      => $uploaded->getMimeType(),
            'size'          => $uploaded->getSize(),
            'hash'          => $hash,
            'path'          => $path,
            'disk'          => 's3',
        ]);

        /* Document */
        Document::create([
            'title'           => $request->title,
            'expiration_date' => Carbon::now()->addDays(30),
            'file_id'         => $file->id,
            'user_id'         => $user->id,
        ]);

        return back()->with('success', 'Certificat enregistré avec titre.');
    }





    /**
     * Show a public certificate if it exists and not expired
     */
    public function showCertificate($userId, $token)
    {
        $files = Storage::disk('s3')->files("certificate/{$userId}");

        foreach ($files as $filePath) {
            if (Str::contains($filePath, $token)) {
                $filename = basename($filePath);
                $parts = explode('_', $filename);
                if (count($parts) < 2) continue;

                $expirationString = explode('.', $parts[1])[0] ?? null;

                if ($expirationString && Carbon::createFromFormat('Ymd', $expirationString)->isFuture()) {
                    // File is valid
                    return redirect(env('AWS_URL') . '/' . $filePath);
                }

                return response()->json(['error' => 'Certificat expiré'], 410);
            }
        }

        return response()->json(['error' => 'Certificat introuvable'], 404);
    }



    // FileController.php
    public function destroyCertificate(Request $request, Document $document)
    {
        // ✅ bonne vérification d’auteur
        abort_unless($document->user_id === $request->user()->id, 403);

        // supprime le fichier S3 si besoin
        if ($document->file && $document->file->disk === 's3') {
            Storage::disk('s3')->delete($document->file->path);
        }

        $document->delete();              // (FK ON DELETE CASCADE → File auto)

        return back()->with('success', 'Document supprimé.');
    }


    /**
     * Delete one certificate file
     */
    public function deleteCertificate(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        $userId = Auth::id();
        $path = $request->input('path');

        if (Str::startsWith($path, "certificate/{$userId}/")) {
            Storage::disk('s3')->delete($path);
            return response()->json(['success' => 'Certificat supprimé']);
        }

        return response()->json(['error' => 'Non autorisé'], 403);
    }

    /**
     * Delete multiple certificate files
     */
    public function deleteMultipleCertificates(Request $request)
    {
        $request->validate([
            'paths' => 'required|array'
        ]);

        $userId = Auth::id();

        foreach ($request->input('paths') as $path) {
            if (Str::startsWith($path, "certificate/{$userId}/")) {
                Storage::disk('s3')->delete($path);
            }
        }

        return response()->json(['success' => 'Certificats supprimés']);
    }

    public function storeUserProfilePicture(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'photo' => 'required|file|mimes:jpg,jpeg,png,gif,svg|max:2048',
        ]);

        $uploadedFile = $request->file('photo');

        /* ► calcule le hash SHA-256 */
        $hash = hash_file('sha256', $uploadedFile->getRealPath());

        /* ► doublon ? */
        if (File::where('fileable_id', $user->id)->where('hash', $hash)->exists()) {
            return back()->withErrors([
                'photo' => 'Vous avez déjà importé cette image.',
            ])->withInput();
        }

        /* ► supprime l’ancienne photo si elle existe */
        if ($user->profilePicture) {
            Storage::disk($user->profilePicture->disk)
                ->delete($user->profilePicture->path);
            $user->profilePicture->delete();
        }

        /* ► upload S3 */
        $path = "user_profile_pictures/{$user->id}." . $uploadedFile->getClientOriginalExtension();
        Storage::disk('s3')->put($path, file_get_contents($uploadedFile), 'public');

        /* ► enregistrement File avec hash */
        $user->profilePicture()->create([
            'name'          => "profile_picture_{$user->id}",
            'extension'     => $uploadedFile->getClientOriginalExtension(),
            'mimetype'      => $uploadedFile->getMimeType(),
            'size'          => $uploadedFile->getSize(),
            'path'          => $path,
            'disk'          => 's3',
            'hash'          => $hash,            // 🟢 ajouté
        ]);

        return back()->with('success', 'Photo de profil mise à jour !');
    }
}
