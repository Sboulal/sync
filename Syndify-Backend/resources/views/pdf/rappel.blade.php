<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Avis de Rappel</title>
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
            font-size: 52px;
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

        .page-break { page-break-after: always; }
    </style>
</head>
<body>

    <div class="footer">
        Contact : {{ $telephone }} &nbsp;/&nbsp; {{ $email }}<br>
        Disclaimer légal : Ce document est un avis de rappel officiel et doit être traité comme tel.
    </div>

    @if(isset($logo_path) && file_exists($logo_path))
        <img src="{{ $logo_path }}" class="logo-img">
    @else
        <div class="logo-circle">S</div>
    @endif

    <table class="header-table">
        <tr>
            <td class="header-left">
                <div class="doc-date">
                    Le {{ \Carbon\Carbon::parse($date_rappel)->locale('fr')->translatedFormat('j F Y') }}
                </div>
                <div class="title-doc" style="color: #1E1B4B;">Avis de rappel</div>
                <div class="ref-line">
                    <span>Référence</span> &rsaquo; {{ $reference }}
                </div>
                
                <!-- 🟢 الكود بار رجع لبلاصتو الفوق بحال Appel de fonds -->
                <div style="margin-top: 15px;">
                    <img src="data:image/png;base64,{{ $barcode }}" alt="Code Barre" style="height: 35px; width: 140px; display: block;">
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

    <!-- 🟢 المساحة كتعوض الخط باش يجي الديزاين نقي -->
    <div class="body-text" style="margin-top: 80px;">
        Bonjour {{ $nom }},
    </div>

    <div class="body-text">
        Sauf erreur ou omission de notre part, nous constatons qu'à ce jour, votre compte de copropriété présente un solde débiteur. Nous vous prions de bien vouloir régulariser cette situation dans les plus brefs délais afin de permettre le bon fonctionnement de notre copropriété.
    </div>

   <div style="background-color: #FEF2F2; border-left: 4px solid #EF4444; padding: 16px 20px; border-radius: 4px; margin-bottom: 20px;">
        <div style="margin: 0; font-size: 20px; color: #1f2937; line-height: 1;">
            Montant total impayé : <span style="font-weight: bold; color: #EF4444;">{{ number_format($montant_du, 2, ',', ' ') }} MAD</span>
        </div>
    </div>

    <div class="body-text">
        Pour faciliter le règlement, vous pouvez effectuer le paiement par Virement bancaire.
    </div>

    <div class="body-text">
        <em style="font-size: 20px; color: #6b7280;">Si votre règlement a été envoyé entre-temps, veuillez ne pas tenir compte de cette lettre.</em><br><br>
        Nous vous remercions de votre collaboration.<br>
        En cas de questions, n'hésitez pas à contacter le syndic.
    </div>

    <div class="body-text" style="margin-top: 32px;">
        Cordialement,<br><strong>Le Syndic.</strong>
    </div>

</body>
</html>