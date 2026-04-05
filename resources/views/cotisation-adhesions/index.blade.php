@extends('layouts.app')

@section('title', 'Demandes d\'adhésion')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-people"></i> Demandes d'adhésion (Serenity mobile)</h1>
</div>

<p class="text-muted small mb-3">Demandes en attente pour les cagnottes créées par l'administration. Les cagnottes créées par les membres sont gérées par leurs créateurs.</p>

<div class="card">
    <div class="card-header"><i class="bi bi-list-ul"></i> Demandes en attente</div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($adhesions->count() > 0)
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Membre</th>
                            <th>Cagnotte</th>
                            <th>Date demande</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($adhesions as $adhesion)
                            <tr>
                                <td>{{ $adhesion->membre->nom_complet }}<br><small class="text-muted">{{ $adhesion->membre->email }}</small></td>
                                <td>{{ $adhesion->cotisation->nom }}</td>
                                <td>{{ $adhesion->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <form action="{{ route('cotisation-adhesions.accepter', $adhesion) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="Accepter"><i class="bi bi-check"></i></button>
                                    </form>
                                    <form action="{{ route('cotisation-adhesions.refuser', $adhesion) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger" title="Refuser" onclick="return confirm('Refuser cette demande ?')"><i class="bi bi-x"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $adhesions->links() }}
        @else
            <div class="text-center py-4">
                <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                <p class="text-muted mt-2 mb-0">Aucune demande en attente.</p>
            </div>
        @endif
    </div>
</div>
@endsection
