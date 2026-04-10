<?php

namespace Database\Seeders;

use App\Models\Cotisation;
use App\Models\Caisse;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * CotisationSeeder
 *
 * Crée 10 cagnottes thématiques contextualisées au Burkina Faso :
 *   → 5 cagnottes PUBLIQUES (solidarité nationale, mémoire, aide humanitaire)
 *   → 5 cagnottes PRIVÉES   (associations, paroisses, communautés)
 *
 * Toutes créées via Cotisation::create() (Eloquent) → HasChecksum déclenche
 * le calcul du checksum et l'entrée dans le Merkle Ledger.
 *
 * En plus des 10 cagnottes fixes, génère 40 cagnottes aléatoires
 * pour remplir le jeu de données de démo (total : 50).
 */
class CotisationSeeder extends Seeder
{
    public function run(): void
    {
        $caisses = Caisse::where('statut', 'active')->get();

        if ($caisses->isEmpty()) {
            $this->command->warn('Aucune caisse active. Veuillez exécuter CaisseSeeder d\'abord.');
            return;
        }

        $this->command->info('Création des cagnottes (5 publiques + 5 privées + 40 demo)...');

        $tags = Tag::where('type', 'cotisation')->pluck('nom')->toArray();
        $created = 0;

        // ─────────────────────────────────────────────────────────────────────
        // CAGNOTTES PUBLIQUES — Solidarité & Mémoire nationale (Burkina Faso)
        // ─────────────────────────────────────────────────────────────────────
        $cotisationsPubliques = [
            [
                'nom'         => 'Fonds de Soutien Patriotique',
                'description' => 'Fonds national destiné à soutenir l\'effort de défense et de souveraineté du Burkina Faso. Contributions volontaires de tous les citoyens burkinabè, de la diaspora et des amis du Faso.',
                'notes'       => 'Cagnotte officielle. Contributions déductibles selon les dispositions légales en vigueur.',
                'type'        => 'reguliere',
                'frequence'   => 'mensuelle',
                'type_montant'=> 'libre',
                'montant'     => null,
                'visibilite'  => 'publique',
                'actif'       => true,
                'tag'         => $tags[0] ?? null,
            ],
            [
                'nom'         => 'Fonds Mémorial Thomas Sankara',
                'description' => 'Cagnotte dédiée à la construction du mémorial et au financement des activités commémoratives en l\'honneur du Président Thomas Isidore Noël Sankara, icône de la révolution africaine.',
                'notes'       => 'Objectif de collecte : 50 000 000 FCFA pour la première phase du mémorial.',
                'type'        => 'ponctuelle',
                'frequence'   => 'mensuelle',
                'type_montant'=> 'libre',
                'montant'     => null,
                'visibilite'  => 'publique',
                'actif'       => true,
                'tag'         => $tags[1] ?? null,
            ],
            [
                'nom'         => 'Fonds de Soutien aux Veuves des FDS',
                'description' => 'Aide financière mensuelle aux veuves et aux orphelins des Forces de Défense et de Sécurité (FDS) tombés au combat pour la défense du territoire burkinabè. Chaque contribution compte.',
                'notes'       => 'Versements trimestriels aux familles bénéficiaires identifiées par le comité de gestion.',
                'type'        => 'reguliere',
                'frequence'   => 'mensuelle',
                'type_montant'=> 'fixe',
                'montant'     => 2000,
                'visibilite'  => 'publique',
                'actif'       => true,
                'tag'         => $tags[2] ?? null,
            ],
            [
                'nom'         => 'Fonds de Soutien aux Plus Démunis',
                'description' => 'Collecte de solidarité pour les ménages les plus vulnérables du Burkina Faso : distributions alimentaires, kits scolaires, accès aux soins de santé de base pour les familles en grande précarité.',
                'notes'       => 'Partenariat avec les CSPS et les COGES locaux pour la distribution équitable.',
                'type'        => 'reguliere',
                'frequence'   => 'mensuelle',
                'type_montant'=> 'libre',
                'montant'     => null,
                'visibilite'  => 'publique',
                'actif'       => true,
                'tag'         => $tags[3] ?? null,
            ],
            [
                'nom'         => 'Fonds de Soutien aux Déplacés Internes',
                'description' => 'Aide d\'urgence pour les personnes déplacées internes (PDI) fuyant les zones d\'insécurité. Financement de denrées alimentaires, abris temporaires, couvertures, médicaments et scolarisation des enfants.',
                'notes'       => 'Coordination avec le Secrétariat Permanent du CONASUR pour les interventions terrain.',
                'type'        => 'exceptionnelle',
                'frequence'   => 'mensuelle',
                'type_montant'=> 'libre',
                'montant'     => null,
                'visibilite'  => 'publique',
                'actif'       => true,
                'tag'         => $tags[4] ?? null,
            ],
        ];

        // ─────────────────────────────────────────────────────────────────────
        // CAGNOTTES PRIVÉES — Associations, Communautés, Paroisses
        // ─────────────────────────────────────────────────────────────────────
        $cotisationsPrivees = [
            [
                'nom'         => 'Cotisations Association La Diaspora du Djoro',
                'description' => 'Fonds de l\'association des ressortissants du village de Djoro en diaspora. Objectif : financer les projets de développement local (puits, école, dispensaire) et maintenir le lien entre la diaspora et le village.',
                'notes'       => 'Cotisation mensuelle obligatoire pour les membres actifs de l\'association. Réunion de bilan chaque trimestre.',
                'type'        => 'reguliere',
                'frequence'   => 'mensuelle',
                'type_montant'=> 'fixe',
                'montant'     => 5000,
                'visibilite'  => 'privee',
                'actif'       => true,
                'tag'         => $tags[5] ?? ($tags[0] ?? null),
            ],
            [
                'nom'         => 'Fonds de Construction de l\'École Primaire de Babora',
                'description' => 'Collecte communautaire pour financer la construction et l\'équipement d\'une école primaire à Babora. Objectif : offrir un cadre scolaire digne aux enfants du village et réduire le taux d\'abandon scolaire.',
                'notes'       => 'Objectif total : 15 000 000 FCFA. Phase 1 en cours (fondations + murs). Rapport financier mensuel disponible aux membres.',
                'type'        => 'ponctuelle',
                'frequence'   => 'mensuelle',
                'type_montant'=> 'libre',
                'montant'     => null,
                'visibilite'  => 'privee',
                'actif'       => true,
                'tag'         => $tags[0] ?? null,
            ],
            [
                'nom'         => 'Quêtes Hebdomadaires — Paroisse Cathédrale de Ouagadougou',
                'description' => 'Collecte hebdomadaire de la Paroisse de la Cathédrale de l\'Immaculée Conception de Ouagadougou. Fonds destinés à l\'entretien de l\'édifice, au soutien des activités pastorales et caritatives de la paroisse.',
                'notes'       => 'Quête du dimanche. Reddition de comptes mensuelle au Conseil Paroissial d\'Affaires Économiques (CPAE).',
                'type'        => 'reguliere',
                'frequence'   => 'hebdomadaire',
                'type_montant'=> 'libre',
                'montant'     => null,
                'visibilite'  => 'privee',
                'actif'       => true,
                'tag'         => $tags[1] ?? null,
            ],
            [
                'nom'         => 'Dons à l\'Église pour les Orphelins de Ouagadougou',
                'description' => 'Fonds spéciaux de l\'Église pour le financement des centres d\'accueil pour orphelins et enfants vulnérables de Ouagadougou. Couvre les frais de scolarité, de santé, d\'alimentation et d\'hébergement.',
                'notes'       => 'Gestion conjointe avec la Commission Diocésaine pour la Pastorale Sociale. Audit annuel des comptes.',
                'type'        => 'reguliere',
                'frequence'   => 'mensuelle',
                'type_montant'=> 'libre',
                'montant'     => null,
                'visibilite'  => 'privee',
                'actif'       => true,
                'tag'         => $tags[2] ?? null,
            ],
            [
                'nom'         => 'Tontine Solidarité Femmes de Karpala',
                'description' => 'Tontine rotatoire du groupement des femmes commerçantes du quartier Karpala à Ouagadougou. Permet à chaque membre de bénéficier d\'un capital mensuel pour développer son activité génératrice de revenus.',
                'notes'       => 'Tour de bénéfice établi en début d\'année. Ordre déterminé par tirage au sort. Cotisation mensuelle fixe par membre.',
                'type'        => 'reguliere',
                'frequence'   => 'mensuelle',
                'type_montant'=> 'fixe',
                'montant'     => 10000,
                'visibilite'  => 'privee',
                'actif'       => true,
                'tag'         => $tags[3] ?? null,
            ],
        ];

        // ─── Créer les cagnottes fixes ────────────────────────────────────────
        foreach (array_merge($cotisationsPubliques, $cotisationsPrivees) as $data) {
            $caisse = $caisses->random();
            Cotisation::create(array_merge($data, [
                'numero'     => 'COT-' . strtoupper(Str::random(6)),
                'caisse_id'  => $caisse->id,
                'created_at' => Carbon::now()->subDays(rand(10, 365)),
                'updated_at' => Carbon::now()->subDays(rand(1, 10)),
            ]));
            $created++;
        }

        // ─── Cagnottes aléatoires de démo (40 supplémentaires) ───────────────
        $noms = [
            'Fonds de solidarité du secteur {n}',
            'Cagnotte de bienvenue — promo {n}',
            'Fonds d\'urgence médicale {n}',
            'Collecte mariage collectif {n}',
            'Aide funèbre famille {n}',
            'Fonds scolarisation {n}',
            'Cagnotte voyage groupé {n}',
            'Fonds réparation puits {n}',
            'Quête spéciale {n}',
            'Fonds baptême communautaire {n}',
        ];
        $types        = ['reguliere', 'ponctuelle', 'exceptionnelle'];
        $frequences   = ['journaliere', 'hebdomadaire', 'mensuelle'];
        $typeMontants = ['libre', 'fixe'];
        $visibilites  = ['publique', 'privee'];

        for ($i = 0; $i < 40; $i++) {
            $caisse      = $caisses->random();
            $type        = $types[array_rand($types)];
            $frequence   = $frequences[array_rand($frequences)];
            $typeMontant = $typeMontants[array_rand($typeMontants)];
            $montant     = $typeMontant === 'fixe' ? rand(1000, 50000) : null;
            $visibilite  = $visibilites[array_rand($visibilites)];
            $nom         = str_replace('{n}', $i + 1, $noms[$i % count($noms)]);
            $tag         = !empty($tags) ? ($i % 3 === 0 ? $tags[array_rand($tags)] : null) : null;

            Cotisation::create([
                'numero'      => 'COT-' . strtoupper(Str::random(6)),
                'nom'         => $nom,
                'caisse_id'   => $caisse->id,
                'type'        => $type,
                'frequence'   => $frequence,
                'type_montant'=> $typeMontant,
                'montant'     => $montant,
                'description' => 'Cagnotte de démonstration : ' . $nom,
                'notes'       => null,
                'actif'       => rand(1, 10) <= 8,
                'tag'         => $tag,
                'visibilite'  => $visibilite,
                'created_at'  => Carbon::now()->subDays(rand(1, 200)),
                'updated_at'  => Carbon::now()->subDays(rand(1, 30)),
            ]);
            $created++;
        }

        $this->command->info("{$created} cagnotte(s) créée(s) avec succès.");
        $this->command->info('  → 5 publiques (solidarité Burkina Faso)');
        $this->command->info('  → 5 privées (associations, paroisse, groupements)');
        $this->command->info('  → 40 démo aléatoires');
    }
}
