@extends('layouts.app')

@section('title', 'Retraits Gains Garants')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h1 class="h3 mb-0 text-gray-800">Retraits Gains Garants</h1>
        <p class="mb-0 text-muted">Gestion des demandes de retrait des bénéfices partagés par les garants.</p>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Demandes de Retrait</h6>
        <div class="dropdown no-arrow">
            <form action="{{ route('nano-credits.garants.retraits.index') }}" method="GET" class="form-inline">
                <select name="statut" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="">Tous les statuts</option>
                    <option value="en_attente" {{ request('statut') == 'en_attente' ? 'selected' : '' }}>En attente</option>
                    <option value="approuve" {{ request('statut') == 'approuve' ? 'selected' : '' }}>Approuvés</option>
                    <option value="refuse" {{ request('statut') == 'refuse' ? 'selected' : '' }}>Refusés</option>
                </select>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Rechercher...">
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>Référence</th>
                        <th>Membre</th>
                        <th>Montant</th>
                        <th>Solde Actuel</th>
                        <th>Statut</th>
                        <th>Traitement</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($retraits as $retrait)
                        <tr>
                            <td><code>{{ $retrait->reference }}</code></td>
                            <td>
                                <strong>{{ $retrait->membre->nom_complet }}</strong>
                                <br><small class="text-muted">{{ $retrait->membre->telephone }}</small>
                            </td>
                            <td class="text-right font-weight-bold">{{ number_format($retrait->montant, 0, ',', ' ') }} XOF</td>
                            <td class="text-right text-primary">{{ number_format($retrait->membre->garant_solde, 0, ',', ' ') }} XOF</td>
                            <td class="text-center">
                                @if($retrait->statut === 'en_attente')
                                    <span class="badge badge-warning">En attente</span>
                                @elseif($retrait->statut === 'approuve')
                                    <span class="badge badge-success">Approuvé</span>
                                @else
                                    <span class="badge badge-danger">Refusé</span>
                                @endif
                            </td>
                            <td>
                                @if($retrait->traite_par)
                                    <small>Par: {{ $retrait->traitePar->name }}</small>
                                    <br><small>Le: {{ $retrait->traite_le->format('d/m/Y H:i') }}</small>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">
                                @if($retrait->statut === 'en_attente')
                                    <button type="button" class="btn btn-success btn-circle btn-sm" data-toggle="modal" data-target="#approveModal{{ $retrait->id }}" title="Approuver">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-circle btn-sm" data-toggle="modal" data-target="#rejectModal{{ $retrait->id }}" title="Rejeter">
                                        <i class="fas fa-times"></i>
                                    </button>
                                @else
                                    <span class="text-muted small">Traité</span>
                                @endif
                            </td>
                        </tr>

                        <!-- Modal Approbation -->
                        <div class="modal fade" id="approveModal{{ $retrait->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <form action="{{ route('nano-credits.garants.retraits.approve', $retrait) }}" method="POST">
                                    @csrf
                                    <div class="modal-content">
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title">Approuver le retrait</h5>
                                            <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">×</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Voulez-vous approuver le retrait de <strong>{{ number_format($retrait->montant, 0, ',', ' ') }} XOF</strong> pour <strong>{{ $retrait->membre->nom_complet }}</strong> ?</p>
                                            <p class="small text-danger">Le solde du membre sera automatiquement débité.</p>
                                            <div class="form-group">
                                                <label>Commentaire / Note de paiement (Optionnel)</label>
                                                <textarea name="commentaire_admin" class="form-control" rows="2"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button class="btn btn-secondary btn-sm" type="button" data-dismiss="modal">Annuler</button>
                                            <button class="btn btn-success btn-sm" type="submit">Confirmer l'approbation</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Modal Rejet -->
                        <div class="modal fade" id="rejectModal{{ $retrait->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <form action="{{ route('nano-credits.garants.retraits.reject', $retrait) }}" method="POST">
                                    @csrf
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title">Rejeter le retrait</h5>
                                            <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">×</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Voulez-vous rejeter le retrait de <strong>{{ number_format($retrait->montant, 0, ',', ' ') }} XOF</strong> pour <strong>{{ $retrait->membre->nom_complet }}</strong> ?</p>
                                            <div class="form-group">
                                                <label>Motif du rejet (Obligatoire)</label>
                                                <textarea name="commentaire_admin" class="form-control" rows="3" required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button class="btn btn-secondary btn-sm" type="button" data-dismiss="modal">Annuler</button>
                                            <button class="btn btn-danger btn-sm" type="submit">Confirmer le rejet</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">Aucune demande trouvée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $retraits->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
