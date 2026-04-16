<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Trajets</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #0056b3; padding-bottom: 10px; }
        .header h1 { color: #0056b3; margin: 0; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 5px 0; font-size: 14px; color: #555; }
        .info-date { text-align: right; font-style: italic; margin-bottom: 20px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .price { font-weight: bold; color: #0056b3; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $agenceName ?? 'Agence' }}</h1>
        <p>Gare : {{ $gareName ?? 'Non spécifiée' }}</p>
        <p>Liste des Trajets Opérés</p>
    </div>
    <div class="info-date">Édité le {{ date('d/m/Y à H:i') }}</div>
    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Départ</th>
                <th>Arrivée</th>
                <th>Type</th>
                <th>Distance estimée (km)</th>
                <th>Prix (FCFA)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($trajets as $index => $trajet)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $trajet->gareDepart->ville ?? 'Inconnu' }}</td>
                <td>{{ $trajet->gareArrivee->ville ?? 'Inconnu' }}</td>
                <td>{{ ucfirst($trajet->type_trajet) }}</td>
                <td>{{ $trajet->distance_km ?? 'N/A' }}</td>
                <td class="price">{{ number_format($trajet->prix, 0, ',', ' ') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align: center;">Aucun trajet enregistré.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
