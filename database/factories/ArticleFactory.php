<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\{User, Event};
use Carbon\Carbon;

class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $articleTypes = ['news', 'results', 'tutorial', 'announcement', 'event_info'];
        $type = $this->faker->randomElement($articleTypes);

        $titles = [
            'news' => [
                'Nouvelle saison sportive : les inscriptions sont ouvertes',
                'Partenariat avec la municipalité pour de nouveaux équipements',
                'L\'association se modernise avec une nouvelle application',
                'Bilan positif de l\'assemblée générale annuelle',
                'Arrivée de nouveaux encadrants dans l\'équipe',
                'Rénovation des vestiaires prévue cet été'
            ],
            'results' => [
                'Résultats du championnat départemental',
                'Beau succès au tournoi inter-clubs',
                'Nos jeunes brillent en compétition régionale',
                'Podium pour nos athlètes au Grand Prix',
                'Performance remarquable lors du dernier stage',
                'Félicitations à nos champions !'
            ],
            'tutorial' => [
                'Guide : Comment bien s\'échauffer avant l\'effort',
                'Technique : Les bases du mouvement parfait',
                'Conseils nutrition pour les sportifs',
                'Préparer sa première compétition',
                'L\'importance de la récupération après l\'entraînement',
                'Choisir son équipement : guide d\'achat'
            ],
            'announcement' => [
                'Assemblée générale extraordinaire le 15 mars',
                'Modification des horaires d\'entraînement',
                'Nouvelle procédure d\'inscription en ligne',
                'Fermeture exceptionnelle des installations',
                'Recherche bénévoles pour l\'organisation d\'événements',
                'Mise à jour du règlement intérieur'
            ],
            'event_info' => [
                'Tout savoir sur le prochain stage d\'été',
                'Préparation au championnat : programme spécial',
                'Sortie familiale : inscriptions ouvertes',
                'Formation encadrants : dernières places disponibles',
                'Compétition régionale : modalités de participation',
                'Camp d\'entraînement : programme détaillé'
            ]
        ];

        $publishDate = $this->faker->dateTimeBetween('-3 months', '+1 month');

        $metadata = null;
        if ($this->faker->boolean(20)) { // 20% de chance d'avoir des metadata
            $metadataArray = [
                'author_note' => $this->faker->sentence(),
                'reading_time' => $this->faker->numberBetween(2, 10),
            ];
            $metadata = json_encode($metadataArray);
        }

        return [
            'title' => $this->faker->randomElement($titles[$type]),
            'excerpt' => $this->generateExcerpt($type),
            'content' => $this->generateContent($type),
            'publish_date' => $publishDate,
            'event_id' => null, // Sera défini dans le seeder
            'is_pinned' => $this->faker->boolean(15), // 15% d'articles épinglés
            'is_post' => $this->faker->boolean(20), // 20% de posts courts
            'metadata' => $metadata,
            'status' => $this->faker->randomElement(['published', 'published', 'published', 'draft']), // 75% publiés
            'views_count' => $this->faker->numberBetween(10, 500),
            'user_id' => User::factory(),
        ];
    }

    private function generateExcerpt(string $type): string
    {
        $excerpts = [
            'news' => [
                'Découvrez les dernières actualités de notre association et les nouveautés de cette saison.',
                'Une année riche en projets nous attend avec de nombreuses améliorations prévues.',
                'Point sur les développements récents et les perspectives d\'avenir de l\'association.'
            ],
            'results' => [
                'Retour sur les performances de nos athlètes lors de la dernière compétition.',
                'Des résultats encourageants qui récompensent le travail de toute l\'équipe.',
                'Bilan des dernières épreuves avec des performances remarquables à retenir.'
            ],
            'tutorial' => [
                'Guide pratique pour améliorer votre pratique et progresser efficacement.',
                'Conseils d\'experts pour optimiser vos performances et éviter les blessures.',
                'Techniques et astuces pour perfectionner votre technique et atteindre vos objectifs.'
            ],
            'announcement' => [
                'Information importante concernant le fonctionnement de l\'association.',
                'Annonce officielle à destination de tous les membres de l\'association.',
                'Communication importante : merci de prendre connaissance de ces informations.'
            ],
            'event_info' => [
                'Toutes les informations pratiques pour participer à cet événement exceptionnel.',
                'Découvrez le programme détaillé et les modalités d\'inscription.',
                'Présentation complète de l\'événement avec tous les détails pratiques.'
            ]
        ];

        return $this->faker->randomElement($excerpts[$type]);
    }

    private function generateContent(string $type): string
    {
        $contents = [
            'news' => [
                "Nous sommes heureux de vous annoncer le lancement de la nouvelle saison sportive. Cette année s'annonce particulièrement riche avec de nombreux projets en cours.\n\nL'équipe dirigeante a travaillé tout l'été pour vous proposer un programme d'activités varié et adapté à tous les niveaux. Que vous soyez débutant ou confirmé, vous trouverez votre place dans notre association.\n\nLes inscriptions sont d'ores et déjà ouvertes et nous vous encourageons à ne pas tarder car les places sont limitées pour certaines activités. N'hésitez pas à nous contacter pour plus d'informations.",

                "C'est avec un grand plaisir que nous vous faisons part des dernières actualités de notre association. Grâce à l'engagement de tous nos bénévoles et la confiance de nos adhérents, nous continuons à développer nos activités.\n\nPlusieurs nouveautés vous attendent cette saison, notamment l'arrivée de nouveaux encadrants qualifiés qui enrichiront notre équipe pédagogique. Des investissements importants ont également été réalisés pour améliorer nos équipements.\n\nNous comptons sur votre participation active pour faire de cette nouvelle saison un véritable succès collectif."
            ],
            'results' => [
                "Nos athlètes ont brillamment représenté les couleurs de l'association lors du dernier championnat. Les résultats sont à la hauteur de l'investissement de chacun tout au long de la saison.\n\nNous tenons à féliciter particulièrement nos jeunes talents qui ont montré une progression remarquable. Leurs performances témoignent de la qualité de notre encadrement et de leur motivation exemplaire.\n\nCes excellents résultats nous encouragent à poursuivre nos efforts et à maintenir le cap pour les prochaines échéances. Bravo à tous les participants !",

                "Le week-end dernier s'est déroulée une compétition d'envergure régionale où notre association était représentée par une délégation de 15 athlètes. L'ambiance était au rendez-vous et le niveau général très relevé.\n\nAu terme de deux jours d'épreuves intenses, nos représentants ont obtenu des résultats très honorables avec plusieurs podiums à la clé. Ces performances récompensent des mois d'entraînement assidu et de préparation méthodique.\n\nRendez-vous est déjà pris pour la prochaine compétition où nous espérons confirmer ces bons résultats."
            ],
            'tutorial' => [
                "L'échauffement est une étape cruciale de toute séance sportive. Trop souvent négligé, il conditionne pourtant la qualité de votre entraînement et prévient les blessures.\n\nUn bon échauffement se déroule en trois phases : réveil articulaire, activation cardio-vasculaire et préparation spécifique. Comptez entre 15 et 20 minutes pour une préparation optimale.\n\nCommencez par des mouvements lents et progressifs, augmentez graduellement l'intensité et terminez par des gestes proches de votre activité principale. N'oubliez pas que l'échauffement doit s'adapter aux conditions extérieures et à votre forme du jour.",

                "La technique est la base de toute progression durable dans notre discipline. Bien maîtrisée, elle permet d'optimiser l'efficacité gestuelle tout en préservant l'intégrité physique.\n\nNous vous proposons une approche progressive en trois étapes : apprentissage du mouvement de base, travail de coordination et enfin recherche de fluidité. Chaque étape nécessite patience et répétition.\n\nN'hésitez pas à solliciter vos encadrants qui sauront corriger vos défauts et vous guider vers une technique irréprochable. La régularité de la pratique reste le secret d'une progression constante."
            ],
            'announcement' => [
                "Suite aux décisions prises lors de la dernière réunion du bureau, nous vous informons de modifications importantes dans l'organisation de nos activités.\n\nÀ compter du 1er du mois prochain, de nouveaux horaires d'entraînement entreront en vigueur. Ces changements ont été décidés pour mieux répartir l'utilisation de nos installations et optimiser l'encadrement.\n\nUn planning détaillé sera affiché dans nos locaux et envoyé par mail à tous les adhérents. Nous comptons sur votre compréhension et votre adaptation à ces nouvelles dispositions.",

                "L'assemblée générale annuelle de notre association aura lieu le samedi 15 mars à 14h30 dans la salle polyvalente de la mairie. La présence de tous les adhérents est vivement souhaitée.\n\nL'ordre du jour comprendra le bilan moral et financier de l'année écoulée, la présentation des projets pour la saison prochaine et l'élection du nouveau bureau. Les candidatures doivent être déposées avant le 1er mars.\n\nCette assemblée est un moment important de la vie associative. Votre participation témoigne de votre engagement et permet les échanges constructifs pour l'avenir de notre structure."
            ],
            'event_info' => [
                "Notre traditionnel stage d'été se déroulera du 15 au 22 juillet dans un cadre exceptionnel. Cette semaine intensive s'adresse à tous les niveaux avec un programme adapté à chaque groupe.\n\nAu menu : entraînements quotidiens, ateliers techniques, activités complémentaires et moments de détente. L'hébergement est prévu en pension complète avec des repas équilibrés adaptés aux sportifs.\n\nLes inscriptions sont ouvertes jusqu'au 30 mai dans la limite des places disponibles. N'attendez pas pour réserver votre participation à cet événement incontournable de notre saison !",

                "La compétition régionale approche à grands pas et les derniers préparatifs s'organisent. Cet événement majeur rassemblera les meilleurs athlètes de la région dans notre discipline.\n\nNotre association présentera une équipe de 20 compétiteurs sélectionnés sur leurs performances récentes. Un stage de préparation spécifique est organisé les deux week-ends précédant la compétition.\n\nNous comptons sur le soutien de tous nos adhérents pour encourager nos représentants. Les modalités de transport et d'hébergement seront communiquées ultérieurement aux participants."
            ]
        ];

        return $this->faker->randomElement($contents[$type]);
    }

    public function post(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_post' => true,
            'excerpt' => null,
            'content' => $this->faker->sentence(rand(10, 30)),
        ]);
    }

    public function pinned(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_pinned' => true,
        ]);
    }
}
