<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Voyages</title>
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
        .bg-primary { background-color: #007bff; }
        .bg-secondary { background-color: #6c757d; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $agenceName ?? 'Agence' }}</h1>
        <p>Gare : {{ $gareName ?? 'Non spécifiée' }}</p>
        <p>Planning des Voyages</p>
    </div>
    <div class="info-date">Édité le {{ date('d/m/Y à H:i') }}</div>
    <table>
        <thead>
            <tr>
                <th>N° Réf</th>
                <th>Trajet</th>
                <th>Départ Prévu</th>
                <th>Arrivée Prévue</th>
                <th>Véhicule</th>
                <th>Chauffeur</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @forelse($voyages as $voyage)
            <tr>
                <td><strong>{{ $voyage->num_voyage }}</strong></td>
                <td>
                    {{ $voyage->trajet->gareDepart->ville ?? '?' }} &rarr; {{ $voyage->trajet->gareArrivee->ville ?? '?' }}
                </td>
                <td>{{ \Carbon\Carbon::parse($voyage->date_depart)->format('d/m/Y H:i') }}</td>
                <td>{{ \Carbon\Carbon::parse($voyage->date_arrivee)->format('d/m/Y H:i') }}</td>
                <td>{{ $voyage->bus->immatriculation ?? 'Non assigné' }}</td>
                <td>{{ $voyage->chauffeur->nom ?? '' }} {{ $voyage->chauffeur->prenom ?? 'Non assigné' }}</td>
                <td>
                    @php
                        $color = match(strtolower($voyage->statut)) {
                            'planifie', 'planifié' => 'bg-primary',
                            'en cours' => 'bg-warning',
                            'termine', 'terminé' => 'bg-success',
                            default => 'bg-secondary'
                        };
                    @endphp
                    <span class="badge {{ $color }}">{{ ucfirst($voyage->statut) }}</span>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align: center;">Aucun voyage programmé.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
