<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Appel de Charges</title>
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; color: #333; line-height: 1.5; }
        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { color: #1A5EAC; margin-bottom: 0; }
        .content { margin: 0 30px; }
        .details-box { border: 1px solid #ddd; padding: 20px; border-radius: 8px; margin-top: 20px; background-color: #F4F6F9; }
        .amount { font-size: 24px; font-weight: bold; color: #D32F2F; text-align: center; margin: 20px 0; }
        .footer { position: fixed; bottom: 30px; text-align: center; width: 100%; font-size: 12px; color: #777; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Syndify - Avis d'Appel de Charges</h1>
        <p>Document généré le {{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>
    </div>

    <div class="content">
        <p><strong>Copropriétaire :</strong> {{ $user_name }}</p>
        <p><strong>Objet :</strong> {{ $title }}</p>

        <div class="details-box">
            <p>Veuillez trouver ci-dessous les détails de votre appel de charges :</p>
            
            <ul>
                <li><strong>Date d'émission :</strong> {{ \Carbon\Carbon::parse($date_emission)->format('d/m/Y') }}</li>
                <li><strong>Date d'échéance (à payer avant le) :</strong> {{ \Carbon\Carbon::parse($due_date)->format('d/m/Y') }}</li>
            </ul>

            <div class="amount">
                Montant dû : {{ number_format($amount, 2, ',', ' ') }} MAD
            </div>
        </div>

        <p style="margin-top: 30px;">
            Merci de procéder au règlement avant la date d'échéance. En cas de paiement déjà effectué, veuillez ne pas tenir compte de ce document.
        </p>
    </div>

    <div class="footer">
        Syndify App - Gestion de Copropriété Automatisée
    </div>

</body>
</html>