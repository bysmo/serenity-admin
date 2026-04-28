<?php

namespace Database\Seeders;

use App\Models\Membre;
use App\Models\Segment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * Crée 50 clients de démonstration contextualisés Burkina Faso.
 * Chaque client est affecté à un segment de segmentation clientèle.
 *
 * Prérequis : SegmentSeeder doit être exécuté AVANT ce seeder.
 *
 * Distribution des segments (approximative sur 50 membres) :
 *   - NON CLASSÉ          : ~5  (10%) — clients récemment créés
 *   - Commerçant          : ~10 (20%) — secteur dominant en zone UEMOA
 *   - Fonctionnaire       : ~8  (16%)
 *   - Entreprise Informelle: ~7 (14%)
 *   - Artisan             : ~5  (10%)
 *   - Étudiant            : ~5  (10%)
 *   - Association         : ~4  (8%)
 *   - Communauté Religieuse: ~3 (6%)
 *   - Retraité            : ~2  (4%)
 *   - Autres              : ~1  chacun
 */
class MembreSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Création des clients...');

        // Charger les segments disponibles (SegmentSeeder doit être passé avant)
        $segments = Segment::where('actif', true)->get()->keyBy('slug');

        if ($segments->isEmpty()) {
            $this->command->error('Aucun segment trouvé ! Exécutez d\'abord SegmentSeeder.');
            return;
        }

        $defaultSegmentId = $segments->get('non-classe')?->id;

        // Distribution pondérée des segments (slug => poids)
        $segmentPoids = [
            'commercant'            => 10,
            'fonctionnaire'         => 8,
            'entreprise-informelle' => 7,
            'artisan'               => 5,
            'etudiant'              => 5,
            'association'           => 4,
            'communaute-religieuse' => 3,
            'retraite'              => 2,
            'entreprise-privee'     => 2,
            'ong'                   => 1,
            'diaspora'              => 1,
            'non-classe'            => 2,
        ];

        // Construire la liste pondérée
        $segmentsWeighted = [];
        foreach ($segmentPoids as $slug => $poids) {
            if ($segments->has($slug)) {
                for ($i = 0; $i < $poids; $i++) {
                    $segmentsWeighted[] = $segments->get($slug)->id;
                }
            }
        }

        $prenoms = [
            'Arouna', 'Abdoulaye', 'Issouf', 'Salif', 'Moussa', 'Issiaka',
            'Modeste', 'Céline', 'Fatimata', 'Aminata', 'Rasmane', 'Inoussa',
            'Adama', 'Mariam', 'Alizèta', 'Seydou', 'Oumar', 'Awa', 'Brice',
            'Clarisse', 'Désiré', 'Estelle', 'Fidèle', 'Grace', 'Hermann',
            'Joël', 'Kadiatou', 'Lassina', 'Mamadou', 'Nadège', 'Olivier',
            'Pascaline', 'Raïssa', 'Souleymane', 'Tégawendé', 'Ursule',
        ];

        $noms = [
            'SOMDA', 'OUEDRAOGO', 'BAMBA', 'KABORE', 'TRAORE', 'SANFO',
            'SANOU', 'ZONGO', 'TUINA', 'DIALLO', 'SANKARA', 'TALL',
            'DIARRA', 'KEITA', 'SISSOKO', 'CISSE', 'DEMBELE', 'NIANG',
            'SYLLA', 'TOURE', 'OUATTARA', 'SIDIBE', 'KAMATE', 'KANTE',
            'KONE', 'KOURBA', 'SOMTORE', 'TIENDREBEOGO', 'ZOMODO', 'SOME',
            'HIEN', 'LALSAGA', 'COMPAORE', 'NASSA', 'KASSAMBA', 'DAH',
            'BANAO', 'LOURE', 'LOMPO', 'ILBOUDO', 'TINDANO', 'BELEM',
        ];

        $domaines     = ['gmail.com', 'yahoo.fr', 'hotmail.com', 'outlook.com', 'fasonet.bf'];
        $villes       = [
            'Ouagadougou', 'Bobo-Dioulasso', 'Koudougou', 'Ouahigouya',
            'Banfora', 'Fada N\'Gourma', 'Kaya', 'Gaoua', 'Dédougou', 'Tenkodogo',
        ];
        $rues = [
            'Boulevard Thomas SANKARA', 'Rue de l\'Indépendance', 'Avenue de la Paix',
            'Rue de la Liberté', 'Avenue Bassawarga', 'Avenue de l\'Unité',
            'Avenue de la Fraternité', 'Avenue des Martyrs', 'Rue 15.42',
            'Secteur 30 — Zone du Bois', 'Hamdalaye', 'Gounghin',
        ];
        $quartiers = [
            'Ouaga 2000',
            'Karpala',
            'Somgandé',
            'Tampouy',
            'Saaba',
            'Bendogo',
            'Gounghin',
            'Bissighin',
            'Pissy',
            'Balkuy',
            'Kambouinsin',
            'Cissin',
            'Patte d\'Oie',
            'Yagma',
            'Zogona',
            'Zongo',
            'Boulmiougou',
            'Zagtouli',
            'Trame d\'accueil',
            'Dassasgho',
            'Kilwin',
            'Wemtenga',
            'Nagrin',
            'Kossodo',
            'Rimkieta',
            'Tanghin', 
        ];

        $nbMembres = 50; // Nombre de clients à créer
        $created = 0;

        for ($i = 1; $i <= $nbMembres; $i++) {
            $prenom = $prenoms[$i % count($prenoms)];
            $nom    = $noms[$i % count($noms)];

            // Téléphone stable basé sur l'index pour l'idempotence
            $telephone  = '+22670' . str_pad($i, 6, '0', STR_PAD_LEFT);
            $email = strtolower($prenom . '.' . $nom . $i . '@' . $domaines[$i % count($domaines)]);

            $ville = $villes[$i % count($villes)];
            $quartier = $quartiers[$i % count($quartiers)];
            $secteur = 'Secteur ' . (($i % 50) + 1);
            $rue = $rues[$i % count($rues)];
            $adresse = $quartier . ', ' . $ville . ', ' . $rue;
            
            $statut       = ($i % 10) < 8 ? 'actif' : 'inactif';
            $dateAdhesion = Carbon::now()->subDays($i * 10 % 730);

            $femmes = ['Céline', 'Fatimata', 'Aminata', 'Mariam', 'Alizèta', 'Awa', 'Clarisse', 'Estelle', 'Grace', 'Kadiatou', 'Nadège', 'Pascaline', 'Raïssa', 'Ursule'];
            $sexe = in_array($prenom, $femmes) ? 'F' : 'M';

            $segmentId = !empty($segmentsWeighted)
                ? $segmentsWeighted[$i % count($segmentsWeighted)]
                : $defaultSegmentId;

            try {
                $numero = app(\App\Services\AutoNumberingService::class)->generate('client');
            } catch (\Exception $e) {
                $numero = 'CLI-' . str_pad($i, 6, '0', STR_PAD_LEFT);
            }

            Membre::updateOrCreate(
                ['telephone' => $telephone], 
                [
                    'numero'            => $numero,
                    'nom'               => $nom,
                    'prenom'            => $prenom,
                    'sexe'              => $sexe,
                    'email'             => $email,
                    'adresse'           => $adresse,
                    'pays'              => 'Burkina Faso',
                    'ville'             => $ville,
                    'quartier'          => $quartier,
                    'secteur'           => $secteur,
                    'date_adhesion'     => $dateAdhesion,
                    'statut'            => $statut,
                    'segment_id'        => $segmentId,
                    'password'          => Hash::make('password'),
                    'email_verified_at' => Carbon::now(),
                    'created_at'        => $dateAdhesion,
                    'updated_at'        => $dateAdhesion,
                ]
            );

            $created++;
            if ($created % 10 == 0) {
                $this->command->info("Traitement {$created}/{$nbMembres} clients...");
            }
        }

        // Afficher la répartition par segment
        $this->command->info("{$created} client(s) créé(s) avec succès.");
        $this->command->newLine();
        $this->command->info('Répartition par segment :');
        foreach (Segment::withCount('membres')->get() as $seg) {
            if ($seg->membres_count > 0) {
                $this->command->info("  → {$seg->nom} : {$seg->membres_count} client(s)");
            }
        }
    }
}
