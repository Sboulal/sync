<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reçu d'encaissement</title>
    <style>
        @font-face {
            font-family: 'Poppins';
            font-weight: 400;
            src: url('https://fonts.gstatic.com/s/poppins/v20/pxiEyp8kv8JHgFVrJJfecnFHGPc.ttf') format('truetype');
        }
        @font-face {
            font-family: 'Poppins';
            font-weight: 700;
            src: url('https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8JHgFVrLCz7Z1xlFd2JQEk.ttf') format('truetype');
        }

        @page {
            margin: 50px 80px 110px 80px;
        }

        body {
            font-family: 'Poppins', Helvetica, Arial, sans-serif;
            font-size: 17px;
            color: #1f2937;
            line-height: 1.6;
        }

        .logo-circle {
            width: 64px;
            height: 64px;
            background-color: #1E1B4B;
            border-radius: 16px;
            color: white;
            text-align: center;
            line-height: 64px;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 28px;
        }
        .logo-img {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            margin-bottom: 28px;
            display: block;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        .header-table td {
            vertical-align: top;
            padding: 0;
        }
        .header-left { width: 48%; }
        .header-right {
            width: 52%;
            padding-left: 32px;
        }

        .doc-date {
            font-size: 18px;
            color: #1E1B4B;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .title-doc {
            font-size: 46px; /* نقصت منو شوية باش مايخرجش للسطر الثاني */
            font-weight: bold;
            color: #1E1B4B;
            margin: 0 0 1px 0;
            letter-spacing: -1px;
            line-height: 1.05;
        }
        .ref-line {
            font-size: 20px;
            color: #6b7280;
            font-weight: bold;
        }
        .ref-line span {
            color: #1E1B4B;
        }

        .info-label {
            font-weight: bold;
            color: #1E1B4B;
            font-size: 20px;
            margin-bottom: 1px;
        }
        .info-val {
            color: #374151;
            font-size: 20px;
            margin-bottom: 14px;
        }

        .divider {
            border: none;
            border-top: 1.5px solid #e5e7eb;
            margin: 8px 0 8px 0;
        }

        .body-text {
            font-size: 20px;
            color: #1f2937;
            text-align: left;
            margin-bottom: 20px;
            margin-top: 0;
        }

        .montant-block {
            margin-bottom: 22px;
        }
        .montant-line {
            font-size: 20px;
            line-height: 2.0;
            color: #1f2937;
        }
        .montant-line span {
            font-weight: bold;
            color: #1E1B4B;
        }

        .footer {
            position: fixed;
            bottom: -75px;
            left: 0;
            right: 0;
            font-size: 18px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
            line-height: 1.3;
        }

    </style>
</head>
<body>

    <div class="footer">
        Contact : {{ $telephone }} &nbsp;/&nbsp; {{ $email }}<br>
        Disclaimer légal : Ce document est un reçu officiel et doit être traité comme tel.
    </div>

    {{-- LOGO --}}
    @if(isset($logo_path) && file_exists($logo_path))
        <img src="{{ $logo_path }}" class="logo-img">
    @else
        <div class="logo-circle">S</div>
    @endif

    {{-- HEADER --}}
    <table class="header-table">
        <tr>
            <td class="header-left">
                <div class="doc-date">
                    Le {{ \Carbon\Carbon::parse($date_paiement)->locale('fr')->translatedFormat('j F Y') }}
                </div>
                <div class="title-doc">Reçu d'encaissement</div>
                <div class="ref-line">
                    <span>Référence</span> &rsaquo; {{ $reference }}
                </div>
            </td>
            <td class="header-right">
                <div class="info-label">Destinataire</div>
                <div class="info-val">Monsieur/Madame {{ $nom }}</div>

                <div class="info-label">Adresse</div>
                <div class="info-val">{{ $adresse }}</div>

                <div class="info-label">Lots</div>
                <div class="info-val" style="margin-bottom: 0;">{{ $lots }}</div>
            </td>
        </tr>
    </table>

    {{-- ESPACE POUR COMPENSER LE hr QUI N'EST PLUS LA --}}
    <div class="body-text" style="margin-top: 80px;">
        Cher(e) {{ $nom }},
    </div>

    <div class="body-text">
        Nous vous confirmons la réception de votre paiement d'un montant de <strong>{{ number_format($montant, 2, ',', ' ') }} MAD</strong>, effectué le {{ \Carbon\Carbon::parse($date_paiement)->locale('fr')->translatedFormat('d/m/Y') }}, via {{ $mode_paiement }}. Ce paiement concerne les charges liées à <strong>{{ $nom_copropriete }}</strong> pour la période de {{ $periode }}.
    </div>

    <div class="body-text" style="margin-top: 24px;">
        Le paiement couvre les frais suivants :
    </div>

    <div class="montant-block">
        <div class="montant-line" style="margin-bottom: 4px;">
            Type de frais réglés : <span>{{ $type_frais }}</span>
        </div>
        <div class="montant-line">
            Période concernée : <span>{{ $periode }}</span>
        </div>
    </div>

    <div class="body-text" style="margin-top: 24px;">
        Nous vous remercions pour votre prompt règlement. Ce reçu fait foi et peut être utilisé comme preuve de paiement.
    </div>

    <div class="body-text" style="margin-top: 32px;">
        Cordialement,<br><strong>Le Syndic.</strong>
    </div>

</body>
</html>