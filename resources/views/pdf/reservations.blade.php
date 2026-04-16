<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Réservations</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0056b3; padding-bottom: 10px; }
        .header h1 { color: #0056b3; margin: 0; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 5px 0; font-size: 14px; color: #555; }
        .info-date { text-align: right; font-style: italic; margin-bottom: 20px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .badge { padding: 4px; border-radius: 4px; color: #fff; font-size: 9px; font-weight: bold; }
        .bg-success { background-color: #28a745; }
        .bg-warning { background-color: #ffc107; color: #000; }
        .bg-danger { background-color: #dc3545; }
        .bg-secondary { background-color: #6c757d; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $agenceName ?? 'Agence' }}</h1>
        <p>Gare : {{ $gareName ?? 'Non spécifiée' }}</p>
        <p>Point sur les Réservations</p>
    </div>
    <div class="info-date">Édité le {{ date('d/m/Y à H:i') }}</div>
    <table>
        <thead>
            <tr>
                <th>Réf</th>
                <th>Date Réservation</th>
                <th>Client / Passager</th>
                <th>Contact</th>
                <th>Voyage (Code)</th>
                <th>Siège</th>
                <th>Montant</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reservations as $res)
            <tr>
                <td><strong>{{ $res->num_reservation ?? ('#'.$res->id) }}</strong></td>
                <td>{{ \Carbon\Carbon::parse($res->created_at)->format('d/m/Y H:i') }}</td>
                <td>
                    @if($res->user)
                        {{ $res->user->nom }} {{ $res->user->prenom }}
                    @else
                        {{ $res->client_name ?? 'N/A' }}
                    @endif
                </td>
                <td>{{ $res->user->telephone ?? 'Non fourni' }}</td>
                <td>{{ $res->voyage->num_voyage ?? 'N/A' }}</td>
                <td style="text-align: center;">{{ $res->place ?? '-' }}</td>
                <td>{{ number_format($res->prix, 0, ',', ' ') }} FCFA</td>
                <td>
                    @php
                        $color = match(strtolower($res->statut)) {
                            'validee', 'validée' => 'bg-success',
                            'en attente' => 'bg-warning',
                            'annule', 'annulée' => 'bg-danger',
                            default => 'bg-secondary'
                        };
                    @endphp
                    <span class="badge {{ $color }}">{{ ucfirst($res->statut) }}</span>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align: center;">Aucune réservation trouvée.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
