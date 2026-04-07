<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails de l'engagement - {{ $engagement->numero }}</title>
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
        $montantPaye = $engagement->montant_paye ?? 0;
        $resteAPayer = $engagement->montant_engage - $montantPaye;
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
                <td style="padding: 4px 10px 4px 0; width: 20%; font-size: 7pt; color: #333; font-family: Arial, Helvetica, sans-serif; text-align: left;">{{ $engagement->membre->numero ?? '-' }}</td>
                <td style="padding: 4px 10px 4px 0; width: 20%; font-size: 7pt; color: #666; font-family: Arial, Helvetica, sans-serif; text-align: left;">Email :</td>
                <td style="padding: 4px 0; width: 30%; font-size: 7pt; color: #333; font-family: Arial, Helvetica, sans-serif; text-align: left;">{{ $engagement->membre->email ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding: 4px 10px 4px 0; width: 30%; font-size: 7pt; color: #666; font-family: Arial, Helvetica, sans-serif; text-align: left;">Nom et Prénom :</td>
                <td style="padding: 4px 10px 4px 0; width: 20%; font-size: 7pt; color: #333; font-family: Arial, Helvetica, sans-serif; text-align: left;">{{ $engagement->membre->nom }} {{ $engagement->membre->prenom }}</td>
                <td style="padding: 4px 10px 4px 0; width: 20%; font-size: 7pt; color: #666; font-family: Arial, Helvetica, sans-serif; text-align: left;">Contact :</td>
                <td style="padding: 4px 0; width: 30%; font-size: 7pt; color: #333; font-family: Arial, Helvetica, sans-serif; text-align: left;">{{ $engagement->membre->telephone ?? '-' }}</td>
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
    
    <div style="font-size: 9pt; color: #1e3a5f; margin-bottom: 8px; font-weight: bold; font-family: Arial, Helvetica, sans-serif;">Informations Engagement</div>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;" cellpadding="0" cellspacing="0">
        <tr style="background-color: #f8f9fa;">
            <td style="padding: 6px 8px; width: 40%; color: #666; font-size: 8pt; font-family: Arial, Helvetica, sans-serif;">Numéro d'engagement :</td>
            <td style="padding: 6px 8px; color: #333; font-size: 8pt; font-family: Arial, Helvetica, sans-serif;">{{ $engagement->numero }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 8px; width: 40%; color: #666; font-size: 8pt; font-family: Arial, Helvetica, sans-serif;">Cotisation :</td>
            <td style="padding: 6px 8px; color: #333; font-size: 8pt; font-family: Arial, Helvetica, sans-serif;">{{ $engagement->cotisation->nom ?? 'N/A' }}</td>
        </tr>
        <tr style="background-color: #f8f9fa;">
            <td style="padding: 6px 8px; width: 40%; color: #666; font-size: 8pt; font-family: Arial, Helvetica, sans-serif;">Période :</td>
            <td style="padding: 6px 8px; color: #333; font-size: 8pt; font-family: Arial, Helvetica, sans-serif;">{{ $engagement->periode_debut->format('d/m/Y') }} au {{ $engagement->periode_fin->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 8px; width: 40%; color: #666; font-size: 8pt; font-family: Arial, Helvetica, sans-serif;">Périodicité :</td>
            <td style="padding: 6px 8px; color: #333; font-size: 8pt; font-family: Arial, Helvetica, sans-serif;">{{ ucfirst($engagement->periodicite ?? '') }}</td>
        </tr>
        <tr style="background-color: #f8f9fa;">
            <td style="padding: 6px 8px; width: 40%; color: #666; font-size: 8pt; font-family: Arial, Helvetica, sans-serif;">Statut :</td>
            <td style="padding: 6px 8px; color: #333; font-size: 8pt; font-family: Arial, Helvetica, sans-serif;">{{ ucfirst(str_replace('_', ' ', $engagement->statut ?? '')) }}</td>
        </tr>
        @if($engagement->notes)
            <tr>
                <td style="padding: 6px 8px; width: 40%; color: #666; font-size: 8pt; font-family: Arial, Helvetica, sans-serif;">Notes :</td>
                <td style="padding: 6px 8px; color: #333; font-size: 8pt; font-family: Arial, Helvetica, sans-serif;">{{ $engagement->notes }}</td>
            </tr>
        @endif
    </table>
    
    <div style="background-color: #e7f3ff; padding: 12px; border-left: 4px solid #1e3a5f; margin-top: 20px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 6px 0; border-bottom: 1px solid #b3d9ff; width: 60%; font-size: 8pt; color: #666; font-family: Arial, Helvetica, sans-serif;">Montant engagé :</td>
                <td style="padding: 6px 0; border-bottom: 1px solid #b3d9ff; text-align: right; font-size: 9pt; color: #1e3a5f; font-weight: bold; font-family: Arial, Helvetica, sans-serif;">{{ number_format($engagement->montant_engage, 0, ',', ' ') }} XOF</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; border-bottom: 1px solid #b3d9ff; width: 60%; font-size: 8pt; color: #666; font-family: Arial, Helvetica, sans-serif;">Montant payé :</td>
                <td style="padding: 6px 0; border-bottom: 1px solid #b3d9ff; text-align: right; font-size: 9pt; color: #1e3a5f; font-weight: bold; font-family: Arial, Helvetica, sans-serif;">{{ number_format($montantPaye, 0, ',', ' ') }} XOF</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; margin-top: 5px; border-top: 2px solid #1e3a5f; width: 60%; font-size: 8pt; color: #666; font-family: Arial, Helvetica, sans-serif;">Reste à payer :</td>
                <td style="padding: 10px 0; margin-top: 5px; border-top: 2px solid #1e3a5f; text-align: right; font-size: 12pt; color: #1e3a5f; font-weight: bold; font-family: Arial, Helvetica, sans-serif;">{{ number_format($resteAPayer, 0, ',', ' ') }} XOF</td>
            </tr>
        </table>
    </div>
    
    <div style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 7pt; color: #666; text-align: center;">
        <p>Document généré le {{ now()->format('d/m/Y à H:i') }}</p>
        <p>{{ \App\Models\AppSetting::get('entreprise_nom', 'Serenity') }} - Tous droits réservés</p>
    </div>
</body>
</html>
