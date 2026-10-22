<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport de Clôture</title>
    <style>
        @font-face {
            font-family: 'Poppins';
            font-weight: 400;
            src: url('https://fonts.gstatic.com/s/poppins/v20/pxiEyp8kv8JHgFVrJJfecnFHGPc.ttf') format('truetype');
        }
        @font-face {
            font-family: 'Poppins';
            font-weight: 600;
            src: url('https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8JHgFVrLCz7Z1xlFd2JQEk.ttf') format('truetype');
        }
        @font-face {
            font-family: 'Poppins';
            font-weight: 700;
            src: url('https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8JHgFVrLCz7Z1xlFd2JQEk.ttf') format('truetype');
        }

        /* 🟢 صغرت هوامش الصفحة باش نستغلو مساحة الورقة A4 */
        @page { margin: 40px 50px 80px 50px; }

        body {
            font-family: 'Poppins', Helvetica, Arial, sans-serif;
            font-size: 13px; 
            color: #1f2937;
            line-height: 1.5;
        }

        .logo-circle {
            width: 50px; height: 50px;
            background-color: #1E1B4B;
            border-radius: 12px;
            color: white; text-align: center;
            line-height: 50px; font-size: 22px;
            font-weight: bold; margin-bottom: 20px;
        }
        .logo-img {
            width: 50px; height: 50px;
            border-radius: 12px;
            margin-bottom: 20px; display: block;
        }

        .header-table {
            width: 100%; border-collapse: collapse; margin-bottom: 20px;
        }
        .header-table td { vertical-align: top; padding: 0; }
        .header-left  { width: 45%; }
        .header-right { width: 55%; padding-left: 20px; }

        .doc-date {
            font-size: 12px; color: #1E1B4B;
            font-weight: bold; margin-bottom: 4px;
        }
        .title-doc {
            font-size: 26px; 
            font-weight: bold; color: #1E1B4B;
            margin: 0 0 4px 0; letter-spacing: -0.5px; line-height: 1.1;
        }
        .ref-line { font-size: 12px; color: #6b7280; font-weight: bold; }
        .ref-line span { color: #1E1B4B; }

        .info-label {
            font-weight: bold; color: #1E1B4B;
            font-size: 12px; margin-bottom: 2px; text-transform: uppercase;
        }
        .info-val {
            color: #374151; font-size: 13px;
            margin-bottom: 10px; line-height: 1.3;
        }

        .body-text {
            font-size: 13px;
            color: #1f2937; text-align: left;
            margin-bottom: 14px; margin-top: 0;
        }

        /* ===== SECTION TITLES ===== */
        .section-title {
            font-size: 16px; font-weight: bold; color: #1E1B4B;
            margin: 20px 0 8px 0; padding-bottom: 6px;
        }

        /* =======================================================
           🟢 الديزاين الجديد ديال الطابلو (بحال المنصة / bg-gray-200)
           ======================================================= */
        .stats-table {
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px;
            border: 1px solid #e2e8f0; /* إطار خفيف للطابلو */
        }
        .stats-table th {
            background-color: #e5e7eb; /* 🟢 لون bg-gray-200 */
            color: #4b5563;
            font-size: 11px; 
            font-weight: 700;
            padding: 12px 14px; 
            text-align: left;
            border-bottom: 2px solid #d1d5db;
            text-transform: uppercase; 
            letter-spacing: 0.5px;
        }
        .stats-table th:last-child {
            text-align: right; /* المبالغ لجهة اليمن */
        }
        .stats-table td {
            font-size: 13px; 
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9; 
            color: #334155;
            vertical-align: middle;
        }
        .stats-table tr:last-child td { border-bottom: none; }
        
        .stats-table .label-col { 
            font-weight: 600; 
            color: #1e293b; 
            width: 50%; 
        }
        
        .val-green { font-weight: bold; color: #059669; text-align: right; }
        .val-red   { font-weight: bold; color: #ef4444; text-align: right; }
        .val-dark  { font-weight: bold; color: #0f172a; text-align: right; }

        /* ===== GRAND TOTAL BLOCK ===== */
        .grand-total-block {
            background-color: #f8fafc;
            border-left: 4px solid #1E1B4B;
            border-radius: 4px;
            padding: 12px 16px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            border-left-width: 4px;
        }
        .grand-total-line {
            font-size: 13px; line-height: 1.8; color: #1f2937;
            display: flex; justify-content: space-between;
        }
        .grand-total-line span {
            font-weight: bold; color: #1E1B4B;
        }

        /* ===== FOOTER ===== */
        .footer {
            position: fixed; bottom: -50px; left: 0; right: 0;
            font-size: 10px; color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px; line-height: 1.4; text-align: center;
        }
    </style>
</head>
<body>

    <div class="footer">
        Contact : {{ $telephone }} &nbsp;/&nbsp; {{ $email }}<br>
        Disclaimer légal : Ce document est un rapport de clôture officiel et doit être traité comme tel.
    </div>

    @if(isset($logo_path) && file_exists($logo_path))
        <img src="{{ $logo_path }}" class="logo-img">
    @else
        <div class="logo-circle">S</div>
    @endif

    <table class="header-table">
        <tr>
            <td class="header-left">
                <div class="doc-date">Le {{ \Carbon\Carbon::now()->locale('fr')->translatedFormat('j F Y') }}</div>
                <div class="title-doc">Rapport de clôture</div>
                <div class="ref-line">
                    <span>Exercice</span> &rsaquo; {{ $se_identifier }}
                </div>
            </td>
            <td class="header-right">
                <div class="info-label">Résidence</div>
                <div class="info-val">{{ $residence_nom }}</div>

                <div class="info-label">Adresse</div>
                <div class="info-val">{{ $residence_adresse }}</div>

                <div class="info-label">Période</div>
                <div class="info-val" style="margin-bottom: 0;">
                    {{ \Carbon\Carbon::parse($exercice_start)->locale('fr')->translatedFormat('d/m/Y') }}
                    –
                    {{ \Carbon\Carbon::parse($exercice_end)->locale('fr')->translatedFormat('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>

    <div class="body-text" style="margin-top: 30px;">
        Ce rapport présente le bilan financier complet de l'exercice pour la résidence <strong>{{ $residence_nom }}</strong>.
        Il récapitule l'ensemble des encaissements, dépenses et soldes par type de budget.
    </div>

    <div class="section-title">Budget Prévisionnel</div>
    <table class="stats-table">
        <thead>
            <tr>
                <th>Indicateur</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="label-col">Budget total</td>
                <td class="val-dark">{{ number_format($prev['budget'], 2, ',', ' ') }} MAD</td>
            </tr>
            <tr>
                <td class="label-col">Total encaissements</td>
                <td class="val-green">{{ number_format($prev['encaissements'], 2, ',', ' ') }} MAD</td>
            </tr>
            <tr>
                <td class="label-col">Total dépenses</td>
                <td class="val-red">{{ number_format($prev['depenses'], 2, ',', ' ') }} MAD</td>
            </tr>
            <tr>
                <td class="label-col">Solde restant</td>
                <td class="val-dark">{{ number_format($prev['reste'], 2, ',', ' ') }} MAD</td>
            </tr>
            <tr>
                <td class="label-col">Total dû</td>
                <td class="val-red">{{ number_format($prev['du'], 2, ',', ' ') }} MAD</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Budget Travaux</div>
    <table class="stats-table">
        <thead>
            <tr>
                <th>Indicateur</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="label-col">Budget total</td>
                <td class="val-dark">{{ number_format($trav['budget'], 2, ',', ' ') }} MAD</td>
            </tr>
            <tr>
                <td class="label-col">Total encaissements</td>
                <td class="val-green">{{ number_format($trav['encaissements'], 2, ',', ' ') }} MAD</td>
            </tr>
            <tr>
                <td class="label-col">Total dépenses</td>
                <td class="val-red">{{ number_format($trav['depenses'], 2, ',', ' ') }} MAD</td>
            </tr>
            <tr>
                <td class="label-col">Solde restant</td>
                <td class="val-dark">{{ number_format($trav['reste'], 2, ',', ' ') }} MAD</td>
            </tr>
            <tr>
                <td class="label-col">Total dû</td>
                <td class="val-red">{{ number_format($trav['du'], 2, ',', ' ') }} MAD</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Grand Total</div>
    <div class="grand-total-block">
        <div class="grand-total-line">
            Budget total combiné : <span>{{ number_format($grand['budget'], 2, ',', ' ') }} MAD</span>
        </div>
        <div class="grand-total-line">
            Total encaissements : <span>{{ number_format($grand['encaissements'], 2, ',', ' ') }} MAD</span>
        </div>
        <div class="grand-total-line">
            Total dépenses : <span>{{ number_format($grand['depenses'], 2, ',', ' ') }} MAD</span>
        </div>
        <div class="grand-total-line">
            Solde final restant : <span>{{ number_format($grand['reste'], 2, ',', ' ') }} MAD</span>
        </div>
        <div class="grand-total-line">
            Total dû global : <span>{{ number_format($grand['du'], 2, ',', ' ') }} MAD</span>
        </div>
    </div>

    <div class="body-text" style="margin-top: 24px;">
        Cordialement,<br><strong>Le Syndic.</strong>
    </div>

</body>
</html>