<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Relevé des Opérations</title>
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
            width: 100%; border-collapse: collapse; margin-bottom: 30px;
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

        .section-title {
            font-size: 16px; font-weight: bold; color: #1E1B4B;
            margin: 0 0 14px 0; padding-bottom: 6px;
        }

        /* =======================================================
           🟢 الديزاين الجديد ديال الطابلو
           ======================================================= */
        .ops-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 16px; 
            border: 1px solid #e2e8f0; 
        }
        
        .ops-table th {
            background-color: #e5e7eb; /* 🟢 bg-gray-200 */
            color: #4b5563;            /* لون الخط رمادي غامق باش يبان مع الخلفية */
            font-size: 11px; 
            font-weight: 700;          /* خط غليظ باش يبان العنوان */
            padding: 12px 14px; 
            text-align: left;
            border-bottom: 2px solid #d1d5db; /* خط التحت مغلوق شوية على الخلفية */
            text-transform: uppercase; 
            letter-spacing: 0.5px;
        }
        
        .ops-table td {
            font-size: 13px; 
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155; 
            vertical-align: middle;
        }
        
        .ops-table tr:last-child td { border-bottom: none; }
        
        /* ألوان الأعمدة */
        .ops-table .date-col  { color: #64748b; font-weight: 500; width: 15%; }
        .ops-table .type-col  { width: 18%; }
        .ops-table .label-col { font-weight: 600; color: #1e293b; }
        .ops-table .amt-col   { text-align: right; width: 22%; font-weight: bold; color: #0f172a; }

        /* ======================================================= */

        .badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; }
        .badge-enc { background: #ecfdf5; color: #059669; }
        .badge-dep { background: #fef2f2; color: #ef4444; }
        .text-green { color: #059669; }
        .text-red   { color: #ef4444; }

        .empty-row td { text-align: center; color: #94a3b8; padding: 30px; font-size: 13px; font-style: italic; }

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
        {{ $residence->nom ?? '' }} &nbsp;/&nbsp; Document généré officiellement par le système — {{ date('Y') }}
    </div>

    @if(isset($logo_path) && file_exists($logo_path))
        <img src="{{ $logo_path }}" class="logo-img">
    @else
        <div class="logo-circle">S</div>
    @endif

    <table class="header-table">
        <tr>
            <td class="header-left">
                <div class="doc-date">Édité le {{ date('d/m/Y') }}</div>
                <div class="title-doc">Relevé des opérations</div>
                <div class="ref-line">
                    <span>Budget</span> &rsaquo; {{ ucfirst($type ?? 'Prévisionnel') }}
                </div>
            </td>
            <td class="header-right">
                <div class="info-label">Résidence</div>
                <div class="info-val">{{ $residence->nom ?? 'Non définie' }}</div>

                <div class="info-label">Adresse</div>
                <div class="info-val">{{ $residence->address ?? '' }}</div>

                <div class="info-label">Exercice</div>
                <div class="info-val" style="margin-bottom: 0;">{{ $exercice ?? 'Non défini' }}</div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 20px;">
        <div class="section-title">Liste des opérations</div>
        <table class="ops-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Libellé</th>
                    <th style="text-align: right;">Montant (MAD)</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($operations) && count($operations) > 0)
                    @foreach($operations as $op)
                    <tr>
                        <td class="date-col">{{ \Carbon\Carbon::parse($op->date)->format('d/m/Y') }}</td>
                        <td class="type-col">
                            <span class="badge {{ $op->type === 'Encaissement' ? 'badge-enc' : 'badge-dep' }}">
                                {{ $op->type }}
                            </span>
                        </td>
                        <td class="label-col">{{ $op->libelle }}</td>
                        <td class="amt-col">
                            @if($op->montant > 0)
                                <span class="text-green">+{{ number_format($op->montant, 2, ',', ' ') }}</span>
                            @else
                                <span class="text-red">{{ number_format($op->montant, 2, ',', ' ') }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                @else
                    <tr class="empty-row">
                        <td colspan="4">Aucune opération trouvée pour ce relevé.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

</body>
</html>