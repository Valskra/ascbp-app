<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\{User, File};

class DocumentFactory extends Factory
{
    public function definition(): array
    {
        $documentTypes = [
            'Certificat médical',
            'Licence sportive',
            'Assurance responsabilité civile',
            'Autorisation parentale',
            'Pièce d\'identité',
            'Justificatif de domicile',
            'Photo d\'identité',
            'Attestation sur l\'honneur'
        ];

        $title = $this->faker->randomElement($documentTypes);

        // Date d'expiration selon le type de document
        $expirationDate = null;
        if (in_array($title, ['Certificat médical', 'Licence sportive', 'Assurance responsabilité civile'])) {
            $expirationDate = $this->faker->dateTimeBetween('now', '+2 years');
        }

        $metadata = null;
        if ($this->faker->boolean(30)) { // 30% de chance d'avoir des metadata
            $metadataArray = $this->faker->randomElement([
                ['category' => 'medical', 'doctor_name' => 'Dr. ' . $this->faker->lastName()],
                ['category' => 'administrative', 'issuing_authority' => $this->faker->company()],
                ['category' => 'insurance', 'policy_number' => $this->faker->numberBetween(100000, 999999)],
                ['category' => 'identity', 'document_number' => $this->faker->bothify('??######')],
            ]);
            $metadata = json_encode($metadataArray);
        }

        return [
            'title' => $title,
            'expiration_date' => $expirationDate,
            'metadata' => $metadata,
            'file_id' => File::factory()->certificate(),
            'user_id' => User::factory(),
        ];
    }

    public function medicalCertificate(): static
    {
        return $this->state(fn(array $attributes) => [
            'title' => 'Certificat médical',
            'expiration_date' => $this->faker->dateTimeBetween('+6 months', '+18 months'),
            'metadata' => [
                'category' => 'medical',
                'doctor_name' => 'Dr. ' . $this->faker->lastName(),
                'valid_for_competition' => $this->faker->boolean(80)
            ],
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn(array $attributes) => [
            'expiration_date' => $this->faker->dateTimeBetween('-6 months', '-1 day'),
        ]);
    }
}
