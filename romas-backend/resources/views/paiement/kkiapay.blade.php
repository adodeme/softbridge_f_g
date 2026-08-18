<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement – SoftBridge</title>
    <script src="https://cdn.kkiapay.me/k.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .payment-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .payment-card img {
            height: 60px;
            margin-bottom: 20px;
        }
        .payment-card h1 {
            font-size: 24px;
            color: #0C3A7A;
            margin-bottom: 10px;
        }
        .payment-card p {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .amount {
            font-size: 36px;
            font-weight: bold;
            color: #1572E8;
            margin: 20px 0;
        }
        .btn-pay {
            background-color: #1572E8;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 16px 30px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
            width: 100%;
            margin-bottom: 10px;
        }
        .btn-pay:hover {
            background-color: #0C3A7A;
        }
        .secure-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #28a745;
            font-size: 14px;
        }
        .secure-info i {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="payment-card">
        <img src="/logo-softbridge.png" alt="SoftBridge">
        <h1>Paiement sécurisé</h1>
        <p>
            Vous êtes sur le point de payer pour <strong>{{ $transaction->metadata['type'] === 'devis' ? 'votre projet sur mesure' : 'votre abonnement SaaS' }}</strong>.
            Veuillez cliquer sur le bouton ci-dessous pour procéder au paiement via KkiaPay.
        </p>
        <div class="amount">{{ number_format($transaction->amount, 2, ',', ' ') }} FCFA</div>

        <button id="payBtn" class="btn-pay">
            <i class="fas fa-lock mr-2"></i> Payer maintenant
        </button>
        <div class="secure-info">
            <i class="fas fa-lock"></i> Transaction cryptée et sécurisée
        </div>

        <kkiapay-widget
            amount="{{ $transaction->amount }}"
            key="{{ $publicKey }}"
            sandbox="{{ $sandbox }}"
            data="{{ $transaction->reference }}"
            callback="{{ $callbackUrl }}"
        ></kkiapay-widget>
    </div>

    <script>
        document.getElementById('payBtn').addEventListener('click', function() {
            const widget = document.querySelector('kkiapay-widget');
            if (widget) {
                widget.open();
            } else {
                console.error('Widget Kkiapay introuvable');
            }
        });

        // Pas besoin de forcer la redirection : le widget gère le callback via l'attribut callback.
    </script>

    <!-- Font Awesome pour l'icône cadenas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>