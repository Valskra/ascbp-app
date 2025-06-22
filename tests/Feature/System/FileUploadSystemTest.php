<?php
// tests/Feature/System/FileUploadSystemTest.php

use App\Models\{User, File, Document, UploadLink};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Configuration S3 pour les tests
    config([
        'filesystems.disks.s3.driver' => 's3',
        'filesystems.disks.s3.key' => 'test_key',
        'filesystems.disks.s3.secret' => 'test_secret',
        'filesystems.disks.s3.region' => 'test_region',
        'filesystems.disks.s3.bucket' => 'test_bucket',
        'filesystems.default' => 's3'
    ]);

    // Fake Storage S3 pour les tests
    Storage::fake('s3');
    Storage::fake('public');
});

describe('Certificate Upload System', function () {

    it('uploads certificate PDF successfully to S3', function () {
        // Arrange
        $user = User::factory()->create();
        $pdfFile = UploadedFile::fake()->create('medical_certificate.pdf', 2048, 'application/pdf');

        $certificateData = [
            'title' => 'Certificat médical 2025',
            'file' => $pdfFile,
            'expires_at' => now()->addYear()->format('Y-m-d')
        ];

        // Act
        $response = $this->actingAs($user)
            ->post(route('certificats.store'), $certificateData);

        // Assert
        $response->assertRedirect()
            ->assertSessionHas('success', 'Certificat enregistré avec titre.');

        // Vérifier en base de données
        expect(Document::count())->toBe(1);
        expect(File::count())->toBe(1);

        $document = Document::first();
        expect($document->title)->toBe('Certificat médical 2025');
        expect($document->user_id)->toBe($user->id);
        expect($document->expiration_date->format('Y-m-d'))->toBe(now()->addYear()->format('Y-m-d'));

        $file = $document->file;
        expect($file->name)->toBe('medical_certificate');
        expect($file->extension)->toBe('pdf');
        expect($file->mimetype)->toBe('application/pdf');
        expect($file->size)->toBe(2048);
        expect($file->disk)->toBe('s3');

        // Vérifier le stockage S3
        expect($file->path)->toStartWith("certificate/{$user->id}/");
        Storage::disk('s3')->assertExists($file->path);

        // Vérifier le hash unique
        expect($file->hash)->not->toBeEmpty();
        expect(strlen($file->hash))->toBe(64); // SHA-256
    });

    it('validates certificate file types correctly', function () {
        // Arrange
        $user = User::factory()->create();
        $invalidFile = UploadedFile::fake()->create('malware.exe', 1024, 'application/exe');

        // Act
        $response = $this->actingAs($user)
            ->post(route('certificats.store'), [
                'title' => 'Test Certificate',
                'file' => $invalidFile,
                'expires_at' => now()->addYear()->format('Y-m-d')
            ]);

        // Assert
        $response->assertSessionHasErrors(['file']);
        expect(Document::count())->toBe(0);
        expect(File::count())->toBe(0);
    });

    it('prevents duplicate certificate upload by hash', function () {
        // Arrange
        $user = User::factory()->create();
        $content = 'fake pdf content for duplicate test';
        $hash = hash('sha256', $content);

        // Créer un fichier existant avec le même hash
        File::factory()->create([
            'fileable_id' => $user->id,
            'fileable_type' => get_class($user),
            'hash' => $hash,
            'disk' => 's3'
        ]);

        // Créer un fichier avec le même contenu
        $duplicateFile = UploadedFile::fake()->createWithContent('duplicate.pdf', $content);

        // Act
        $response = $this->actingAs($user)
            ->post(route('certificats.store'), [
                'title' => 'Duplicate Certificate',
                'file' => $duplicateFile,
                'expires_at' => now()->addYear()->format('Y-m-d')
            ]);

        // Assert
        $response->assertSessionHasErrors(['file' => 'Vous avez déjà importé un document identique.']);
        expect(Document::count())->toBe(0); // Aucun nouveau document
    });

    it('enforces file size limits', function () {
        // Arrange
        $user = User::factory()->create();
        $largeFile = UploadedFile::fake()->create('large_certificate.pdf', 15000, 'application/pdf'); // 15MB > 10MB limit

        // Act
        $response = $this->actingAs($user)
            ->post(route('certificats.store'), [
                'title' => 'Large Certificate',
                'file' => $largeFile,
                'expires_at' => now()->addYear()->format('Y-m-d')
            ]);

        // Assert
        $response->assertSessionHasErrors(['file']);
        expect(Document::count())->toBe(0);
    });

    it('validates expiration date correctly', function () {
        // Arrange
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('certificate.pdf', 1024, 'application/pdf');

        // Test avec date passée
        $response = $this->actingAs($user)
            ->post(route('certificats.store'), [
                'title' => 'Expired Certificate',
                'file' => $file,
                'expires_at' => now()->subDay()->format('Y-m-d')
            ]);

        // Assert
        $response->assertSessionHasErrors(['expires_at']);
        expect(Document::count())->toBe(0);
    });
});

