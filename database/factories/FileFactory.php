<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FileFactory extends Factory
{
    public function definition(): array
    {
        $extensions = ['pdf', 'jpg', 'png', 'doc', 'docx'];
        $extension = $this->faker->randomElement($extensions);

        $mimetypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        $filename = $this->faker->slug() . '.' . $extension;

        return [
            'name' => $filename,
            'extension' => $extension,
            'mimetype' => $mimetypes[$extension],
            'size' => $this->faker->numberBetween(1024, 5242880), // 1KB à 5MB
            'hash' => hash('sha256', $filename . time()),
            'path' => 'documents/' . date('Y/m/') . $filename,
            'disk' => 's3',
        ];
    }

    public function image(): static
    {
        $extension = $this->faker->randomElement(['jpg', 'png']);
        $filename = $this->faker->slug() . '.' . $extension;

        return $this->state(fn(array $attributes) => [
            'name' => $filename,
            'extension' => $extension,
            'mimetype' => $extension === 'jpg' ? 'image/jpeg' : 'image/png',
            'size' => $this->faker->numberBetween(50000, 2000000), // 50KB à 2MB
            'path' => 'images/' . date('Y/m/') . $filename,
        ]);
    }

    public function certificate(): static
    {
        $filename = $this->faker->slug() . '.pdf';

        return $this->state(fn(array $attributes) => [
            'name' => $filename,
            'extension' => 'pdf',
            'mimetype' => 'application/pdf',
            'size' => $this->faker->numberBetween(100000, 1000000), // 100KB à 1MB
            'path' => 'certificate/' . date('Y/') . $filename,
        ]);
    }
}
