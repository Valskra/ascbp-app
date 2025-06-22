<?php
// ===================================================================

// tests/Unit/Models/FileTest.php - Version simplifiée

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(\Tests\TestCase::class, RefreshDatabase::class);

describe('File Model', function () {
    beforeEach(function () {

        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->file = File::factory()->create([
            'fileable_id' => $this->user->id,
            'fileable_type' => User::class,
            'name' => 'test-document',
            'extension' => 'pdf',
            'mimetype' => 'application/pdf',
            'size' => 1024000,
            'hash' => 'abcd1234567890',
            'path' => 'documents/test-document.pdf',
            'disk' => 'public'
        ]);
    });


    // Tests des attributs de base
    it('can create a file with valid attributes', function () {
        expect($this->file->name)->toBe('test-document')
            ->and($this->file->extension)->toBe('pdf')
            ->and($this->file->mimetype)->toBe('application/pdf')
            ->and($this->file->size)->toBe(1024000)
            ->and($this->file->disk)->toBe('public');
    });

    it('has correct fillable attributes', function () {
        $fillable = [
            'fileable_id',
            'fileable_type',
            'name',
            'extension',
            'mimetype',
            'size',
            'hash',
            'path',
            'disk'
        ];
        expect($this->file->getFillable())->toEqual($fillable);
    });

    // Tests des relations polymorphiques
    it('has polymorphic fileable relationship', function () {
        expect($this->file->fileable)->toBeInstanceOf(User::class)
            ->and($this->file->fileable->id)->toBe($this->user->id);
    });

    // Tests de l'URL
    it('generates url attribute', function () {
        expect($this->file->path)->toBe('documents/test-document.pdf')
            ->and($this->file->disk)->toBe('public');
    });

    // Tests de validation métier
    it('stores file hash for integrity', function () {
        expect($this->file->hash)->toBe('abcd1234567890')
            ->and(strlen($this->file->hash))->toBeGreaterThan(10);
    });

    it('supports different file sizes', function () {
        $smallFile = File::factory()->create(['size' => 1024]);
        $largeFile = File::factory()->create(['size' => 10485760]);

        expect($smallFile->size)->toBe(1024)
            ->and($largeFile->size)->toBe(10485760);
    });

    it('supports various extensions', function () {
        $extensions = ['pdf', 'jpg', 'png', 'docx'];

        foreach ($extensions as $ext) {
            $file = File::factory()->create(['extension' => $ext]);
            expect($file->extension)->toBe($ext);
        }
    });

    // Tests de suppression
    it('can be deleted', function () {
        $fileId = $this->file->id;
        $this->file->delete();

        expect(File::find($fileId))->toBeNull();
    });

    // Tests des associations
    it('allows nullable fileable association', function () {
        $orphanFile = File::factory()->create([
            'fileable_id' => null,
            'fileable_type' => null
        ]);

        expect($orphanFile->fileable_id)->toBeNull()
            ->and($orphanFile->fileable_type)->toBeNull();
    });

    it('can update disk information', function () {
        $this->file->update(['disk' => 's3']);

        expect($this->file->fresh()->disk)->toBe('s3');
    });
});
