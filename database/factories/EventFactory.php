<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use Carbon\Carbon;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $categories = ['formation', 'competition', 'loisir', 'stage', 'sortie', 'reunion'];

        $eventTitles = [
            'formation' => [
                'Formation initiation débutants',
                'Stage perfectionnement technique',
                'Formation premiers secours',
                'Atelier technique avancée',
                'Formation encadrement',
                'Stage intensif weekend'
            ],
            'competition' => [
                'Championnat départemental',
                'Tournoi inter-clubs',
                'Compétition régionale',
                'Challenge de printemps',
                'Coupe d\'automne',
                'Grand Prix municipal'
            ],
            'loisir' => [
                'Sortie découverte nature',
                'Randonnée en groupe',
                'Activité familiale',
                'Journée détente',
                'Pique-nique annuel',
                'Balade découverte'
            ],
            'stage' => [
                'Stage été jeunes',
                'Stage perfectionnement adultes',
                'Stage vacances',
                'Camp d\'entraînement',
                'Stage technique spécialisé',
                'Séjour sportif'
            ],
            'sortie' => [
                'Visite musée local',
                'Sortie culturelle',
                'Excursion d\'une journée',
                'Découverte patrimoine',
                'Sortie nature guidée',
                'Visite site historique'
            ],
            'reunion' => [
                'Assemblée générale annuelle',
                'Réunion bureau',
                'Conseil d\'administration',
                'Réunion parents',
                'Réunion technique',
                'Point équipe encadrement'
            ]
        ];

        // Générer des dates cohérentes
        $category = $this->faker->randomElement($categories);

        // Déterminer si l'événement est passé, présent ou futur
        $timeframe = $this->faker->randomElement(['past', 'present', 'future']);

        switch ($timeframe) {
            case 'past':
                $startDate = $this->faker->dateTimeBetween('-6 months', '-1 month');
                break;
            case 'present':
                $startDate = $this->faker->dateTimeBetween('-2 weeks', '+2 weeks');
                break;
            case 'future':
                $startDate = $this->faker->dateTimeBetween('+1 week', '+6 months');
                break;
        }

        $startDate = Carbon::instance($startDate);

        // Durée de l'événement (1 jour à 7 jours selon le type)
        $duration = match ($category) {
            'reunion' => $this->faker->numberBetween(2, 4), // 2-4 heures
            'formation', 'loisir', 'sortie' => $this->faker->numberBetween(1, 2), // 1-2 jours
            'competition' => $this->faker->numberBetween(1, 3), // 1-3 jours
            'stage' => $this->faker->numberBetween(3, 7), // 3-7 jours
            default => 1
        };

        $endDate = $category === 'reunion'
            ? $startDate->copy()->addHours($duration)
            : $startDate->copy()->addDays($duration);

        // Dates d'inscription cohérentes
        $registrationOpen = $startDate->copy()->subDays($this->faker->numberBetween(30, 90));
        $registrationClose = $startDate->copy()->subDays($this->faker->numberBetween(1, 7));

        // Si l'événement est passé, s'assurer que les inscriptions sont fermées
        if ($timeframe === 'past') {
            $registrationClose = min($registrationClose, now()->subDays(1));
        }

        $title = $this->faker->randomElement($eventTitles[$category]);

        return [
            'title' => $title,
            'category' => $category,
            'description' => $this->generateDescription($category),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'registration_open' => $registrationOpen,
            'registration_close' => $registrationClose,
            'max_participants' => $this->faker->optional(0.7)->randomElement([10, 15, 20, 25, 30, 50]),
            'members_only' => $this->faker->boolean(40), // 40% réservés aux adhérents
            'requires_medical_certificate' => $category === 'competition' ? $this->faker->boolean(80) : $this->faker->boolean(20),
            'price' => $this->generatePrice($category),
            'organizer_id' => User::factory(),
        ];
    }

    private function generateDescription(string $category): string
    {
        $descriptions = [
            'formation' => [
                'Une formation complète pour acquérir les bases essentielles. Encadrement par des professionnels expérimentés.',
                'Stage technique destiné à perfectionner vos compétences. Matériel fourni, prévoir tenue adaptée.',
                'Formation théorique et pratique. Validation par attestation en fin de session.'
            ],
            'competition' => [
                'Compétition officielle ouverte à tous les niveaux. Inscription obligatoire avec certificat médical.',
                'Tournoi convivial dans un esprit sportif. Récompenses pour tous les participants.',
                'Épreuve qualificative pour les championnats régionaux. Niveau confirmé requis.'
            ],
            'loisir' => [
                'Activité détente en groupe dans une ambiance conviviale. Ouvert à toute la famille.',
                'Sortie découverte accessible à tous. Prévoir pique-nique et vêtements de saison.',
                'Moment de partage et de découverte. Animation assurée par l\'équipe encadrante.'
            ],
            'stage' => [
                'Stage intensif pour progresser rapidement. Programme adapté au niveau des participants.',
                'Séjour complet avec hébergement et restauration. Encadrement diplômé.',
                'Camp d\'entraînement avec programme personnalisé. Matériel technique fourni.'
            ],
            'sortie' => [
                'Découverte culturelle et patrimoniale de notre région. Transport organisé.',
                'Visite guidée suivie d\'un moment convivial. Réservation obligatoire.',
                'Excursion d\'une journée avec activités variées. Prévoir repas tiré du sac.'
            ],
            'reunion' => [
                'Réunion statutaire obligatoire. Présence vivement recommandée pour tous les adhérents.',
                'Point d\'information sur la vie de l\'association. Questions diverses et échanges.',
                'Séance de travail avec l\'équipe dirigeante. Ordre du jour envoyé par mail.'
            ]
        ];

        return $this->faker->randomElement($descriptions[$category]);
    }

    private function generatePrice(string $category): string
    {
        return match ($category) {
            'reunion' => 'Gratuit',
            'formation' => $this->faker->randomElement(['Gratuit', '15€', '25€', '35€']),
            'competition' => $this->faker->randomElement(['10€', '15€', '20€', '25€']),
            'loisir', 'sortie' => $this->faker->randomElement(['Gratuit', '5€', '10€', '15€']),
            'stage' => $this->faker->randomElement(['50€', '75€', '100€', '150€', '200€']),
            default => 'Gratuit'
        };
    }
}
