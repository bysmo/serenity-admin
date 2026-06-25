<?php

namespace Database\Seeders;

use App\Models\Membre;
use App\Models\Segment;
use App\Models\User;
use App\Models\KycVerification;
use App\Models\KycDocument;
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
        $createdMemberIds = [];

        for ($i = 1; $i <= $nbMembres; $i++) {
            $prenom = $prenoms[$i % count($prenoms)];
            $nom    = $noms[$i % count($noms)];

            // Téléphone stable basé sur l'index pour l'idempotence
            $telephone  = '+22670' . str_pad($i, 6, '0', STR_PAD_LEFT);
            $emailLocal = Str::ascii(strtolower($prenom . '.' . $nom . $i));
            $emailLocal = str_replace(' ', '', $emailLocal);
            $email = $emailLocal . '@' . $domaines[$i % count($domaines)];

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

            // Parrainage dynamique (à partir du 6ème membre)
            $parrainId = null;
            if ($i > 5 && !empty($createdMemberIds)) {
                $sponsorIndex = ($i % 5);
                if (isset($createdMemberIds[$sponsorIndex])) {
                    $parrainId = $createdMemberIds[$sponsorIndex];
                }
            }

            $membre = Membre::updateOrCreate(
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
                    'parrain_id'        => $parrainId,
                ]
            );

            // Générer/Récupérer le code de parrainage pour le membre
            $membre->getOrCreateCodeParrainage();

            $createdMemberIds[] = $membre->id;

            // Déclencher les commissions de parrainage à l'inscription
            if ($parrainId) {
                try {
                    app(\App\Services\ParrainageService::class)->genererCommissions($membre, 'inscription');
                } catch (\Exception $e) {
                    $this->command->error("Erreur parrainage pour membre #{$membre->id} : " . $e->getMessage());
                }
            }

            // Créer un compte utilisateur correspondant dans la table users
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $prenom . ' ' . $nom,
                    'password' => Hash::make('password'),
                    'email_verified_at' => Carbon::now(),
                ]
            );

            $isActif = ($membre->statut === 'actif');

            // Créer la demande de validation KYC en attente ou validée
            $kyc = KycVerification::updateOrCreate(
                ['membre_id' => $membre->id],
                [
                    'statut' => $isActif ? KycVerification::STATUT_VALIDE : KycVerification::STATUT_EN_ATTENTE,
                    'type_piece' => 'cni',
                    'numero_piece' => 'CNI-' . str_pad($i, 8, '0', STR_PAD_LEFT),
                    'date_naissance' => $membre->date_naissance ?? now()->subYears(rand(20, 50))->toDateString(),
                    'lieu_naissance' => $ville,
                    'adresse_kyc' => $adresse,
                    'metier' => 'Activité commerciale',
                    'localisation' => $quartier,
                    'contact_1' => $telephone,
                    'validated_at' => $isActif ? now() : null,
                    'validated_by' => $isActif ? 1 : null,
                ]
            );

            // Créer des documents KYC fictifs pour chaque demande
            $documentTypes = [
                KycDocument::TYPE_PIECE_IDENTITE_RECTO => 'cni_recto.jpg',
                KycDocument::TYPE_PIECE_IDENTITE_VERSO => 'cni_verso.jpg',
                KycDocument::TYPE_PHOTO_IDENTITE => 'photo.jpg',
                KycDocument::TYPE_JUSTIFICATIF_DOMICILE => 'facture.pdf',
            ];

            foreach ($documentTypes as $type => $fileName) {
                KycDocument::updateOrCreate(
                    [
                        'kyc_verification_id' => $kyc->id,
                        'type' => $type,
                    ],
                    [
                        'path' => 'kyc_documents/' . $kyc->id . '/' . $fileName,
                        'nom_original' => $fileName,
                    ]
                );
            }

            if ($isActif) {
                // Donner un solde positif au compte d'épargne
                $caisseEpargne = \App\Models\Caisse::where('membre_id', $membre->id)
                    ->where('type', 'epargne')
                    ->first();
                if ($caisseEpargne) {
                    $caisseEpargne->update([
                        'solde_initial' => (float) rand(5000, 20000),
                    ]);
                }

                // Créer une souscription à un plan d'épargne/tontine avec solde courant positif
                $plan = \App\Models\EpargnePlan::inRandomOrder()->first();
                if ($plan) {
                    \App\Models\EpargneSouscription::create([
                        'membre_id'     => $membre->id,
                        'plan_id'       => $plan->id,
                        'montant'       => rand((int)$plan->montant_min, min((int)$plan->montant_max, 20000)),
                        'date_debut'    => now()->subMonths(1),
                        'date_fin'      => now()->addMonths($plan->duree_mois - 1),
                        'jour_du_mois'  => rand(1, 28),
                        'statut'        => 'active',
                        'solde_courant' => rand(10000, 30000),
                    ]);
                }
            }

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