describe('Profile Picture Upload System', function () {

    it('uploads profile picture successfully', function () {
        // Arrange
        $user = User::factory()->create();
        $imageFile = UploadedFile::fake()->image('profile.jpg', 800, 600);

        // Act
        $response = $this->actingAs($user)
            ->post(route('files.store.user.profile-picture'), [
                'photo' => $imageFile
            ]);

        // Assert
        $response->assertRedirect()
            ->assertSessionHas('success', 'Photo de profil mise à jour !');

        // Vérifier en base
        expect(File::count())->toBe(1);

        $file = File::first();
        expect($file->fileable_id)->toBe($user->id);
        expect($file->fileable_type)->toBe(get_class($user));
        expect($file->name)->toBe("profile_picture_{$user->id}");
        expect($file->extension)->toBe('jpg');
        expect($file->disk)->toBe('s3');

        // Vérifier le stockage S3
        expect($file->path)->toBe("user_profile_pictures/{$user->id}.jpg");
        Storage::disk('s3')->assertExists($file->path);

        // Vérifier la relation
        $user->refresh();
        expect($user->profilePicture)->not->toBeNull();
        expect($user->profilePicture->id)->toBe($file->id);
    });

    it('replaces existing profile picture', function () {
        // Arrange
        $user = User::factory()->create();

        // Créer une photo existante
        $oldFile = File::factory()->create([
            'fileable_id' => $user->id,
            'fileable_type' => get_class($user),
            'name' => "profile_picture_{$user->id}",
            'path' => "user_profile_pictures/{$user->id}.png",
            'disk' => 's3'
        ]);
        Storage::disk('s3')->put($oldFile->path, 'fake old image content');

        $newImage = UploadedFile::fake()->image('new_profile.jpg');

        // Act
        $response = $this->actingAs($user)
            ->post(route('files.store.user.profile-picture'), [
                'photo' => $newImage
            ]);

        // Assert
        $response->assertRedirect()
            ->assertSessionHas('success');

        // Vérifier que l'ancien fichier est supprimé
        Storage::disk('s3')->assertMissing($oldFile->path);
        expect(File::find($oldFile->id))->toBeNull();

        // Vérifier le nouveau fichier
        expect(File::count())->toBe(1);
        $newFile = File::first();
        expect($newFile->extension)->toBe('jpg');
        Storage::disk('s3')->assertExists($newFile->path);
    });

    it('prevents duplicate profile picture upload', function () {
        // Arrange
        $user = User::factory()->create();
        $content = 'fake image content for duplicate test';
        $hash = hash('sha256', $content);

        // Créer un fichier existant
        File::factory()->create([
            'fileable_id' => $user->id,
            'fileable_type' => get_class($user),
            'hash' => $hash,
            'disk' => 's3'
        ]);

        $duplicateImage = UploadedFile::fake()->createWithContent('duplicate.jpg', $content);

        // Act
        $response = $this->actingAs($user)
            ->post(route('files.store.user.profile-picture'), [
                'photo' => $duplicateImage
            ]);

        // Assert
        $response->assertSessionHasErrors(['photo' => 'Vous avez déjà importé cette image.']);
    });

    it('validates image format and size', function () {
        // Arrange
        $user = User::factory()->create();

        // Test format invalide
        $invalidFile = UploadedFile::fake()->create('document.txt', 100, 'text/plain');
        $response = $this->actingAs($user)
            ->post(route('files.store.user.profile-picture'), [
                'photo' => $invalidFile
            ]);
        $response->assertSessionHasErrors(['photo']);

        // Test taille trop grande
        $largeImage = UploadedFile::fake()->create('large.jpg', 15000, 'image/jpeg'); // 15MB > 10MB
        $response = $this->actingAs($user)
            ->post(route('files.store.user.profile-picture'), [
                'photo' => $largeImage
            ]);
        $response->assertSessionHasErrors(['photo']);

        expect(File::count())->toBe(0);
    });
});

