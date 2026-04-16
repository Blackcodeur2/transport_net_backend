<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Bus</title>
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
        .badge { padding: 4px 8px; border-radius: 4px; color: #fff; font-size: 10px; font-weight: bold; }
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
        <p>Inventaire du Parc Automobile (Bus)</p>
    </div>
    <div class="info-date">Édité le {{ date('d/m/Y à H:i') }}</div>
    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Immatriculation</th>
                <th>Code Bus</th>
                <th>Modèle / Type</th>
                <th>Places</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @forelse($buses as $index => $bus)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $bus->immatriculation }}</td>
                <td>{{ $bus->code_bus }}</td>
                <td>{{ ucfirst($bus->modele) }} ({{ ucfirst($bus->type) }})</td>
                <td>{{ $bus->nb_places }}</td>
                <td>
                    @php
                        $color = match($bus->statut) {
                            'disponible' => 'bg-success',
                            'en voyage' => 'bg-warning',
                            'en maintenance' => 'bg-secondary',
                            'indisponible' => 'bg-danger',
                            default => 'bg-secondary'
                        };
                    @endphp
                    <span class="badge {{ $color }}">{{ ucfirst($bus->statut) }}</span>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align: center;">Aucun bus enregistré.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
