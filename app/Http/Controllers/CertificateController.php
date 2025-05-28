<?php
// app/Http/Controllers/CertificateController.php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateController extends Controller
{
    /**
     * Accès public avec token complexe
     * Route: /certificate/{userId}/{token}
     */
    public function showPublic($userId, $token)
    {
        $files = Storage::disk('s3')->files("certificate/{$userId}");

        foreach ($files as $filePath) {
            if (str_contains($filePath, $token)) {
                $filename = basename($filePath);
                $parts = explode('_', $filename);

                if (count($parts) < 2) continue;

                $expirationString = explode('.', $parts[1])[0] ?? null;

                // Vérifier si le certificat n'est pas expiré
                if ($expirationString && \Carbon\Carbon::createFromFormat('Ymd', $expirationString)->isFuture()) {
                    return $this->streamFile($filePath);
                }

                return response()->json(['error' => 'Certificat expiré'], 410);
            }
        }

        return response()->json(['error' => 'Certificat introuvable'], 404);
    }



    /**
     * Stream le fichier depuis S3
     */
    private function streamFile($filePath)
    {
        if (!Storage::disk('s3')->exists($filePath)) {
            abort(404, 'Fichier non trouvé');
        }

        $file = Storage::disk('s3')->get($filePath);
        $mimeType = Storage::disk('s3')->mimeType($filePath);
        $size = Storage::disk('s3')->size($filePath);

        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Length', $size)
            ->header('Content-Disposition', 'inline; filename="' . basename($filePath) . '"');
    }
}
