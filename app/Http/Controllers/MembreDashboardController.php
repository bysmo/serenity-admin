<?php

namespace App\Http\Controllers;

use App\Models\Membre;
use App\Models\Segment;
use App\Models\KycVerification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MembreDashboardController extends Controller
{
    public function index(Request $request)
    {
        // Période par défaut : le mois en cours
        $dateDebut = $request->filled('date_debut') 
            ? Carbon::parse($request->date_debut)->startOfDay() 
            : Carbon::now()->startOfMonth();
            
        $dateFin = $request->filled('date_fin') 
            ? Carbon::parse($request->date_fin)->endOfDay() 
            : Carbon::now()->endOfDay();

        // 1. Statistiques générales
        $totalMembres = Membre::count();
        $membresActifs = Membre::where('statut', 'actif')->count();
        
        // Membres inscrits sur la période
        $nouveauxMembres = Membre::whereBetween('created_at', [$dateDebut, $dateFin])->count();

        // 2. Statistiques KYC
        $kycStats = [
            'valide' => KycVerification::where('statut', 'valide')->count(),
            'en_attente' => KycVerification::where('statut', 'en_attente')->count(),
            'rejete' => KycVerification::where('statut', 'rejete')->count(),
        ];
        
        // Membres sans aucun enregistrement KYC
        $membresAvecKycIds = KycVerification::pluck('membre_id')->unique();
        $kycStats['manquant'] = Membre::whereNotIn('id', $membresAvecKycIds)->count();

        // 3. Répartition par segment
        $segments = Segment::withCount('membres')->get();
        
        // 4. Évolution des adhésions (par jour ou par mois selon la plage)
        $diffInDays = $dateDebut->diffInDays($dateFin);
        
        if ($diffInDays <= 62) {
            // Par jour
            $evolution = Membre::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as aggregate'))
                ->whereBetween('created_at', [$dateDebut, $dateFin])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->mapWithKeys(fn($item) => [$item->date => $item->aggregate]);
        } else {
            // Par mois
            $evolution = Membre::select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as date"), DB::raw('count(*) as aggregate'))
                ->whereBetween('created_at', [$dateDebut, $dateFin])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->mapWithKeys(fn($item) => [$item->date => $item->aggregate]);
        }

        // 5. Répartition par sexe
        $sexeStats = Membre::select('sexe', DB::raw('count(*) as count'))
            ->groupBy('sexe')
            ->get()
            ->mapWithKeys(fn($item) => [($item->sexe ?: 'Non défini') => $item->count]);

        return view('membres.dashboard', compact(
            'totalMembres', 'membresActifs', 'nouveauxMembres',
            'kycStats', 'segments', 'evolution', 'sexeStats',
            'dateDebut', 'dateFin'
        ));
    }
}
