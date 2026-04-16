<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <style>
        @page {
            margin: 0;
            size: 85.6mm 54mm landscape;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            width: 85.6mm;
            height: 54mm;
            background: white;
            overflow: hidden;
            font-size: 7px;
        }

        .card {
            width: 85.6mm;
            height: 54mm;
            display: flex;
            flex-direction: column;
            background: white;
            border: 0.3mm solid #e2e8f0;
            border-radius: 1.5mm;
            overflow: hidden;
        }

        /* ── HEADER STRIP ── */
        .header {
            background: #064E3B;
            color: white;
            padding: 1.5mm 2.5mm 1.2mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .agency-name {
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 0.3px;
        }
        .agency-sub {
            font-size: 5.5px;
            opacity: 0.75;
            margin-top: 0.5mm;
        }
        .ticket-label {
            background: rgba(255,255,255,0.18);
            border: 0.2mm solid rgba(255,255,255,0.35);
            padding: 0.5mm 1.5mm;
            border-radius: 1mm;
            font-size: 5.5px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: white;
        }

        /* ── ROUTE BAR ── */
        .route-bar {
            background: #047857;
            color: white;
            padding: 1mm 2.5mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .city { font-size: 9px; font-weight: bold; line-height: 1; }
        .city-label { font-size: 5px; opacity: 0.7; margin-top: 0.3mm; text-transform: uppercase; }
        .arrow { font-size: 8px; opacity: 0.8; padding: 0 1mm; }

        /* ── BODY (2 columns) ── */
        .body {
            flex: 1;
            display: flex;
            padding: 1.5mm 2.5mm;
            gap: 2mm;
        }

        /* Left column: Passenger info */
        .col-left {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1mm;
        }

        /* Right column: QR + seat */
        .col-right {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            width: 15mm;
        }

        .info-row {}
        .lbl {
            font-size: 5px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: bold;
        }
        .val {
            font-size: 7px;
            font-weight: bold;
            color: #0f172a;
            line-height: 1.2;
        }
        .val.green { color: #065F46; font-size: 8px; }
        .val.mono {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 6px;
        }

        .qr-img {
            width: 14mm;
            height: 14mm;
            border: 0.3mm solid #e2e8f0;
            border-radius: 0.5mm;
            padding: 0.5mm;
        }

        .seat-box {
            background: #064E3B;
            color: white;
            width: 14mm;
            text-align: center;
            border-radius: 1mm;
            padding: 1mm 0;
        }
        .seat-lab { font-size: 4.5px; opacity: 0.75; text-transform: uppercase; }
        .seat-num { font-size: 11px; font-weight: bold; line-height: 1; }

        /* ── FOOTER STRIP ── */
        .footer {
            background: #F1F5F9;
            border-top: 0.2mm solid #E2E8F0;
            padding: 0.8mm 2.5mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .res-num {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 6px;
            color: #064E3B;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .date-print {
            font-size: 5px;
            color: #94a3b8;
        }
        .status-ok {
            background: #dcfce7;
            color: #15803d;
            padding: 0.3mm 1.5mm;
            border-radius: 2mm;
            font-size: 5.5px;
            font-weight: bold;
        }
        .status-wait {
            background: #fef3c7;
            color: #b45309;
            padding: 0.3mm 1.5mm;
            border-radius: 2mm;
            font-size: 5.5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="card">

    <!-- HEADER -->
    <div class="header">
        <div>
            <div class="agency-name">{{ $agence['nom'] ?? 'GEV Transport' }}</div>
            <div class="agency-sub">{{ $gare['ville'] ?? '' }}{{ isset($gare['adresse']) ? ' — '.$gare['adresse'] : '' }}</div>
        </div>
        <div class="ticket-label">Billet</div>
    </div>

    <!-- ROUTE -->
    <div class="route-bar">
        <div>
            <div class="city">{{ $depart }}</div>
            <div class="city-label">Départ</div>
        </div>
        <span class="arrow">➜</span>
        <div style="text-align:right">
            <div class="city">{{ $arrivee }}</div>
            <div class="city-label">Arrivée</div>
        </div>
    </div>

    <!-- BODY -->
    <div class="body">
        <div class="col-left">
            <div class="info-row">
                <div class="lbl">Passager</div>
                <div class="val">{{ $passager }}</div>
            </div>
            <div class="info-row">
                <div class="lbl">Date &amp; Heure</div>
                <div class="val">{{ $dateDepart }} • {{ $heureDepart }}</div>
            </div>
            <div class="info-row">
                <div class="lbl">Bus / Classe</div>
                <div class="val">{{ $immatriculation }} — {{ strtoupper($typeTrajet) }}</div>
            </div>
            <div class="info-row">
                <div class="lbl">Tarif</div>
                <div class="val green">{{ number_format($prix, 0, ',', ' ') }} FCFA</div>
            </div>
        </div>

        <div class="col-right">
            <img class="qr-img" src="{{ $qrCodeUrl }}" alt="QR" />
            <div class="seat-box">
                <div class="seat-lab">Siège</div>
                <div class="seat-num">{{ $siege }}</div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <span class="res-num">{{ $numReservation }}</span>
        <span class="{{ $statut === 'validee' ? 'status-ok' : 'status-wait' }}">
            {{ $statut === 'validee' ? '✓ Validé' : '⏳ Attente' }}
        </span>
        <span class="date-print">{{ $printDate }}</span>
    </div>

</div>
</body>
</html>
