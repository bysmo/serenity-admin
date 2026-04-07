<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Récapitulatif des paiements - {{ $periodeDebut->format('F Y') }}</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; font-size: 9pt; color: #333; padding: 15px; margin: 0;">
    @php
        $logoPath = \App\Models\AppSetting::get('entreprise_logo');
        $logoFullPath = $logoPath ? storage_path('app/public/' . $logoPath) : null;
        $logoExists = $logoFullPath && \Illuminate\Support\Facades\File::exists($logoFullPath);
        $logoBase64 = null;
        if ($logoExists) {
            $logoContent = \Illuminate\Support\Facades\File::get($logoFullPath);
            $logoMime = mime_content_type($logoFullPath);
            $logoBase64 = 'data:' . $logoMime . ';base64,' . base64_encode($logoContent);
        }
    @endphp
    
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 15px;">&nbsp;</td>
            <td style="width: 150px; vertical-align: middle;">
                @if($logoExists && $logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo" style="max-width: 80px; max-height: 50px;">
                @endif
            </td>
            <td style="width: auto; text-align: right; vertical-align: middle;">
                <div style="font-size: 11pt; color: #1e3a5f; margin-bottom: 5px; font-weight: normal;">{{ \App\Models\AppSetting::get('entreprise_nom', 'Serenity') }}</div>
                <div style="font-size: 7pt; color: #666;">
                    @if(\App\Models\AppSetting::get('entreprise_adresse'))
                        {{ \App\Models\AppSetting::get('entreprise_adresse') }}<br>
                    @endif
                    @if(\App\Models\AppSetting::get('entreprise_contact'))
                        Tél: {{ \App\Models\AppSetting::get('entreprise_contact') }}<br>
                    @endif
                    @if(\App\Models\AppSetting::get('entreprise_email'))
                        Email: {{ \App\Models\AppSetting::get('entreprise_email') }}
                    @endif
                </div>
            </td>
            <td style="width: 15px;">&nbsp;</td>
        </tr>
    </table>
    
    <div style="background-color: #f8f9fa; padding: 12px; margin-bottom: 20px;">
        <div style="font-size: 9pt; color: #1e3a5f; margin-bottom: 8px; font-weight: bold; font-family: Arial, Helvetica, sans-serif;">Informations Membre</div>
        <table style="width: 100%; border-collapse: collapse;" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding: 4px 10px 4px 0; width: 30%; font-size: 7pt; color: #666; font-family: Arial, Helvetica, sans-serif; text-align: left;">Numéro Membre :</td>
                <td style="padding: 4px 10px 4px 0; width: 20%; font-size: 7pt; color: #333; font-family: Arial, Helvetica, sans-serif; text-align: left;">{{ $membre->numero ?? '-' }}</td>
                <td style="padding: 4px 10px 4px 0; width: 20%; font-size: 7pt; color: #666; font-family: Arial, Helvetica, sans-serif; text-align: left;">Email :</td>
                <td style="padding: 4px 0; width: 30%; font-size: 7pt; color: #333; font-family: Arial, Helvetica, sans-serif; text-align: left;">{{ $membre->email ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding: 4px 10px 4px 0; width: 30%; font-size: 7pt; color: #666; font-family: Arial, Helvetica, sans-serif; text-align: left;">Nom et Prénom :</td>
                <td style="padding: 4px 10px 4px 0; width: 20%; font-size: 7pt; color: #333; font-family: Arial, Helvetica, sans-serif; text-align: left;">{{ $membre->nom }} {{ $membre->prenom }}</td>
                <td style="padding: 4px 10px 4px 0; width: 20%; font-size: 7pt; color: #666; font-family: Arial, Helvetica, sans-serif; text-align: left;">Contact :</td>
                <td style="padding: 4px 0; width: 30%; font-size: 7pt; color: #333; font-family: Arial, Helvetica, sans-serif; text-align: left;">{{ $membre->telephone ?? '-' }}</td>
            </tr>
        </table>
    </div>
    
    @php
        $annoncesActives = \App\Models\Annonce::active()->orderBy('ordre', 'asc')->get();
    @endphp
    @if($annoncesActives->count() > 0)
        <div style="background-color: #f8f9fa; padding: 12px; margin-bottom: 20px;">
            <div style="font-size: 9pt; color: #1e3a5f; margin-bottom: 8px; font-weight: bold; font-family: Arial, Helvetica, sans-serif;">Annonces</div>
            @foreach($annoncesActives as $annonce)
                <div style="padding: 8px; margin-bottom: 8px; border-left: 4px solid {{ $annonce->type === 'info' ? '#0dcaf0' : ($annonce->type === 'warning' ? '#ffc107' : ($annonce->type === 'success' ? '#198754' : '#dc3545')) }}; background-color: {{ $annonce->type === 'info' ? '#e7f3ff' : ($annonce->type === 'warning' ? '#fff3cd' : ($annonce->type === 'success' ? '#d1e7dd' : '#f8d7da')) }};">
                    <div style="font-size: 8pt; font-weight: bold; color: #1e3a5f; margin-bottom: 4px; font-family: Arial, Helvetica, sans-serif;">{{ $annonce->titre }}</div>
                    <div style="font-size: 7pt; color: #333; font-family: Arial, Helvetica, sans-serif;">{!! nl2br(e($annonce->contenu)) !!}</div>
                </div>
            @endforeach
        </div>
    @endif
    
    <div style="font-size: 9pt; color: #1e3a5f; margin-bottom: 8px; font-weight: bold; font-family: Arial, Helvetica, sans-serif;">Période</div>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;" cellpadding="0" cellspacing="0">
        <tr style="background-color: #f8f9fa;">
            <td style="padding: 6px 8px; width: 40%; color: #666; font-size: 8pt; font-family: Arial, Helvetica, sans-serif;">Période :</td>
            <td style="padding: 6px 8px; color: #333; font-size: 8pt; font-family: Arial, Helvetica, sans-serif;">{{ $periodeDebut->format('d/m/Y') }} au {{ $periodeFin->format('d/m/Y') }}</td>
        </tr>
    </table>
    
    <div style="font-size: 9pt; color: #1e3a5f; margin-bottom: 8px; font-weight: bold; font-family: Arial, Helvetica, sans-serif;">Résumé</div>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;" cellpadding="0" cellspacing="0">
        <tr style="background-color: #f8f9fa;">
            <td style="padding: 6px 8px; color: #666; font-size: 7pt; font-family: Arial, Helvetica, sans-serif;">Nombre total de paiements : <strong style="color: #333;">{{ $nombrePaiements }}</strong> | Montant total payé : <strong style="color: #333;">{{ number_format($montantTotal, 0, ',', ' ') }} XOF</strong></td>
        </tr>
    </table>
    
    @if($paiements->count() > 0)
        <div style="font-size: 9pt; color: #1e3a5f; margin-bottom: 8px; font-weight: bold; font-family: Arial, Helvetica, sans-serif;">Détail des Paiements</div>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;" cellpadding="0" cellspacing="0">
            <tr style="background-color: #1e3a5f; color: white;">
                <td style="padding: 6px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; font-weight: bold; width: 15%;">Date</td>
                <td style="padding: 6px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; font-weight: bold; width: 20%;">Numéro</td>
                <td style="padding: 6px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; font-weight: bold; width: 30%;">Cotisation</td>
                <td style="padding: 6px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; font-weight: bold; width: 15%;">Mode de paiement</td>
                <td style="padding: 6px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; font-weight: bold; width: 20%; text-align: right;">Montant</td>
            </tr>
            @foreach($paiements as $index => $paiement)
                <tr style="{{ $index % 2 == 0 ? 'background-color: #f8f9fa;' : '' }}">
                    <td style="padding: 5px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; color: #333;">{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                    <td style="padding: 5px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; color: #333;">{{ $paiement->numero }}</td>
                    <td style="padding: 5px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; color: #333;">{{ $paiement->cotisation->nom ?? 'N/A' }}</td>
                    <td style="padding: 5px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; color: #333;">{{ ucfirst(str_replace('_', ' ', $paiement->mode_paiement ?? '')) }}</td>
                    <td style="padding: 5px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; color: #333; text-align: right;">{{ number_format($paiement->montant, 0, ',', ' ') }} XOF</td>
                </tr>
            @endforeach
            <tr style="background-color: #e7f3ff; border-top: 2px solid #1e3a5f;">
                <td colspan="4" style="padding: 6px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; font-weight: bold; text-align: right; color: #1e3a5f;">TOTAL :</td>
                <td style="padding: 6px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; font-weight: bold; text-align: right; color: #1e3a5f;">{{ number_format($montantTotal, 0, ',', ' ') }} XOF</td>
            </tr>
        </table>
    @endif
    
    @if($engagements->count() > 0)
        <div style="font-size: 9pt; color: #1e3a5f; margin-bottom: 8px; font-weight: bold; font-family: Arial, Helvetica, sans-serif; margin-top: 20px;">Engagements en cours</div>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;" cellpadding="0" cellspacing="0">
            <tr style="background-color: #1e3a5f; color: white;">
                <td style="padding: 6px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; font-weight: bold; width: 30%;">Cotisation</td>
                <td style="padding: 6px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; font-weight: bold; width: 20%; text-align: right;">Montant engagé</td>
                <td style="padding: 6px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; font-weight: bold; width: 20%; text-align: right;">Montant payé</td>
                <td style="padding: 6px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; font-weight: bold; width: 20%; text-align: right;">Reste à payer</td>
                <td style="padding: 6px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; font-weight: bold; width: 10%;">Périodicité</td>
            </tr>
            @foreach($engagements as $index => $engagement)
                @php
                    $montantPaye = $engagement->montant_paye ?? 0;
                    $resteAPayer = $engagement->montant_engage - $montantPaye;
                @endphp
                <tr style="{{ $index % 2 == 0 ? 'background-color: #f8f9fa;' : '' }}">
                    <td style="padding: 5px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; color: #333;">{{ $engagement->cotisation->nom ?? 'N/A' }}</td>
                    <td style="padding: 5px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; color: #333; text-align: right;">{{ number_format($engagement->montant_engage, 0, ',', ' ') }} XOF</td>
                    <td style="padding: 5px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; color: #333; text-align: right;">{{ number_format($montantPaye, 0, ',', ' ') }} XOF</td>
                    <td style="padding: 5px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; color: #333; text-align: right;">{{ number_format($resteAPayer, 0, ',', ' ') }} XOF</td>
                    <td style="padding: 5px 8px; font-size: 7pt; font-family: Arial, Helvetica, sans-serif; color: #333;">{{ ucfirst($engagement->periodicite ?? '') }}</td>
                </tr>
            @endforeach
        </table>
    @endif
    
    <div style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 7pt; color: #666; text-align: center;">
        <p>Document généré le {{ now()->format('d/m/Y à H:i') }}</p>
        <p>{{ \App\Models\AppSetting::get('entreprise_nom', 'Serenity') }} - Tous droits réservés</p>
    </div>
</body>
</html>
