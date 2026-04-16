<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste du Personnel</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #0056b3;
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }
        .info-date {
            text-align: right;
            font-style: italic;
            margin-bottom: 20px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
            color: #333;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            color: #fff;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
        }
        .badge.chauffeur { background-color: #28a745; }
        .badge.agent { background-color: #17a2b8; }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #999;
            margin-top: 50px;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>{{ $agenceName ?? 'Agence' }}</h1>
        <p>Gare : {{ $gareName ?? 'Non spécifiée' }}</p>
        <p>Liste Officielle du Personnel</p>
    </div>

    <div class="info-date">
        Édité le {{ date('d/m/Y à H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Nom & Prénom</th>
                <th>Téléphone</th>
                <th>Email</th>
                <th>CNI</th>
                <th>Rôle</th>
            </tr>
        </thead>
        <tbody>
            @forelse($personnel as $index => $user)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $user->nom }} {{ $user->prenom }}</td>
                <td>{{ $user->telephone ?? 'N/A' }}</td>
                <td>{{ $user->email ?? 'N/A' }}</td>
                <td>{{ $user->num_cni ?? 'N/A' }}</td>
                <td>
                    <span class="badge {{ strtolower($user->role_user) }}">
                        {{ $user->role_user }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center;">Aucun personnel enregistré pour cette gare.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Généré automatiquement par {{ config('app.name', 'GEV') }} &copy; {{ date('Y') }}
    </div>

</body>
</html>
