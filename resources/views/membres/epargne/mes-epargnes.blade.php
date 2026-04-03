@extends('layouts.membre')

@section('title', 'Mes tontines')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <h1 style="font-weight: 300; font-family: 'Ubuntu', sans-serif;"><i class="bi bi-wallet2"></i> Mes tontines</h1>
    <a href="{{ route('membre.epargne.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-piggy-bank"></i> Voir les plans</a>
</div>

<div class="card">
    <div class="card-header" style="font-weight: 300;"><i class="bi bi-list-ul"></i> Mes souscriptions</div>
    <div class="card-body">
        @if($souscriptions->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Fréquence</th>
                            <th class="text-end">Montant / versement</th>
                            <th class="text-end">Solde actuel</th>
                            <th>Date de fin</th>
                            <th>Montant reversé à l'échéance</th>
                            <th>Prochaine échéance</th>
                            <th>Statut</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($souscriptions as $s)
                            @php $prochaine = $s->echeances->first(); @endphp
                            <tr>
                                <td>{{ $s->plan->nom }}</td>
                                <td>{{ $s->plan->frequence_label }}</td>
                                <td class="text-end">{{ number_format($s->montant, 0, ',', ' ') }} XOF</td>
                                <td class="text-end">{{ number_format($s->solde_courant, 0, ',', ' ') }} XOF</td>
                                <td>{{ $s->date_fin ? $s->date_fin->format('d/m/Y') : '—' }}</td>
                                <td>{{ $s->date_fin ? number_format($s->montant_total_reverse, 0, ',', ' ') . ' XOF' : '—' }}</td>
                                <td>
                                    @if($prochaine)
                                        {{ $prochaine->date_echeance->format('d/m/Y') }}
                                        <span class="badge bg-{{ $prochaine->statut === 'en_retard' ? 'danger' : 'secondary' }} ms-1">{{ $prochaine->statut === 'en_retard' ? 'En retard' : 'À venir' }}</span>
                                    @else — @endif
                                </td>
                                <td>
                                    @if($s->statut === 'active')<span class="badge bg-success">Active</span>
                                    @elseif($s->statut === 'suspendue')<span class="badge bg-warning">Suspendue</span>
                                    @else<span class="badge bg-secondary">Clôturée</span>@endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('membre.epargne.souscription.show', $s) }}" class="btn btn-sm btn-outline-primary" title="Détail"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                <i class="bi bi-wallet2 text-muted" style="font-size: 2.5rem;"></i>
                <p class="text-muted mt-2 mb-2">Vous n'avez aucune souscription tontine.</p>
                <a href="{{ route('membre.epargne.index') }}" class="btn btn-primary btn-sm"><i class="bi bi-piggy-bank"></i> Voir les plans</a>
            </div>
        @endif
    </div>
</div>
@endsection
