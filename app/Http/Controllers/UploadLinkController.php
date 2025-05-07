<?php

namespace App\Http\Controllers;

use App\Models\{UploadLink, File, Document};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UploadLinkController extends Controller
{



    /* ---------- 1. Génération du lien par l’utilisateur ---------- */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'title' => [
                'nullable',
                'string',
                'max:100',
                function ($attribute, $value, $fail) use ($user) {
                    if (!$value) {
                        return;
                    }
                    $existsLink = UploadLink::where('user_id', $user->id)
                        ->where('title', $value)
                        ->exists();

                    $existsFile = File::where('fileable_id', $user->id)
                        ->where('fileable_type', get_class($user))
                        ->where('name', $value)
                        ->exists();

                    if ($existsLink || $existsFile) {
                        $fail('Nom de fichier déjà utilisé dans un autre fichier ou lien');
                    }
                },
            ],
            'duration' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $expiresAt = Carbon::now()->addDays($request->duration);
        $link = UploadLink::create([
            'user_id'    => $user->id,
            'title'       => $request->title,
            'token'      => Str::random(40),
            'expires_at' => $expiresAt,
        ]);

        $publicUrl = route('upload-link.show', ['token' => $link->token]);

        return back()->with([
            'link_url' => $publicUrl,
        ]);
    }

    /* ---------- 2. Page publique de dépôt ---------- */
    public function showForm(string $token)
    {
        $link = UploadLink::where('token', $token)->firstOrFail();

        if ($link->expires_at->isPast()) {
            $link->delete();
            abort(410, 'Ce lien a expiré.');
        }

        return Inertia::render('Public/UploadViaLink', [
            'token'  => $token,
            'title'  => $link->title,
            'expire' => $link->expires_at,
            'used'   => $link->used_at !== null,
        ]);
    }



    /* ---------- 3. Réception du fichier ---------- */
    public function upload(Request $request, string $token)
    {
        $link = UploadLink::where('token', $token)->firstOrFail();
        abort_if($link->used_at !== null, 410, 'Ce lien a déjà été utilisé.');

        $rules = [
            'file' => 'required|file|mimes:doc,docx,dotx,odt,svg,pdf,png,jpg,jpeg,gif,bmp,webp,heic|max:10240',
        ];
        if (!$link->title) $rules['title'] = 'required|string|max:100';

        $validated = $request->validate($rules);

        /* --- Stockage identique à storeCertificate() --- */
        $user   = $link->user;
        $fileUp = $request->file('file');
        $hash   = hash_file('sha256', $fileUp->getRealPath());


        $tokenName = Str::random(40);
        $expiresAt = Carbon::now()->addDays(30)->format('Ymd');
        $ext       = $fileUp->getClientOriginalExtension();
        $path      = "certificate/{$user->id}/{$tokenName}_{$expiresAt}.{$ext}";
        Storage::disk('s3')->put($path, file_get_contents($fileUp), ['visibility' => 'public']);

        $file = File::create([
            'fileable_id'   => $user->id,
            'fileable_type' => get_class($user),
            'name'          => pathinfo($fileUp->getClientOriginalName(), PATHINFO_FILENAME),
            'extension'     => $ext,
            'mimetype'      => $fileUp->getMimeType(),
            'size'          => $fileUp->getSize(),
            'hash'          => $hash,
            'path'          => $path,
            'disk'          => 's3',
        ]);

        Document::create([
            'title'           => $link->title ?: $validated['title'],
            'expiration_date' => Carbon::now()->addDays(30),
            'file_id'         => $file->id,
            'user_id'         => $user->id,
        ]);

        /* marque le lien comme utilisé */
        $link->markAsUsed();

        return Inertia::render('Public/UploadSuccess');
    }

    public function latest(Request $request)
    {
        $link = $request->user()
            ->uploadLinks()->orderByDesc('created_at')
            ->firstOrFail();

        $url = route('upload-link.show', ['token' => $link->token]);

        return response($url, 200)
            ->header('Content-Type', 'text/plain');
    }
}
