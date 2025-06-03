<?php


namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Models\{File, Document, UploadLink};


class FileController extends Controller
{

    /**
     * List certificates of the authenticated user
     */
    public function listUserCertificates(Request $request)
    {
        $user = $request->user();


        $now = Carbon::now();
        $links = UploadLink::where('user_id', $user->id)
            ->orderBy('expires_at')
            ->get();

        foreach ($links as $link) {
            if ($link->expires_at->isPast() || $link->used_at !== null) {
                $link->delete();
            } else {
                break;
            }
        }


        $certificates = Document::with('file')
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn($doc) => [
                'id'        => $doc->id,
                'title'     => $doc->title,
                'extension' => $doc->file->extension,
                'url'       => $doc->file->url,
            ]);

        $uploadLinks = $user->getAllUploadLinks()
            ->map(fn($l) => [
                'id'         => $l->id,
                'title'      => $l->title,
                'url'        => route('upload-link.show', ['token' => $l->token]),
                'expires_at' => $l->expires_at,
                'used_at'    => $l->used_at,
            ]);

        return Inertia::render('Profile/UserCertificat', [
            'certificates' => $certificates,
            'uploadLinks'  => $uploadLinks,
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:doc,docx,dotx,odt,svg,pdf,png,jpg,jpeg,gif,bmp,webp,heic'
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
            'hash'          => $hash,
        ]);

        return back()->with('success', 'Fichier uploadé !');
    }

    public function storeCertificate(Request $request)
    {
        $user = $request->user();

        /* validation avec 10 Mo max */
        $user = $request->user();

        $request->validate([
            'title' => [
                'required',
                'string',
                'max:100',
                Rule::unique('documents', 'title')
                    ->where(fn($q) => $q->where('user_id', $user->id)),
            ],
            'file' => 'required|file|mimes:doc,docx,dotx,odt,svg,pdf,png,jpg,jpeg,gif,bmp,webp|max:10240',
            'expires_at' => 'required|date|after_or_equal:today',
        ], [
            'title.required' => 'Le titre est requis.',
            'title.unique' => 'Vous avez déjà un document avec ce titre.',
            'file.required' => 'Veuillez sélectionner un fichier.',
            'file.mimes' => 'Format de fichier invalide.',
            'file.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
            'expires_at.required' => 'La date d’expiration est requise.',
            'expires_at.date' => 'La date d’expiration doit être une date valide.',
            'expires_at.after_or_equal' => 'La date d’expiration doit être aujourd’hui ou dans le futur.',
        ]);

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
            'expiration_date' => Carbon::createFromFormat('Y-m-d', $request->expires_at)->endOfDay(),
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

                    return redirect(env('AWS_URL') . '/' . $filePath);
                }

                return response()->json(['error' => 'Certificat expiré'], 410);
            }
        }

        return response()->json(['error' => 'Certificat introuvable'], 404);
    }


    public function destroyCertificate(Request $request, Document $document)
    {
        abort_unless($document->user_id === $request->user()->id, 403);

        if ($document->file && $document->file->disk === 's3') {
            Storage::disk('s3')->delete($document->file->path);
        }

        $document->file()->delete();
        $document->delete();

        return back()->with('success', 'Document supprimé.');
    }


    public function storeUserProfilePicture(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'photo' => 'required|file|mimes:jpg,jpeg,png,gif,svg|max:10240',
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
            'hash'          => $hash,
        ]);

        return back()->with('success', 'Photo de profil mise à jour !');
    }
}
