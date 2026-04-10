<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

/**
 * TagSeeder
 *
 * Tags thématiques contextualisés pour le Burkina Faso :
 *  - Tags de cagnottes : catégorisation selon l'objet social
 *  - Tags d'engagements : nature et périodicité des engagements
 */
class TagSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Création des tags...');

        // ─── Tags pour les cagnottes (cotisations) ────────────────────────────
        $tagsCotisations = [
            ['nom' => 'Solidarité',      'type' => 'cotisation', 'description' => 'Cagnotte à vocation de solidarité sociale ou humanitaire'],
            ['nom' => 'Patriotique',     'type' => 'cotisation', 'description' => 'Cagnotte de soutien national et patriotique'],
            ['nom' => 'Aide Familiale',  'type' => 'cotisation', 'description' => 'Soutien aux familles vulnérables (veuves, orphelins…)'],
            ['nom' => 'Communauté',      'type' => 'cotisation', 'description' => 'Cagnotte de groupement communautaire ou villageois'],
            ['nom' => 'Religieux',       'type' => 'cotisation', 'description' => 'Quêtes, dîmes, collectes d\'édifices religieux'],
            ['nom' => 'Éducation',       'type' => 'cotisation', 'description' => 'Financement de projets éducatifs (écoles, bourses…)'],
            ['nom' => 'Mémorial',        'type' => 'cotisation', 'description' => 'Cagnotte à caractère mémoriel ou commémoratif'],
            ['nom' => 'Urgence',         'type' => 'cotisation', 'description' => 'Collecte d\'urgence humanitaire ou médicale'],
            ['nom' => 'Diaspora',        'type' => 'cotisation', 'description' => 'Groupements et associations de la diaspora burkinabè'],
            ['nom' => 'Tontine',         'type' => 'cotisation', 'description' => 'Tontine rotatoire ou système d\'épargne informel'],
        ];

        // ─── Tags pour les engagements ────────────────────────────────────────
        $tagsEngagements = [
            ['nom' => 'Mensuel',         'type' => 'engagement', 'description' => 'Engagement à versement mensuel'],
            ['nom' => 'Hebdomadaire',    'type' => 'engagement', 'description' => 'Engagement à versement hebdomadaire'],
            ['nom' => 'Trimestriel',     'type' => 'engagement', 'description' => 'Engagement à versement trimestriel'],
            ['nom' => 'Annuel',          'type' => 'engagement', 'description' => 'Engagement à versement annuel'],
            ['nom' => 'Projet',          'type' => 'engagement', 'description' => 'Engagement lié à un projet de développement'],
            ['nom' => 'Infrastructure',  'type' => 'engagement', 'description' => 'Financement d\'infrastructure (bâtiment, puits, route…)'],
            ['nom' => 'Social',          'type' => 'engagement', 'description' => 'Engagement à finalité sociale'],
            ['nom' => 'Formation',       'type' => 'engagement', 'description' => 'Financement de formations et renforcement de capacités'],
        ];

        foreach ($tagsCotisations as $tag) {
            Tag::firstOrCreate(['nom' => $tag['nom'], 'type' => $tag['type']], $tag);
        }

        foreach ($tagsEngagements as $tag) {
            Tag::firstOrCreate(['nom' => $tag['nom'], 'type' => $tag['type']], $tag);
        }

        $this->command->info(count($tagsCotisations) . ' tags de cotisations créés.');
        $this->command->info(count($tagsEngagements) . ' tags d\'engagements créés.');
    }
}
