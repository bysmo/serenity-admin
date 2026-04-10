<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Annonce;
use App\Models\Caisse;
use App\Models\Cotisation;
use App\Models\CotisationAdhesion;
use App\Models\CotisationMessage;
use App\Models\EpargneEcheance;
use App\Models\EpargnePlan;
use App\Models\EpargneSouscription;
use App\Models\KycVerification;
use App\Models\Tag;
use App\Models\Segment;
use App\Services\PinService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MembreApiController extends Controller
{
    private function assertCotisationChatAccess(Request $request, Cotisation $cotisation): void
    {
        $membre = $request->user();
        $isAdmin = (int) ($cotisation->admin_membre_id ?? 0) === (int) $membre->id || (int) ($cotisation->created_by_membre_id ?? 0) === (int) $membre->id;
        if ($isAdmin) return;
        $adhesion = CotisationAdhesion::where('membre_id', $membre->id)->where('cotisation_id', $cotisation->id)->where('statut', 'accepte')->first();
        if (! $adhesion) {
            abort(403, 'Accès discussion refusé.');
        }
    }

    /**
     * Dashboard : stats, paiements récents, engagements en cours, annonces
     */
    public function dashboard(Request $request): JsonResponse
    {
        $membre = $request->user();
        $cotisationIds = CotisationAdhesion::where('membre_id', $membre->id)->where('statut', 'accepte')->pluck('cotisation_id');
        $cotisationsCreeesOuAdminIds = Cotisation::where(function ($q) use ($membre) {
            $q->where('created_by_membre_id', $membre->id)->orWhere('admin_membre_id', $membre->id);
        })->pluck('id');

        $paiementsMembre = $membre->paiements()->with(['cotisation', 'caisse'])->orderBy('date_paiement', 'desc')->get();
        $orphelins = \App\Models\Paiement::whereNull('membre_id')->whereIn('cotisation_id', $cotisationIds)->with(['cotisation', 'caisse'])->orderBy('date_paiement', 'desc')->get();
        $tousPaiements = $paiementsMembre->merge($orphelins)->unique('id')->sortByDesc('date_paiement')->values();
        $paiementsRecents = $tousPaiements->take(5)->map(fn ($p) => $this->formatPaiement($p));

        $engagementsEnCours = $membre->engagements()->whereIn('statut', ['en_cours', 'en_retard'])->orderBy('periode_fin', 'asc')->get();
        foreach ($engagementsEnCours as $e) {
            $e->checkAndUpdateStatut();
        }

        $epargnesActives = $membre->epargneSouscriptions()->where('statut', 'active')->count();
        $cotisationsPubliquesActives = Cotisation::where('actif', true)
            ->where(function ($q) {
                $q->where('visibilite', 'publique')->orWhereNull('visibilite');
            })
            ->count();
        $cotisationsPriveesActives = Cotisation::where('actif', true)
            ->where('visibilite', 'privee')
            ->whereIn('id', $cotisationsCreeesOuAdminIds)
            ->count();

        $annonces = Annonce::active()->orderBy('ordre')->orderBy('created_at')->get()->map(fn ($a) => [
            'id' => $a->id,
            'titre' => $a->titre,
            'contenu' => $a->contenu,
            'type' => $a->type,
        ]);

        // Évolution des paiements (6 derniers mois) pour graphique
        $paiements6Mois = $tousPaiements->filter(fn ($p) => $p->date_paiement && $p->date_paiement >= now()->subMonths(6));
        $evolutionPaiements = $paiements6Mois->groupBy(fn ($p) => $p->date_paiement?->format('Y-m'))
            ->map(fn ($items) => ['date' => $items->first()->date_paiement?->format('Y-m').'-01', 'total' => (float) $items->sum('montant')])
            ->values()->sortBy('date')->values();

        // Répartition par mode de paiement pour graphique
        $paiementsParMode = $tousPaiements->groupBy('mode_paiement')
            ->map(fn ($items, $mode) => ['mode_paiement' => $mode ?? 'autre', 'total' => (float) $items->sum('montant')])
            ->values();

        return response()->json([
            'membre' => $this->membreResource($membre),
            'stats' => [
                'total_paiements' => $tousPaiements->count(),
                'montant_total' => (float) $tousPaiements->sum('montant'),
                'engagements_en_cours' => $membre->engagements()->whereIn('statut', ['en_cours', 'en_retard'])->count(),
                'epargnes_actives' => $epargnesActives,
                'cotisations_publiques_actives' => $cotisationsPubliquesActives,
                'cotisations_privees_actives' => $cotisationsPriveesActives,
                'cotisations_creees' => Cotisation::where('created_by_membre_id', $membre->id)->count(),
            ],
            'evolution_paiements' => $evolutionPaiements,
            'paiements_par_mode' => $paiementsParMode,
            'paiements_recents' => $paiementsRecents,
            'engagements_en_cours' => $engagementsEnCours->map(fn ($e) => $this->formatEngagement($e)),
            'annonces' => $annonces,
        ]);
    }

    public function cotisationsPubliques(Request $request): JsonResponse
    {
        $membre = $request->user();
        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(1, min(50, $perPage));
        $cotisations = Cotisation::where('actif', true)->with('caisse')
            ->where(function ($q) {
                $q->where('visibilite', 'publique')->orWhereNull('visibilite');
            })
            ->orderBy('nom')
            ->paginate($perPage);
        $adhesions = CotisationAdhesion::where('membre_id', $membre->id)->get()->keyBy('cotisation_id');
        $list = $cotisations->getCollection()->map(fn ($c) => $this->formatCotisation($c, $adhesions->get($c->id)));
        return response()->json(['data' => $list, 'meta' => $this->paginateMeta($cotisations)]);
    }

    public function cotisationsPrivees(Request $request): JsonResponse
    {
        $membre = $request->user();
        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(1, min(50, $perPage));
        $ids = CotisationAdhesion::where('membre_id', $membre->id)->where('statut', 'accepte')->pluck('cotisation_id');
        $cotisations = Cotisation::where('actif', true)->with('caisse')->where('visibilite', 'privee')->whereIn('id', $ids)->orderBy('nom')->paginate($perPage);
        $adhesions = CotisationAdhesion::where('membre_id', $membre->id)->get()->keyBy('cotisation_id');
        $list = $cotisations->getCollection()->map(fn ($c) => $this->formatCotisation($c, $adhesions->get($c->id)));
        return response()->json(['data' => $list, 'meta' => $this->paginateMeta($cotisations)]);
    }

    public function rechercherCotisation(Request $request): JsonResponse
    {
        $code = $request->query('code');
        if (! $code || strlen(trim($code)) < 2) {
            return response()->json(['message' => 'Code requis.'], 422);
        }
        $cotisation = Cotisation::where('actif', true)->where('code', trim($code))->with('caisse')->first();
        if (! $cotisation) {
            return response()->json(['message' => 'Aucune cotisation trouvée pour ce code.'], 404);
        }
        $membre = $request->user();
        $adhesion = CotisationAdhesion::where('membre_id', $membre->id)->where('cotisation_id', $cotisation->id)->first();
        return response()->json(['cotisation' => $this->formatCotisation($cotisation, $adhesion)]);
    }

    public function adhererCotisation(Request $request, Cotisation $cotisation): JsonResponse
    {
        $membre = $request->user();
        if (! $cotisation->actif) {
            return response()->json(['message' => 'Cette cotisation n\'est plus active.'], 422);
        }
        $exist = CotisationAdhesion::where('membre_id', $membre->id)->where('cotisation_id', $cotisation->id)->first();
        if ($exist) {
            if ($exist->statut === 'accepte') {
                return response()->json(['message' => 'Vous \u00eates d\u00e9j\u00e0 adh\u00e9rent.', 'adhesion' => $this->formatAdhesion($exist)]);
            }
            if ($exist->statut === 'en_attente') {
                return response()->json(['message' => 'Demande d\u00e9j\u00e0 en attente.', 'adhesion' => $this->formatAdhesion($exist)]);
            }
        }
        if ($cotisation->isPublique()) {
            if (! $membre->isPinEnabled()) {
                return response()->json([
                    'message' => 'L\'accès aux cagnottes est bloqué tant que vous n\'avez pas activé votre code PIN. Rendez-vous dans Profil > Sécurité pour l\'activer.',
                    'require_pin_activation' => true,
                ], 403);
            }
            CotisationAdhesion::create(['membre_id' => $membre->id, 'cotisation_id' => $cotisation->id, 'statut' => 'accepte']);
            return response()->json(['message' => 'Vous avez adh\u00e9r\u00e9.', 'cotisation' => $this->formatCotisation($cotisation->fresh(), null)]);
        }
        // Cagnotte privée : PIN requis
        $pinError = app(PinService::class)->requirePin($request, $membre);
        if ($pinError) return $pinError;

        CotisationAdhesion::create(['membre_id' => $membre->id, 'cotisation_id' => $cotisation->id, 'statut' => 'en_attente']);
        return response()->json(['message' => 'Votre demande d\'adh\u00e9sion a \u00e9t\u00e9 envoy\u00e9e.', 'adhesion_statut' => 'en_attente']);
    }

    public function showCotisation(Request $request, $id): JsonResponse
    {
        $cotisation = Cotisation::with('caisse')->findOrFail($id);
        if (! $cotisation->actif) {
            return response()->json(['message' => 'Cotisation inactive.'], 403);
        }
        $membre = $request->user();
        $adhesion = CotisationAdhesion::where('membre_id', $membre->id)->where('cotisation_id', $cotisation->id)->first();
        if ($cotisation->isPrivee() && (! $adhesion || ! $adhesion->isAccepte())) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }
        $paydunyaEnabled = \App\Models\PayDunyaConfiguration::getActive() && \App\Models\PayDunyaConfiguration::getActive()->enabled;

        // Paiements du membre pour cette cotisation (quand adhésion acceptée)
        $paiements = collect();
        $totalPaye = 0.0;
        if ($adhesion && $adhesion->isAccepte()) {
            $paiementsMembre = \App\Models\Paiement::where('membre_id', $membre->id)
                ->where('cotisation_id', $cotisation->id)
                ->with('caisse')
                ->orderBy('date_paiement', 'desc')
                ->get();
            $orphelins = \App\Models\Paiement::whereNull('membre_id')
                ->where('cotisation_id', $cotisation->id)
                ->with('caisse')
                ->orderBy('date_paiement', 'desc')
                ->get();
            $paiements = $paiementsMembre->merge($orphelins)->unique('id')->sortByDesc('date_paiement')->values();
            $totalPaye = (float) $paiements->sum('montant');
        }

        return response()->json([
            'cotisation' => $this->formatCotisation($cotisation, $adhesion),
            'can_pay' => $adhesion && $adhesion->isAccepte(),
            'paydunya_enabled' => $paydunyaEnabled,
            'paiements' => $paiements->map(fn ($p) => $this->formatPaiement($p))->values()->all(),
            'total_paye' => $totalPaye,
        ]);
    }

    public function cotisationChatMessages(Request $request, Cotisation $cotisation): JsonResponse
    {
        $this->assertCotisationChatAccess($request, $cotisation);
        $afterId = (int) $request->query('after_id', 0);
        $q = CotisationMessage::where('cotisation_id', $cotisation->id)->with('membre:id,nom,prenom');
        if ($afterId > 0) $q->where('id', '>', $afterId);
        $items = $q->orderBy('id', 'asc')->limit(100)->get();

        return response()->json([
            'cotisation_active' => (bool) $cotisation->actif,
            'chat_notice' => $cotisation->chat_notice,
            'chat_notice_at' => $cotisation->chat_notice_at?->toIso8601String(),
            'messages' => $items->map(fn ($m) => [
                'id' => $m->id,
                'message' => $m->message,
                'created_at' => $m->created_at?->toIso8601String(),
                'membre' => [
                    'id' => $m->membre_id,
                    'nom_complet' => trim(($m->membre->prenom ?? '').' '.($m->membre->nom ?? '')),
                ],
            ])->values(),
        ]);
    }

    public function cotisationChatSend(Request $request, Cotisation $cotisation): JsonResponse
    {
        $this->assertCotisationChatAccess($request, $cotisation);
        if (! $cotisation->actif) {
            return response()->json(['message' => 'Discussion fermée : cotisation clôturée.'], 422);
        }
        $validated = $request->validate(['message' => 'required|string|min:1|max:2000']);
        $membre = $request->user();
        $msg = CotisationMessage::create([
            'cotisation_id' => $cotisation->id,
            'membre_id' => $membre->id,
            'message' => trim($validated['message']),
        ]);
        return response()->json(['ok' => true, 'id' => $msg->id], 201);
    }

    public function mesCotisationAdherentsStatus(Request $request, Cotisation $cotisation): JsonResponse
    {
        $membre = $request->user();
        if ($cotisation->created_by_membre_id !== $membre->id && $cotisation->admin_membre_id !== $membre->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }
        $cotisation->load(['adhesions' => fn ($q) => $q->where('statut', 'accepte')->with('membre')]);
        $adherentIds = $cotisation->adhesions->pluck('membre_id')->values();
        $paiementsParMembre = \App\Models\Paiement::where('cotisation_id', $cotisation->id)
            ->whereIn('membre_id', $adherentIds)
            ->get()
            ->groupBy('membre_id')
            ->map(fn ($items) => (float) $items->sum('montant'));
        $montantRequis = (float) ($cotisation->montant ?? 0);

        $items = $cotisation->adhesions->map(function ($a) use ($cotisation, $paiementsParMembre, $montantRequis) {
            $total = (float) ($paiementsParMembre[$a->membre_id] ?? 0);
            $aPaye = $cotisation->type_montant === 'fixe'
                ? ($montantRequis > 0 && $total >= $montantRequis)
                : ($total > 0);
            return [
                'adhesion_id' => $a->id,
                'membre' => $this->membreResource($a->membre),
                'total_paye' => $total,
                'a_paye' => $aPaye,
                'is_admin' => (int) $a->membre_id === (int) $cotisation->getAdminMembreId(),
            ];
        })->values();

        return response()->json([
            'payes' => $items->where('a_paye', true)->values(),
            'non_payes' => $items->where('a_paye', false)->values(),
        ]);
    }

    public function mesCotisationRetirerAdherent(Request $request, Cotisation $cotisation, CotisationAdhesion $adhesion): JsonResponse
    {
        $membre = $request->user();
        if ((int) $cotisation->getAdminMembreId() !== (int) $membre->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }
        if ((int) $adhesion->cotisation_id !== (int) $cotisation->id) {
            return response()->json(['message' => 'Adhésion introuvable.'], 404);
        }
        if ($adhesion->statut !== 'accepte') {
            return response()->json(['message' => 'Seuls les adhérents acceptés peuvent être retirés.'], 422);
        }
        if ((int) $adhesion->membre_id === (int) $cotisation->getAdminMembreId()) {
            return response()->json(['message' => 'Impossible de retirer l\'administrateur.'], 422);
        }
        $adhesion->delete();
        return response()->json(['ok' => true]);
    }

    public function mesCotisationRelancerNonPayes(Request $request, Cotisation $cotisation): JsonResponse
    {
        $membre = $request->user();
        if ((int) $cotisation->getAdminMembreId() !== (int) $membre->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }
        $adminId = (int) $cotisation->getAdminMembreId();
        $adherentIds = CotisationAdhesion::where('cotisation_id', $cotisation->id)->where('statut', 'accepte')->where('membre_id', '<>', $adminId)->pluck('membre_id')->values();
        if ($adherentIds->isEmpty()) return response()->json(['message' => 'Aucun adhérent.'], 422);

        $paiementsParMembre = \App\Models\Paiement::where('cotisation_id', $cotisation->id)
            ->whereIn('membre_id', $adherentIds)
            ->get()
            ->groupBy('membre_id')
            ->map(fn ($items) => (float) $items->sum('montant'));
        $montantRequis = (float) ($cotisation->montant ?? 0);
        $nonPayes = $adherentIds->filter(function ($mid) use ($cotisation, $paiementsParMembre, $montantRequis) {
            $total = (float) ($paiementsParMembre[$mid] ?? 0);
            return $cotisation->type_montant === 'fixe' ? !($montantRequis > 0 && $total >= $montantRequis) : !($total > 0);
        })->values();
        if ($nonPayes->isEmpty()) return response()->json(['message' => 'Tout le monde a payé.'], 200);

        $sent = 0;
        \App\Models\Membre::whereIn('id', $nonPayes)->chunkById(200, function ($membres) use ($cotisation, $paiementsParMembre, $montantRequis, &$sent) {
            foreach ($membres as $m) {
                $total = (float) ($paiementsParMembre[$m->id] ?? 0);
                $montantDu = 0.0;
                if ($cotisation->type_montant === 'fixe' && $montantRequis > 0) {
                    $montantDu = max(0.0, $montantRequis - $total);
                }
                $m->notify(new \App\Notifications\CotisationRelanceNotification($cotisation, $montantDu));
                $sent++;
            }
        });
        return response()->json(['message' => "Relance envoyée à {$sent} membre(s).", 'sent' => $sent]);
    }

    public function mesCotisationUpdateChatNotice(Request $request, Cotisation $cotisation): JsonResponse
    {
        $membre = $request->user();
        if ((int) $cotisation->getAdminMembreId() !== (int) $membre->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }
        $validated = $request->validate(['chat_notice' => 'nullable|string|max:2000']);
        $txt = trim((string) ($validated['chat_notice'] ?? ''));
        $cotisation->update([
            'chat_notice' => $txt === '' ? null : $txt,
            'chat_notice_by_membre_id' => $txt === '' ? null : $membre->id,
            'chat_notice_at' => $txt === '' ? null : now(),
        ]);
        return response()->json(['ok' => true, 'chat_notice' => $cotisation->chat_notice]);
    }

    public function mesCotisations(Request $request): JsonResponse
    {
        $membre = $request->user();
        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min(200, $perPage));
        $cotisations = Cotisation::where(fn ($q) => $q->where('created_by_membre_id', $membre->id)->orWhere('admin_membre_id', $membre->id))
            ->with(['caisse', 'adhesions'])->orderBy('created_at', 'desc')->paginate($perPage);
        $list = $cotisations->getCollection()->map(fn ($c) => array_merge($this->formatCotisation($c, null), ['adhesions_count' => $c->adhesions->count()]));
        return response()->json(['data' => $list, 'meta' => $this->paginateMeta($cotisations)]);
    }

    public function showMesCotisation(Request $request, Cotisation $cotisation): JsonResponse
    {
        $membre = $request->user();
        if ($cotisation->created_by_membre_id !== $membre->id && $cotisation->admin_membre_id !== $membre->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }
        $cotisation->load(['caisse', 'adhesions.membre']);
        return response()->json(['cotisation' => $this->formatCotisationDetail($cotisation)]);
    }

    /**
     * Données pour le formulaire de création (tags, etc.)
     */
    public function createMesCotisation(Request $request): JsonResponse
    {
        $tags = Tag::where('type', 'cotisation')->orderBy('nom')->pluck('nom')->toArray();
        return response()->json(['tags' => $tags]);
    }

    /**
     * Créer une cotisation (comme l'app web)
     */
    public function storeMesCotisation(Request $request): JsonResponse
    {
        $membre = $request->user();
        if (! $membre->isPinEnabled()) {
            return response()->json([
                'message' => 'La création de cagnotte est bloquée tant que vous n\'avez pas activé votre code PIN. Rendez-vous dans Profil > Sécurité pour l\'activer.',
                'require_pin_activation' => true,
            ], 403);
        }
        $rules = [
            'nom' => 'required|string|max:255',
            'visibilite' => 'required|in:publique,privee',
            'type' => 'required|string|in:reguliere,ponctuelle,exceptionnelle',
            'frequence' => 'required|in:mensuelle,trimestrielle,semestrielle,annuelle,unique',
            'type_montant' => 'required|in:libre,fixe',
            'description' => 'nullable|string',
            'tag' => 'nullable|string|max:255',
            'date_fin' => 'nullable|date',
            'penalite_type' => 'nullable|in:montant,pourcentage',
            'penalite_valeur_montant' => 'nullable|numeric|min:0',
            'penalite_valeur_pourcentage' => 'nullable|numeric|min:0|max:100',
        ];
        if ($request->input('type_montant') === 'fixe') {
            $rules['montant'] = 'required|numeric|min:1';
        } else {
            $rules['montant'] = 'nullable|numeric|min:0';
        }
        if ($request->input('penalite_type') === 'montant') {
            $rules['penalite_valeur_montant'] = 'required|numeric|min:0';
        }
        if ($request->input('penalite_type') === 'pourcentage') {
            $rules['penalite_valeur_pourcentage'] = 'required|numeric|min:0|max:100';
        }
        $validated = $request->validate($rules);

        $caisse = Caisse::create([
            'numero' => $this->generateNumeroCaisse(),
            'nom' => 'Caisse - '.$validated['nom'],
            'description' => 'Caisse de collecte pour la cotisation "'.$validated['nom'].'"',
            'statut' => 'active',
            'solde_initial' => 0,
        ]);

        $penaliteType = $validated['penalite_type'] ?? null;
        $penaliteValeur = null;
        if ($penaliteType === 'montant' && isset($validated['penalite_valeur_montant'])) {
            $penaliteValeur = (float) $validated['penalite_valeur_montant'];
        }
        if ($penaliteType === 'pourcentage' && isset($validated['penalite_valeur_pourcentage'])) {
            $penaliteValeur = (float) $validated['penalite_valeur_pourcentage'];
        }

        $cotisation = Cotisation::create([
            'numero' => $this->generateNumeroCotisation(),
            'code' => $this->generateCodeCotisation(),
            'nom' => $validated['nom'],
            'caisse_id' => $caisse->id,
            'created_by_membre_id' => $membre->id,
            'type' => $validated['type'],
            'frequence' => $validated['frequence'],
            'type_montant' => $validated['type_montant'],
            'montant' => $validated['type_montant'] === 'libre' ? null : $validated['montant'],
            'description' => $validated['description'] ?? null,
            'tag' => $validated['tag'] ?? null,
            'visibilite' => $validated['visibilite'],
            'actif' => true,
            'date_fin' => !empty($validated['date_fin']) ? $validated['date_fin'] : null,
            'penalite_type' => $penaliteType,
            'penalite_valeur' => $penaliteValeur,
        ]);

        CotisationAdhesion::create([
            'membre_id' => $membre->id,
            'cotisation_id' => $cotisation->id,
            'statut' => 'accepte',
        ]);

        return response()->json([
            'message' => 'Cotisation créée. Votre code de partage : '.$cotisation->code,
            'cotisation' => $this->formatCotisation($cotisation->load('caisse'), null),
        ], 201);
    }

    private function generateNumeroCaisse(): string
    {
        do {
            $numero = strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
        } while (Caisse::where('numero', $numero)->exists());

        return $numero;
    }

    private function generateNumeroCotisation(): string
    {
        do {
            $numero = 'COT-'.strtoupper(Str::random(8));
        } while (Cotisation::where('numero', $numero)->exists());

        return $numero;
    }

    private function generateCodeCotisation(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (Cotisation::where('code', $code)->exists());

        return $code;
    }

    public function paiements(Request $request): JsonResponse
    {
        $membre = $request->user();
        $annee = $request->query('annee');
        $query = $membre->paiements()->with(['cotisation', 'caisse'])->orderBy('date_paiement', 'desc');
        if ($annee) {
            $query->whereYear('date_paiement', (int) $annee);
        }
        $paiements = $query->paginate(15);
        $list = $paiements->getCollection()->map(fn ($p) => $this->formatPaiement($p));
        $annees = $membre->paiements()->selectRaw('YEAR(date_paiement) as annee')->distinct()->orderByDesc('annee')->pluck('annee');
        return response()->json(['data' => $list, 'meta' => $this->paginateMeta($paiements), 'annees' => $annees]);
    }

    public function engagements(Request $request): JsonResponse
    {
        $membre = $request->user();
        $engagements = $membre->engagements()->with('cotisation')->orderBy('periode_fin', 'desc')->paginate(15);
        $list = $engagements->getCollection()->map(fn ($e) => $this->formatEngagement($e));
        return response()->json(['data' => $list, 'meta' => $this->paginateMeta($engagements)]);
    }

    public function showEngagement(Request $request, $id): JsonResponse
    {
        $engagement = $request->user()->engagements()->with('cotisation')->findOrFail($id);
        return response()->json(['engagement' => $this->formatEngagement($engagement)]);
    }

    public function remboursements(Request $request): JsonResponse
    {
        $membre = $request->user();
        $list = $membre->remboursements()->with('paiement')->orderBy('created_at', 'desc')->paginate(15);
        $data = $list->getCollection()->map(fn ($r) => [
            'id' => $r->id,
            'montant' => (float) $r->montant,
            'statut' => $r->statut,
            'created_at' => $r->created_at?->toIso8601String(),
        ]);
        return response()->json(['data' => $data, 'meta' => $this->paginateMeta($list)]);
    }

    public function kyc(Request $request): JsonResponse
    {
        $membre = $request->user();
        $kyc = \App\Models\KycVerification::where('membre_id', $membre->id)->latest()->first();
        $data = null;
        if ($kyc) {
            $data = [
                'id' => $kyc->id,
                'statut' => $kyc->statut,
                'motif_rejet' => $kyc->motif_rejet,
                'updated_at' => $kyc->updated_at?->toIso8601String(),
                'type_piece' => $kyc->type_piece,
                'numero_piece' => $kyc->numero_piece,
                'date_naissance' => $kyc->date_naissance?->format('Y-m-d'),
                'lieu_naissance' => $kyc->lieu_naissance,
                'adresse_kyc' => $kyc->adresse_kyc,
                'metier' => $kyc->metier,
                'localisation' => $kyc->localisation,
                'contact_1' => $kyc->contact_1,
                'contact_2' => $kyc->contact_2,
            ];
        }
        return response()->json(['kyc' => $data]);
    }

    public function kycStore(Request $request): JsonResponse
    {
        $membre = $request->user();
        $validated = $request->validate([
            'type_piece' => 'required|string|in:cni,passeport,permis',
            'numero_piece' => 'required|string|max:100',
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'required|string|max:255',
            'adresse_kyc' => 'required|string',
            'metier' => 'nullable|string|max:255',
            'localisation' => 'nullable|string|max:255',
            'contact_1' => 'nullable|string|max:50',
            'contact_2' => 'nullable|string|max:50',
            'piece_identite_recto' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'piece_identite_verso' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'photo_identite' => 'required|file|mimes:jpg,jpeg,png|max:5120',
            'justificatif_domicile' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
        $kyc = $membre->kycVerification;
        if ($kyc && $kyc->isRejete()) {
            foreach ($kyc->documents as $doc) {
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($doc->path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($doc->path);
                }
            }
            $kyc->documents()->delete();
            $kyc->update([
                'statut' => KycVerification::STATUT_EN_ATTENTE,
                'motif_rejet' => null,
                'rejected_at' => null,
                'rejected_by' => null,
                'type_piece' => $validated['type_piece'],
                'numero_piece' => $validated['numero_piece'],
                'date_naissance' => $validated['date_naissance'],
                'lieu_naissance' => $validated['lieu_naissance'],
                'adresse_kyc' => $validated['adresse_kyc'],
                'metier' => $validated['metier'] ?? null,
                'localisation' => $validated['localisation'] ?? null,
                'contact_1' => $validated['contact_1'] ?? null,
                'contact_2' => $validated['contact_2'] ?? null,
            ]);
        } elseif ($kyc && ($kyc->isEnAttente() || $kyc->isValide())) {
            return response()->json(['message' => $kyc->isValide() ? 'Votre KYC est déjà validé.' : 'Votre KYC est déjà en cours d\'examen.'], 422);
        } else {
            $kyc = KycVerification::create([
                'membre_id' => $membre->id,
                'statut' => KycVerification::STATUT_EN_ATTENTE,
                'type_piece' => $validated['type_piece'],
                'numero_piece' => $validated['numero_piece'],
                'date_naissance' => $validated['date_naissance'],
                'lieu_naissance' => $validated['lieu_naissance'],
                'adresse_kyc' => $validated['adresse_kyc'],
                'metier' => $validated['metier'] ?? null,
                'localisation' => $validated['localisation'] ?? null,
                'contact_1' => $validated['contact_1'] ?? null,
                'contact_2' => $validated['contact_2'] ?? null,
            ]);
        }
        $basePath = 'kyc_documents/'.$kyc->id;
        $documentInputs = [
            'piece_identite_recto' => \App\Models\KycDocument::TYPE_PIECE_IDENTITE_RECTO,
            'piece_identite_verso' => \App\Models\KycDocument::TYPE_PIECE_IDENTITE_VERSO,
            'photo_identite' => \App\Models\KycDocument::TYPE_PHOTO_IDENTITE,
            'justificatif_domicile' => \App\Models\KycDocument::TYPE_JUSTIFICATIF_DOMICILE,
        ];
        foreach ($documentInputs as $inputKey => $type) {
            if ($request->hasFile($inputKey)) {
                $file = $request->file($inputKey);
                $path = $file->store($basePath, 'public');
                \App\Models\KycDocument::create([
                    'kyc_verification_id' => $kyc->id,
                    'type' => $type,
                    'path' => $path,
                    'nom_original' => $file->getClientOriginalName(),
                ]);
            }
        }
        return response()->json(['message' => 'KYC enregistré. Votre dossier sera examiné par l\'administration.', 'kyc' => ['id' => $kyc->id, 'statut' => $kyc->statut]]);
    }

    public function nanoCreditsIndex(Request $request): JsonResponse
    {
        $membre = $request->user();
        $palier = $membre->nanoCreditPalier;
        
        if (!$palier && $membre->hasKycValide()) {
            app(\App\Services\NanoCreditPalierService::class)->assignerPalierInitial($membre);
            $membre->refresh();
            $palier = $membre->nanoCreditPalier;
        }

        return response()->json([
            'has_kyc_valide' => $membre->hasKycValide(),
            'has_credit_en_cours' => $membre->hasCreditEnCours(),
            'palier' => $palier ? [
                'id' => $palier->id,
                'nom' => $palier->nom,
                'montant_plafond' => (float) $palier->montant_plafond,
                'duree_jours' => (int) $palier->duree_jours,
                'taux_interet' => (float) $palier->taux_interet,
                'nombre_garants' => (int) $palier->nombre_garants,
                'frequence_remboursement' => $palier->frequence_remboursement,
                'frequence_remboursement_label' => $palier->frequence_remboursement_label,
                'min_epargne_percent' => (float) $palier->min_epargne_percent,
            ] : null
        ]);
    }

    public function nanoCreditsMes(Request $request): JsonResponse
    {
        $membre = $request->user();
        $list = $membre->nanoCredits()->with('palier')->orderBy('created_at', 'desc')->paginate(15);
        $data = $list->getCollection()->map(fn ($n) => [
            'id' => $n->id,
            'montant' => (float) ($n->montant ?? 0),
            'statut' => $n->statut,
            'date_octroi' => $n->date_octroi?->format('Y-m-d'),
            'palier' => $n->palier ? ['id' => $n->palier->id, 'nom' => $n->palier->nom] : null,
        ]);
        return response()->json(['data' => $data, 'meta' => $this->paginateMeta($list)]);
    }

    public function nanoCreditStoreDemande(Request $request): JsonResponse
    {
        $membre = $request->user();
        if (! $membre->hasKycValide()) {
            return response()->json(['message' => 'KYC requis.'], 403);
        }
        $palier = $membre->nanoCreditPalier;
        if (!$palier) {
            return response()->json(['message' => 'Aucun palier assign\u00e9.'], 403);
        }
        if (! $membre->isPinEnabled()) {
            return response()->json([
                'message' => 'L\'accès aux nano-crédits est bloqué tant que vous n\'avez pas activé votre code PIN. Rendez-vous dans Profil > Sécurité pour l\'activer.',
                'require_pin_activation' => true,
            ], 403);
        }
        if ($membre->hasCreditEnCours()) {
            return response()->json(['message' => 'Vous avez d\u00e9j\u00e0 un cr\u00e9dit en cours.'], 422);
        }

        // Vérification du PIN (opération critique : demande de nano-crédit)
        $pinError = app(PinService::class)->requirePin($request, $membre);
        if ($pinError) return $pinError;

        $validated = $request->validate([
            'montant'    => 'required|numeric|min:1000|max:'.$palier->montant_plafond,
            'garant_ids' => 'required|array|size:'.$palier->nombre_garants,
            'garant_ids.*' => 'required|exists:membres,id',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $nanoCredit = \App\Models\NanoCredit::create([
                'palier_id'  => $palier->id,
                'membre_id'  => $membre->id,
                'montant'    => (int) round($validated['montant'], 0),
                'statut'     => 'demande_en_attente',
            ]);

            foreach ($validated['garant_ids'] as $garantId) {
                $garantMembre = \App\Models\Membre::findOrFail($garantId);
                if (!\App\Models\NanoCreditGarant::membreEstEligibleGarant($garantMembre, $nanoCredit)) {
                    throw new \Exception("Le membre {$garantMembre->nom_complet} n'est pas \u00e9ligible comme garant.");
                }

                $garantRecord = \App\Models\NanoCreditGarant::create([
                    'nano_credit_id' => $nanoCredit->id,
                    'membre_id'      => $garantId,
                    'statut'         => 'en_attente',
                ]);
                $garantMembre->notify(new \App\Notifications\GarantSollicitationNotification($garantRecord));
            }

            \Illuminate\Support\Facades\DB::commit();

            // Notification admin
            $admins = \App\Models\User::whereHas('roles', fn ($q) => $q->where('slug', 'admin')->where('actif', true))->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\NanoCreditDemandeNotification($nanoCredit));
            }

            return response()->json([
                'message'     => 'Demande enregistr\u00e9e. Vos garants ont \u00e9t\u00e9 notifi\u00e9s.',
                'nano_credit' => ['id' => $nanoCredit->id, 'montant' => (float) $nanoCredit->montant, 'statut' => $nanoCredit->statut],
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['message' => 'Erreur : '.$e->getMessage()], 422);
        }
    }

    public function nanoCreditShow(Request $request, $id): JsonResponse
    {
        $membre = $request->user();
        $nanoCredit = \App\Models\NanoCredit::where('membre_id', $membre->id)->with(['palier', 'echeances', 'versements', 'garants.membre'])->findOrFail($id);
        return response()->json([
            'nano_credit' => [
                'id' => $nanoCredit->id,
                'montant' => (float) ($nanoCredit->montant ?? 0),
                'statut' => $nanoCredit->statut,
                'date_octroi' => $nanoCredit->date_octroi?->format('Y-m-d'),
                'palier' => $nanoCredit->palier ? ['nom' => $nanoCredit->palier->nom, 'taux' => $nanoCredit->palier->taux_interet] : null,
                'garants' => $nanoCredit->garants->map(fn($g) => [
                    'nom' => $g->membre->nom_complet,
                    'statut' => $g->statut,
                    'motif_refus' => $g->motif_refus
                ]),
                'echeances' => $nanoCredit->echeances->map(fn ($e) => [
                    'id' => $e->id,
                    'date_echeance' => $e->date_echeance?->format('Y-m-d'),
                    'montant' => (float) ($e->montant ?? 0),
                    'statut' => $e->statut ?? 'a_venir',
                ]),
                'versements' => $nanoCredit->versements->map(fn ($v) => [
                    'date' => $v->date_versement?->format('Y-m-d'),
                    'montant' => (float) ($v->montant ?? 0),
                ]),
            ],
        ]);
    }

    public function nanoCreditSearchGuarantors(Request $request): JsonResponse
    {
        $search = $request->query('q');
        $membre = $request->user();
        $palier = $membre->nanoCreditPalier;
        if (!$palier) return response()->json([]);

        $results = \App\Models\Membre::where('id', '!=', $membre->id)
            ->where('statut', 'actif')
            ->whereHas('kycVerification', fn($q) => $q->where('statut', 'valide'))
            ->where('garant_qualite', '>=', $palier->min_garant_qualite ?? 0)
            ->where(function($q) use ($search) {
                if ($search) {
                    $q->where('nom', 'like', "%{$search}%")
                      ->orWhere('prenom', 'like', "%{$search}%")
                      ->orWhere('telephone', 'like', "%{$search}%");
                }
            })
            ->limit(20)
            ->get()
            ->filter(fn($m) => !$m->aAtteintLimiteGaranties())
            ->map(fn($m) => [
                'id' => $m->id,
                'text' => $m->nom_complet . " (" . $m->telephone . ")",
                'qualite' => $m->garant_qualite,
            ])->values();

        return response()->json($results);
    }

    public function nanoCreditUpdateGarants(Request $request, $id): JsonResponse
    {
        $membre = $request->user();
        $nanoCredit = \App\Models\NanoCredit::where('membre_id', $membre->id)->findOrFail($id);
        $nbRefuses = $nanoCredit->garants()->where('statut', 'refuse')->count();
        if ($nbRefuses === 0) return response()->json(['message' => 'Aucun garant à remplacer.'], 422);

        $validated = $request->validate([
            'new_garant_ids' => 'required|array|size:'.$nbRefuses,
            'new_garant_ids.*' => 'required|exists:membres,id',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $nanoCredit->garants()->where('statut', 'refuse')->delete();
            foreach ($validated['new_garant_ids'] as $garantId) {
                $garantMembre = \App\Models\Membre::findOrFail($garantId);
                $garantRecord = \App\Models\NanoCreditGarant::create([
                    'nano_credit_id' => $nanoCredit->id,
                    'membre_id' => $garantId,
                    'statut' => 'en_attente',
                ]);
                $garantMembre->notify(new \App\Notifications\GarantSollicitationNotification($garantRecord));
            }
            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['message' => 'Nouveaux garants sollicités.']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['message' => 'Erreur : '.$e->getMessage()], 422);
        }
    }

    public function rechercherCagnottesParTags(Request $request): JsonResponse
    {
        $tags = $request->query('tags'); // comma separated
        $query = \App\Models\Cotisation::where('actif', true)->where('visibilite', 'publique');
        if ($tags) {
            $tagList = explode(',', $tags);
            $query->whereIn('tag', $tagList);
        }
        $cotisations = $query->orderBy('nom')->paginate(20);
        $list = $cotisations->getCollection()->map(fn ($c) => $this->formatCotisation($c, null));
        return response()->json(['data' => $list, 'meta' => $this->paginateMeta($cotisations)]);
    }

    public function epargneSouscriptionEcheances(Request $request, $id): JsonResponse
    {
        $membre = $request->user();
        $souscription = \App\Models\EpargneSouscription::where('membre_id', $membre->id)->findOrFail($id);
        $echeances = $souscription->echeances()->orderBy('date_echeance')->get()->map(fn($e) => [
            'id' => $e->id,
            'date' => $e->date_echeance->format('Y-m-d'),
            'montant' => (float) $e->montant,
            'statut' => $e->statut
        ]);
        return response()->json(['data' => $echeances]);
    }

    public function epargneDemanderRetrait(Request $request, $id): JsonResponse
    {
        $membre = $request->user();
        $souscription = \App\Models\EpargneSouscription::where('membre_id', $membre->id)->findOrFail($id);
        if ($souscription->statut !== 'matured') {
            return response()->json(['message' => 'L\'épargne n\'est pas encore échue.'], 422);
        }
        // Logique de demande de retrait (virement mobile money)
        // Ici on pourrait créer un enregistrement 'Remboursement' ou notifier l'admin
        return response()->json(['message' => 'Votre demande de retrait a été enregistrée.']);
    }

    public function profil(Request $request): JsonResponse
    {
        $membre = $request->user();

        // KYC
        $kyc = $membre->kycVerification;
        $kycData = $kyc ? [
            'statut' => $kyc->statut,
            'motif_rejet' => $kyc->motif_rejet,
            'updated_at' => $kyc->updated_at?->toIso8601String(),
        ] : null;

        // PIN
        $pinStatus = [
            'has_pin' => $membre->hasPin(),
            'pin_enabled' => (bool) $membre->pin_enabled,
            'pin_mode' => $membre->pin_mode ?? 'always',
        ];

        // Parrainage
        $codeParrainage = $membre->getOrCreateCodeParrainage();
        $filleulsCount = $membre->filleuls()->count();
        $commissionsEnAttente = $membre->commissionsParrainage()
            ->where('statut', 'en_attente')
            ->sum('montant');
        $commissionsTotal = $membre->commissionsParrainage()
            ->where('statut', 'payee')
            ->sum('montant');

        return response()->json([
            'membre' => $this->membreResource($membre),
            'kyc' => $kycData,
            'pin_status' => $pinStatus,
            'parrainage' => [
                'code' => $codeParrainage,
                'filleuls_count' => $filleulsCount,
                'commissions_en_attente' => (float) $commissionsEnAttente,
                'commissions_total_percu' => (float) $commissionsTotal,
            ],
        ]);
    }

    public function segments(Request $request): JsonResponse
    {
        $segments = Segment::where('actif', true)
            ->orderByRaw('is_default DESC')
            ->orderBy('nom', 'asc')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'nom' => $s->nom,
                'icone' => $s->icone,
                'couleur' => $s->couleur,
                'description' => $s->description,
            ]);

        return response()->json(['segments' => $segments]);
    }

    public function updateProfil(Request $request): JsonResponse
    {
        $membre = $request->user();
        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:membres,email,' . $membre->id,
            'adresse' => 'nullable|string',
            'segment_id' => 'sometimes|nullable|exists:segments,id',
            'old_password' => 'required_with:password|string',
            'password' => 'sometimes|nullable|string|min:6|confirmed',
        ]);

        // Check password change security
        if ($request->filled('password')) {
            if (!Hash::check($request->old_password, $membre->password)) {
                return response()->json([
                    'message' => 'L\'opération a échoué.',
                    'errors' => ['old_password' => ['L\'ancien mot de passe est incorrect.']]
                ], 422);
            }
            $validated['password'] = Hash::make($request->password);
            unset($validated['old_password']);
            unset($validated['password_confirmation']);
        }

        $membre->update($validated);

        return response()->json([
            'message' => 'Profil mis à jour avec succès.',
            'membre' => $this->membreResource($membre->fresh())
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $list = $request->user()->notifications()->orderBy('created_at', 'desc')->limit(50)->get()->map(fn ($n) => [
            'id' => $n->id,
            'type' => class_basename($n->type),
            'data' => $n->data,
            'read_at' => $n->read_at?->toIso8601String(),
            'created_at' => $n->created_at?->toIso8601String(),
        ]);
        return response()->json(['data' => $list]);
    }

    public function markNotificationRead(Request $request, $id): JsonResponse
    {
        $n = $request->user()->notifications()->findOrFail($id);
        $n->markAsRead();
        return response()->json(['message' => 'ok']);
    }

    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->each->markAsRead();
        return response()->json(['message' => 'ok']);
    }

    public function annonces(Request $request): JsonResponse
    {
        $list = Annonce::active()->orderBy('ordre')->orderBy('created_at')->get()->map(fn ($a) => [
            'id' => $a->id,
            'titre' => $a->titre,
            'contenu' => $a->contenu,
            'type' => $a->type,
        ]);
        return response()->json(['data' => $list]);
    }

    /**
     * Épargne : liste des plans + IDs des plans déjà souscrits (en cours)
     */
    public function epargnePlans(Request $request): JsonResponse
    {
        $membre = $request->user();
        $plans = EpargnePlan::where('actif', true)->with('caisse')->orderBy('ordre')->orderBy('nom')->get();
        $planIdsDejaSouscrits = EpargneSouscription::where('membre_id', $membre->id)
            ->where('statut', 'active')
            ->where(function ($q) {
                $q->whereNull('date_fin')->orWhere('date_fin', '>=', now()->toDateString());
            })
            ->pluck('plan_id')
            ->unique()
            ->values()
            ->all();

        $list = $plans->map(fn ($p) => [
            'id' => $p->id,
            'nom' => $p->nom,
            'description' => $p->description,
            'montant_min' => (float) $p->montant_min,
            'montant_max' => $p->montant_max ? (float) $p->montant_max : null,
            'frequence' => $p->frequence,
            'frequence_label' => $p->frequence_label,
            'taux_remuneration' => (float) ($p->taux_remuneration ?? 0),
            'duree_mois' => (int) ($p->duree_mois ?? 12),
            'nombre_versements' => $p->nombre_versements,
        ]);
        return response()->json(['data' => $list, 'plan_ids_deja_souscrits' => $planIdsDejaSouscrits]);
    }

    /**
     * Épargne : détail d'un plan (pour formulaire souscription) + exemple de calcul
     */
    public function epargnePlanDetail(Request $request, $planId): JsonResponse
    {
        $plan = EpargnePlan::where('actif', true)->findOrFail($planId);
        $montantExemple = (float) $plan->montant_min;
        $exempleCalcul = $plan->calculRemboursement($montantExemple);
        $dateDebutExemple = now()->format('Y-m-d');
        $dateFinExemple = Carbon::parse($dateDebutExemple)->addMonths((int) ($plan->duree_mois ?? 12))->format('Y-m-d');

        return response()->json([
            'plan' => [
                'id' => $plan->id,
                'nom' => $plan->nom,
                'description' => $plan->description,
                'montant_min' => (float) $plan->montant_min,
                'montant_max' => $plan->montant_max ? (float) $plan->montant_max : null,
                'frequence' => $plan->frequence,
                'frequence_label' => $plan->frequence_label,
                'taux_remuneration' => (float) ($plan->taux_remuneration ?? 0),
                'duree_mois' => (int) ($plan->duree_mois ?? 12),
                'nombre_versements' => $plan->nombre_versements,
            ],
            'exemple_calcul' => $exempleCalcul,
            'date_fin_exemple' => $dateFinExemple,
        ]);
    }

    /**
     * Épargne : enregistrer une souscription
     */
    public function epargneStoreSouscription(Request $request, $planId): JsonResponse
    {
        $plan   = EpargnePlan::where('actif', true)->findOrFail($planId);
        $membre = $request->user();

        $souscriptionEnCours = EpargneSouscription::where('membre_id', $membre->id)
            ->where('plan_id', $plan->id)
            ->where('statut', 'active')
            ->where(function ($q) {
                $q->whereNull('date_fin')->orWhere('date_fin', '>=', now()->toDateString());
            })
            ->exists();
        if ($souscriptionEnCours) {
            return response()->json(['message' => 'Vous avez d\u00e9j\u00e0 une souscription en cours \u00e0 ce forfait.'], 422);
        }

        if (! $membre->isPinEnabled()) {
            return response()->json([
                'message' => 'L\'accès aux tontines est bloqué tant que vous n\'avez pas activé votre code PIN. Rendez-vous dans Profil > Sécurité pour l\'activer.',
                'require_pin_activation' => true,
            ], 403);
        }

        // Vérification du PIN (opération critique : souscription tontine)
        $pinError = app(PinService::class)->requirePin($request, $membre);
        if ($pinError) return $pinError;

        $montantMin = (float) $plan->montant_min;
        $montantMax = $plan->montant_max ? (float) $plan->montant_max : null;
        $rules = [
            'montant'    => 'required|numeric|min:'.$montantMin,
            'date_debut' => 'required|date|after_or_equal:today',
        ];
        if ($montantMax) {
            $rules['montant'] .= '|max:'.$montantMax;
        }
        if ($plan->frequence === 'mensuel') {
            // Optionnel : si non fourni, on prend le jour du date_debut
            $rules['jour_du_mois'] = 'nullable|integer|min:1|max:28';
        }
        $validated = $request->validate($rules);

        $dateDebut = Carbon::parse($validated['date_debut']);
        $dureeMois = (int) ($plan->duree_mois ?? 12);
        $dateFin   = $dateDebut->copy()->addMonths($dureeMois);

        $souscription = EpargneSouscription::create([
            'membre_id'    => $membre->id,
            'plan_id'      => $plan->id,
            'montant'      => $validated['montant'],
            'date_debut'   => $validated['date_debut'],
            'date_fin'     => $dateFin->format('Y-m-d'),
            'jour_du_mois' => $plan->frequence === 'mensuel' ? (int) ($validated['jour_du_mois'] ?? Carbon::parse($validated['date_debut'])->day) : null,
            'statut'       => 'active',
            'solde_courant' => 0,
        ]);
        $this->epargneGenererEcheances($souscription);
        $souscription->load('plan');
        $calc = $plan->calculRemboursement((float) $souscription->montant);

        return response()->json([
            'message'              => 'Souscription enregistr\u00e9e.',
            'souscription'         => $this->formatEpargneSouscription($souscription),
            'montant_total_reverse' => $calc['montant_total_reverse'],
        ]);
    }

    private function epargneGenererEcheances(EpargneSouscription $souscription): void
    {
        $plan = $souscription->plan;
        $dateDebut = Carbon::parse($souscription->date_debut);
        $montant = (float) $souscription->montant;
        $nbVersements = $plan->nombre_versements;
        $echeances = [];

        switch ($plan->frequence) {
            case 'hebdomadaire':
                for ($i = 0; $i < $nbVersements; $i++) {
                    $echeances[] = ['date_echeance' => $dateDebut->copy()->addWeeks($i)->format('Y-m-d'), 'montant' => $montant];
                }
                break;
            case 'mensuel':
                $jour = (int) ($souscription->jour_du_mois ?? $dateDebut->day);
                $premierMois = $dateDebut->copy()->day(min($jour, 28));
                if ($premierMois->lt($dateDebut)) {
                    $premierMois->addMonth();
                }
                for ($i = 0; $i < $nbVersements; $i++) {
                    $date = $premierMois->copy()->addMonths($i);
                    $echeances[] = ['date_echeance' => $date->format('Y-m-d'), 'montant' => $montant];
                }
                break;
            case 'trimestriel':
                for ($i = 0; $i < $nbVersements; $i++) {
                    $date = $dateDebut->copy()->addMonths(3 * $i);
                    $echeances[] = ['date_echeance' => $date->format('Y-m-d'), 'montant' => $montant];
                }
                break;
            default:
                for ($i = 0; $i < $nbVersements; $i++) {
                    $echeances[] = ['date_echeance' => $dateDebut->copy()->addMonths($i)->format('Y-m-d'), 'montant' => $montant];
                }
        }
        foreach ($echeances as $e) {
            EpargneEcheance::create([
                'souscription_id' => $souscription->id,
                'date_echeance' => $e['date_echeance'],
                'montant' => $e['montant'],
                'statut' => Carbon::parse($e['date_echeance'])->isPast() ? 'en_retard' : 'a_venir',
            ]);
        }
    }

    /**
     * Épargne : mes souscriptions
     */
    public function epargneMesEpargnes(Request $request): JsonResponse
    {
        $membre = $request->user();
        $souscriptions = $membre->epargneSouscriptions()
            ->with(['plan', 'echeances' => fn ($q) => $q->whereIn('statut', ['a_venir', 'en_retard'])->orderBy('date_echeance')->limit(1)])
            ->orderBy('created_at', 'desc')
            ->get();
        $list = $souscriptions->map(fn ($s) => $this->formatEpargneSouscription($s));
        return response()->json(['data' => $list]);
    }

    /**
     * Épargne : détail d'une souscription
     */
    public function epargneSouscriptionDetail(Request $request, $souscriptionId): JsonResponse
    {
        $membre = $request->user();
        $souscription = EpargneSouscription::where('membre_id', $membre->id)->with(['plan', 'echeances' => fn ($q) => $q->orderBy('date_echeance')])->findOrFail($souscriptionId);
        return response()->json(['souscription' => $this->formatEpargneSouscriptionDetail($souscription)]);
    }

    /**
     * Épargne : initier un paiement PayDunya pour une échéance (retourne l'URL de la facture pour l'app mobile)
     */
    public function epargneInitierPaiementEcheance(Request $request, $echeanceId): JsonResponse
    {
        $membre = $request->user();
        $echeance = EpargneEcheance::with('souscription.plan')->findOrFail($echeanceId);
        $souscription = $echeance->souscription;
        if ($souscription->membre_id !== $membre->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }
        if (! in_array($echeance->statut, ['a_venir', 'en_retard'])) {
            return response()->json(['message' => 'Cette échéance est déjà réglée.'], 422);
        }

        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        if (! $paydunyaConfig || ! $paydunyaConfig->enabled) {
            return response()->json(['message' => 'Le paiement en ligne n\'est pas disponible.'], 503);
        }

        try {
            $paydunyaService = new \App\Services\PayDunyaService();
            $plan = $souscription->plan;
            $callbackUrl = url('/membre/paydunya/callback');
            // Retour/cancel "public" pour la WebView mobile (pas besoin d'auth web)
            $returnUrl = url('/paydunya/mobile-return?type=epargne');
            $cancelUrl = url('/paydunya/mobile-cancel?type=epargne');

            $result = $paydunyaService->createInvoice([
                'type' => 'epargne',
                'membre_id' => $membre->id,
                'souscription_id' => $souscription->id,
                'echeance_id' => $echeance->id,
                'item_name' => 'Épargne - '.$plan->nom.' - Échéance '.$echeance->date_echeance->format('d/m/Y'),
                'amount' => (float) $echeance->montant,
                'description' => 'Versement épargne: '.$plan->nom,
                'callback_url' => $callbackUrl,
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ]);

            if ($result['success'] && ! empty($result['invoice_url'])) {
                return response()->json(['success' => true, 'invoice_url' => $result['invoice_url']]);
            }
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'Erreur lors de la création du paiement.'], 400);
        } catch (\Exception $e) {
            \Log::error('PayDunya épargne API: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    private function formatEpargneSouscription(EpargneSouscription $s): array
    {
        $plan = $s->relationLoaded('plan') ? $s->plan : $s->plan()->first();
        $prochaine = $s->relationLoaded('echeances') ? $s->echeances->first() : null;
        return [
            'id' => $s->id,
            'plan' => $plan ? ['id' => $plan->id, 'nom' => $plan->nom, 'frequence_label' => $plan->frequence_label] : null,
            'montant' => (float) $s->montant,
            'solde_courant' => (float) ($s->solde_courant ?? 0),
            'date_debut' => $s->date_debut?->format('Y-m-d'),
            'date_fin' => $s->date_fin?->format('Y-m-d'),
            'statut' => $s->statut,
            'montant_total_reverse' => $s->montant_total_reverse ?? null,
            'prochaine_echeance' => $prochaine ? ['date_echeance' => $prochaine->date_echeance?->format('Y-m-d'), 'statut' => $prochaine->statut, 'montant' => (float) $prochaine->montant] : null,
        ];
    }

    private function formatEpargneSouscriptionDetail(EpargneSouscription $s): array
    {
        $base = $this->formatEpargneSouscription($s);
        $s->load('echeances');
        $base['echeances'] = $s->echeances->map(fn ($e) => [
            'id' => $e->id,
            'date_echeance' => $e->date_echeance?->format('Y-m-d'),
            'montant' => (float) $e->montant,
            'statut' => $e->statut,
        ]);
        return $base;
    }

    // --- Helpers
    private function membreResource($membre): array
    {
        $membre->loadMissing('segment');
        return [
            'id' => $membre->id,
            'numero' => $membre->numero,
            'nom' => $membre->nom,
            'prenom' => $membre->prenom,
            'nom_complet' => $membre->nom_complet,
            'email' => $membre->email,
            'telephone' => $membre->telephone,
            'adresse' => $membre->adresse,
            'photo_url' => $membre->photo_url,
            'date_adhesion' => $membre->date_adhesion?->format('Y-m-d'),
            'statut' => $membre->statut,
            'segment_id' => $membre->segment_id,
            'segment' => $membre->segment ? [
                'id' => $membre->segment->id,
                'nom' => $membre->segment->nom,
                'icone' => $membre->segment->icone,
                'couleur' => $membre->segment->couleur,
            ] : null,
        ];
    }

    private function formatCotisation(Cotisation $c, $adhesion): array
    {
        return [
            'id' => $c->id,
            'nom' => $c->nom,
            'code' => $c->code,
            'tag' => $c->tag,
            'type' => $c->type,
            'frequence' => $c->frequence,
            'visibilite' => $c->visibilite ?? 'publique',
            'montant' => (float) ($c->montant ?? 0),
            'type_montant' => $c->type_montant,
            'description' => $c->description,
            'actif' => (bool) $c->actif,
            'adhesion' => $adhesion ? $this->formatAdhesion($adhesion) : null,
        ];
    }

    private function formatCotisationDetail(Cotisation $c): array
    {
        return array_merge($this->formatCotisation($c, null), [
            'adhesions' => $c->adhesions->map(fn ($a) => $this->formatAdhesion($a)),
            'chat_notice' => $c->chat_notice,
            'chat_notice_at' => $c->chat_notice_at?->toIso8601String(),
        ]);
    }

    private function formatAdhesion($a): array
    {
        return ['statut' => $a->statut];
    }

    private function formatPaiement($p): array
    {
        return [
            'id' => $p->id,
            'numero' => $p->numero,
            'montant' => (float) $p->montant,
            'date_paiement' => $p->date_paiement?->format('Y-m-d'),
            'mode_paiement' => $p->mode_paiement,
            'cotisation' => $p->relationLoaded('cotisation') && $p->cotisation ? ['id' => $p->cotisation->id, 'nom' => $p->cotisation->nom] : null,
        ];
    }

    private function formatEngagement($e): array
    {
        return [
            'id' => $e->id,
            'statut' => $e->statut,
            'periode_debut' => $e->periode_debut?->format('Y-m-d'),
            'periode_fin' => $e->periode_fin?->format('Y-m-d'),
            'montant' => (float) ($e->montant ?? 0),
            'cotisation' => $e->relationLoaded('cotisation') && $e->cotisation ? ['id' => $e->cotisation->id, 'nom' => $e->cotisation->nom] : null,
        ];
    }

    private function paginateMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