describe('Upload Link System', function () {

    it('creates upload link with expiration', function () {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->post(route('upload-link.store'), [
                'title' => 'Certificat pour événement'
            ]);

        // Assert
        $response->assertRedirect()
            ->assertSessionHas('success');

        expect(UploadLink::count())->toBe(1);

        $link = UploadLink::first();
        expect($link->user_id)->toBe($user->id);
        expect($link->title)->toBe('Certificat pour événement');
        expect($link->token)->not->toBeEmpty();
        expect($link->expires_at)->toBeInstanceOf(Carbon::class);
        expect($link->expires_at->isFuture())->toBeTrue();
        expect($link->used_at)->toBeNull();
    });

    it('processes upload through upload link', function () {
        // Arrange
        $user = User::factory()->create();
        $uploadLink = UploadLink::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Upload',
            'expires_at' => now()->addDays(7)
        ]);

        $file = UploadedFile::fake()->create('certificate.pdf', 2048, 'application/pdf');

        // Act
        $response = $this->post(route('upload-link.upload', $uploadLink->token), [
            'title' => 'Mon certificat',
            'file' => $file,
            'expires_at' => now()->addYear()->format('Y-m-d')
        ]);

        // Assert
        $response->assertRedirect()
            ->assertSessionHas('success');

        // Vérifier que le document est créé
        expect(Document::count())->toBe(1);
        expect(File::count())->toBe(1);

        $document = Document::first();
        expect($document->user_id)->toBe($user->id);
        expect($document->title)->toBe('Mon certificat');

        // Vérifier que le lien est marqué comme utilisé
        $uploadLink->refresh();
        expect($uploadLink->used_at)->not->toBeNull();
    });

    it('prevents use of expired upload link', function () {
        // Arrange
        $user = User::factory()->create();
        $expiredLink = UploadLink::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->subDay() // Expiré
        ]);

        // Act
        $response = $this->get(route('upload-link.show', $expiredLink->token));

        // Assert
        $response->assertStatus(404); // Ou redirection avec erreur selon implémentation
    });

    it('prevents reuse of already used upload link', function () {
        // Arrange
        $user = User::factory()->create();
        $usedLink = UploadLink::factory()->create([
            'user_id' => $user->id,
            'used_at' => now()->subHour() // Déjà utilisé
        ]);

        // Act
        $response = $this->get(route('upload-link.show', $usedLink->token));

        // Assert
        $response->assertStatus(404);
    });
});

