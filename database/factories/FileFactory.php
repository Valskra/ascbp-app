<?php

// database/factories/FileFactory.php - Version simplifiée

namespace Database\Factories;

use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        $extensions = ['pdf', 'jpg', 'png', 'docx'];
        $extension = fake()->randomElement($extensions);

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        $name = fake()->slug(2);

        return [
            'name' => $name,
            'extension' => $extension,
            'mimetype' => $mimeTypes[$extension],
            'size' => fake()->numberBetween(1024, 5242880),
            'hash' => fake()->sha256(),
            'path' => "files/{$name}.{$extension}",
            'disk' => fake()->randomElement(['public', 's3']),
            'fileable_id' => null,
            'fileable_type' => null,
        ];
    }
}
