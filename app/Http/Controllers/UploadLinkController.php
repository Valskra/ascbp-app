<?php
// app/Http/Controllers/UploadLinkController.php
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
        $request->validate([
            'name'     => 'nullable|string|max:100',
            'duration' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $expiresAt = Carbon::now()->addDays($request->duration);
        // Génération du token / URL...
        $link = UploadLink::create([
            'user_id'    => $user->id,
            'name'       => $request->name,
            'token'      => Str::random(40),
            'expires_at' => $expiresAt,
        ]);

        // construit ton URL publique
        $publicUrl = route('upload-link.show', ['token' => $link->token]);

        // renvoie un redirect Inertia vers la même page avec un flash
        return back()->with([
            'link_url' => $publicUrl,
        ]);
    }

    /* ---------- 2. Page publique de dépôt ---------- */
    public function showForm(string $token)
    {
        $link = UploadLink::where('token', $token)->firstOrFail();

        abort_if($link->isExpired(), 410, 'Ce lien a expiré ou a déjà été utilisé.');

        return Inertia::render('Public/UploadViaLink', [
            'token'  => $token,
            'title'  => $link->title,       // null ↔ titre libre
            'expire' => $link->expires_at,
        ]);
    }

    /* ---------- 3. Réception du fichier ---------- */
    public function upload(Request $request, string $token)
    {
        $link = UploadLink::where('token', $token)->firstOrFail();
        abort_if($link->isExpired(), 410, 'Lien périmé.');

        $rules = [
            'file' => 'required|file|mimes:doc,docx,dotx,odt,svg,pdf,png,jpg,jpeg,gif,bmp,webp,heic|max:10240',
        ];
        if (!$link->title) $rules['title'] = 'required|string|max:100';

        $validated = $request->validate($rules);

        /* --- Stockage identique à storeCertificate() --- */
        $user   = $link->user;
        $fileUp = $request->file('file');
        $hash   = hash_file('sha256', $fileUp->getRealPath());

        // si l’utilisateur avait déjà ce fichier : on autorise quand même (pas bloquant)

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
        $link->update(['used_at' => now()]);

        return Inertia::render('Public/UploadSuccess');
    }

    public function latest(Request $request)
    {
        $link = $request->user()
            ->uploadLinks()               // la relation hasMany
            ->orderByDesc('created_at')
            ->firstOrFail();

        // Renvoie directement la chaîne de l’URL publique
        $url = route('upload-link.show', ['token' => $link->token]);

        return response($url, 200)
            ->header('Content-Type', 'text/plain');
    }
}