describe('File Access and Security', function () {

    it('generates secure public URL for certificates', function () {
        // Arrange
        $user = User::factory()->create();
        $file = File::factory()->create([
            'fileable_id' => $user->id,
            'fileable_type' => get_class($user),
            'path' => "certificate/{$user->id}/abc123_20251230.pdf",
            'disk' => 's3'
        ]);

        // Act
        $url = $file->url;

        // Assert
        expect($url)->toContain($user->id);
        expect($url)->toContain('abc123_20251230.pdf');
        expect($url)->toContain('/certificate/');
    });

    it('serves public certificate with valid token', function () {
        // Arrange
        $user = User::factory()->create();
        $futureDate = now()->addDays(30)->format('Ymd');
        $token = 'abc123_' . $futureDate . '.pdf';
        $filePath = "certificate/{$user->id}/{$token}";

        // Créer le fichier dans le storage fake
        Storage::disk('s3')->put($filePath, 'fake certificate content');

        // Act
        $response = $this->get(route('certificate.public', [
            'userId' => $user->id,
            'token' => $token
        ]));

        // Assert
        $response->assertRedirect(); // Redirection vers S3 URL
        expect($response->getTargetUrl())->toContain($filePath);
    });

    it('rejects expired certificate access', function () {
        // Arrange
        $user = User::factory()->create();
        $pastDate = now()->subDays(30)->format('Ymd');
        $expiredToken = 'expired123_' . $pastDate . '.pdf';
        $filePath = "certificate/{$user->id}/{$expiredToken}";

        Storage::disk('s3')->put($filePath, 'fake expired certificate');

        // Act
        $response = $this->get(route('certificate.public', [
            'userId' => $user->id,
            'token' => $expiredToken
        ]));

        // Assert
        $response->assertStatus(410); // Gone - certificat expiré
    });

    it('handles missing certificate files', function () {
        // Arrange
        $user = User::factory()->create();
        $nonExistentToken = 'nonexistent_20251230.pdf';

        // Act
        $response = $this->get(route('certificate.public', [
            'userId' => $user->id,
            'token' => $nonExistentToken
        ]));

        // Assert
        $response->assertStatus(404);
    });
});

describe('Storage Performance and Reliability', function () {

    it('handles large file uploads without memory issues', function () {
        // Arrange
        $user = User::factory()->create();
        $largeFile = UploadedFile::fake()->create('large_certificate.pdf', 8192, 'application/pdf'); // 8MB

        // Act
        $response = $this->actingAs($user)
            ->post(route('certificats.store'), [
                'title' => 'Large Certificate',
                'file' => $largeFile,
                'expires_at' => now()->addYear()->format('Y-m-d')
            ]);

        // Assert
        $response->assertRedirect()
            ->assertSessionHas('success');

        $file = File::first();
        expect($file->size)->toBe(8192);
        Storage::disk('s3')->assertExists($file->path);
    });

    it('handles concurrent file uploads', function () {
        // Arrange
        $users = User::factory()->count(3)->create();
        $files = [
            UploadedFile::fake()->create('cert1.pdf', 1024, 'application/pdf'),
            UploadedFile::fake()->create('cert2.pdf', 1024, 'application/pdf'),
            UploadedFile::fake()->create('cert3.pdf', 1024, 'application/pdf'),
        ];

        // Act - Simuler uploads simultanés
        $responses = [];
        foreach ($users as $index => $user) {
            $responses[] = $this->actingAs($user)
                ->post(route('certificats.store'), [
                    'title' => "Certificate {$index}",
                    'file' => $files[$index],
                    'expires_at' => now()->addYear()->format('Y-m-d')
                ]);
        }

        // Assert
        foreach ($responses as $response) {
            $response->assertRedirect()
                ->assertSessionHas('success');
        }

        expect(Document::count())->toBe(3);
        expect(File::count())->toBe(3);

        // Vérifier que tous les fichiers sont stockés
        File::all()->each(function ($file) {
            Storage::disk('s3')->assertExists($file->path);
        });
    });

    it('handles S3 connection failures gracefully', function () {
        // Arrange
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('certificate.pdf', 1024, 'application/pdf');

        // Simuler une panne S3 en utilisant un disque invalide temporairement
        config(['filesystems.disks.s3.driver' => 'invalid']);

        // Act & Assert
        expect(function () use ($user, $file) {
            $this->actingAs($user)
                ->post(route('certificats.store'), [
                    'title' => 'Test Certificate',
                    'file' => $file,
                    'expires_at' => now()->addYear()->format('Y-m-d')
                ]);
        })->toThrow(Exception::class);

        // Vérifier qu'aucune donnée corrompue n'est sauvée
        expect(Document::count())->toBe(0);
        expect(File::count())->toBe(0);
    });
});

