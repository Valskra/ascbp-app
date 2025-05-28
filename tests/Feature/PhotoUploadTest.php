<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\put;
use function Pest\Laravel\delete;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('s3');
});

function uploadPhotoToFileController(User $user, UploadedFile $file)
{
    return actingAs($user)->post(
        '/files/user-profile-picture',
        ['photo' => $file]
    );
}

function updatePhotoViaProfileController(User $user, UploadedFile $file)
{
    return actingAs($user)->put(
        '/profile/photo',
        ['photo' => $file]
    );
}

/** @test it_stores_valid_profile_picture */
it('stores valid profile picture', function () {
    $user = User::factory()->create();

    $response = uploadPhotoToFileController(
        $user,
        UploadedFile::fake()->image('avatar.jpg', 400, 400)
    );

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    /** @var \Illuminate\Support\Testing\Fakes\StorageFake $disk */
    $disk = Storage::disk('s3');
    $disk->assertExists("user_profile_pictures/{$user->id}.jpg");

    expect($user->fresh()->profilePicture)->not()->toBeNull();
});

/** @test it_rejects_invalid_profile_picture */
it('rejects invalid profile picture (exe file)', function () {
    $user = User::factory()->create();

    $response = uploadPhotoToFileController(
        $user,
        UploadedFile::fake()->create('virus.exe', 10)
    );

    $response->assertSessionHasErrors(['photo']);

    /** @var \Illuminate\Support\Testing\Fakes\StorageFake $disk */
    $disk = Storage::disk('s3');
    $disk->assertMissing("user_profile_pictures/{$user->id}.exe");
});

/** @test it_rejects_second_upload_of_same_picture */
it('rejects a second upload of the same profile picture', function () {
    $user = User::factory()->create();

    uploadPhotoToFileController(
        $user,
        UploadedFile::fake()->image('avatar.jpg', 400, 400)
    )->assertSessionHasNoErrors();

    $response = uploadPhotoToFileController(
        $user,
        UploadedFile::fake()->image('avatar.jpg', 400, 400)
    );

    $response->assertSessionHasErrors(['photo']);

    /** @var \Illuminate\Support\Testing\Fakes\StorageFake $disk */
    $disk = Storage::disk('s3');
    $disk->assertExists("user_profile_pictures/{$user->id}.jpg");
});