describe('File Cleanup and Maintenance', function () {

    it('deletes associated S3 files when document is deleted', function () {
        // Arrange
        $user = User::factory()->create();
        $file = File::factory()->create([
            'fileable_id' => $user->id,
            'fileable_type' => get_class($user),
            'path' => "certificate/{$user->id}/test_file.pdf",
            'disk' => 's3'
        ]);

        $document = Document::factory()->create([
            'user_id' => $user->id,
            'file_id' => $file->id
        ]);

        // Créer le fichier dans le storage
        Storage::disk('s3')->put($file->path, 'test content');

        // Act
        $response = $this->actingAs($user)
            ->delete(route('certificats.destroy', $document));

        // Assert
        $response->assertRedirect()
            ->assertSessionHas('success');

        expect(Document::count())->toBe(0);
        expect(File::count())->toBe(0);
        Storage::disk('s3')->assertMissing($file->path);
    });

    it('cleans up expired upload links automatically', function () {
        // Arrange
        $user = User::factory()->create();

        // Créer des liens expirés et valides
        $expiredLinks = UploadLink::factory()->count(3)->create([
            'user_id' => $user->id,
            'expires_at' => now()->subDays(5)
        ]);

        $validLinks = UploadLink::factory()->count(2)->create([
            'user_id' => $user->id,
            'expires_at' => now()->addDays(5)
        ]);

        // Act - Accéder à la page qui déclenche le nettoyage
        $response = $this->actingAs($user)
            ->get(route('certificats'));

        // Assert
        $response->assertOk();

        // Vérifier que les liens expirés sont supprimés
        expect(UploadLink::count())->toBe(2); // Seuls les liens valides restent

        $remainingLinks = UploadLink::all();
        foreach ($remainingLinks as $link) {
            expect($link->expires_at->isFuture())->toBeTrue();
        }
    });

    it('maintains file integrity during batch operations', function () {
        // Arrange
        $user = User::factory()->create();
        $files = File::factory()->count(5)->create([
            'fileable_id' => $user->id,
            'fileable_type' => get_class($user),
            'disk' => 's3'
        ]);

        // Créer les fichiers physiques
        foreach ($files as $file) {
            Storage::disk('s3')->put($file->path, "content for file {$file->id}");
        }

        // Act - Supprimer l'utilisateur (cascade delete)
        $user->delete();

        // Assert - Vérifier que tous les fichiers S3 sont supprimés
        foreach ($files as $file) {
            Storage::disk('s3')->assertMissing($file->path);
        }

        expect(File::count())->toBe(0);
    });
});

describe('Error Recovery and Data Integrity', function () {

    it('recovers from partial upload failures', function () {
        // Arrange
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('certificate.pdf', 1024, 'application/pdf');

        // Simuler une panne pendant l'upload
        $originalConfig = config('filesystems.disks.s3');
        config(['filesystems.disks.s3.bucket' => 'invalid_bucket']);

        // Act
        $response = $this->actingAs($user)
            ->post(route('certificats.store'), [
                'title' => 'Test Certificate',
                'file' => $file,
                'expires_at' => now()->addYear()->format('Y-m-d')
            ]);

        // Restaurer la config
        config(['filesystems.disks.s3' => $originalConfig]);

        // Assert - Vérifier qu'aucune donnée partielle n'est sauvée
        expect(Document::count())->toBe(0);
        expect(File::count())->toBe(0);
    });

    it('validates file hash integrity after upload', function () {
        // Arrange
        $user = User::factory()->create();
        $fileContent = 'test content for hash validation';
        $expectedHash = hash('sha256', $fileContent);

        $file = UploadedFile::fake()->createWithContent('test.pdf', $fileContent);

        // Act
        $response = $this->actingAs($user)
            ->post(route('certificats.store'), [
                'title' => 'Hash Test Certificate',
                'file' => $file,
                'expires_at' => now()->addYear()->format('Y-m-d')
            ]);

        // Assert
        $response->assertRedirect()
            ->assertSessionHas('success');

        $uploadedFile = File::first();
        expect($uploadedFile->hash)->toBe($expectedHash);

        // Vérifier l'intégrité du contenu stocké
        $storedContent = Storage::disk('s3')->get($uploadedFile->path);
        expect(hash('sha256', $storedContent))->toBe($expectedHash);
    });
});
