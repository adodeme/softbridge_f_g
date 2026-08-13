<!DOCTYPE html>
<html>
<head>
    <title>Paiement KkiaPay</title>
    <script src="https://cdn.kkiapay.me/k.js"></script>
</head>
<body>
    <h1>Paiement</h1>
    <button id="payBtn">Payer {{ $transaction->amount }} FCFA</button>

    <kkiapay-widget
        amount="{{ $transaction->amount }}"
        key="{{ $publicKey }}"
        sandbox="{{ $sandbox }}"
        data="{{ $transaction->reference }}"
        callback="{{ route('kkiapay.callback') }}"
    ></kkiapay-widget>

    <script>
        document.getElementById('payBtn').addEventListener('click', function() {
            document.querySelector('kkiapay-widget').open();
        });

        window.addEventListener('kkiapay:success', function(e) {
            window.location.href = "{{ route('kkiapay.callback') }}?reference={{ $transaction->reference }}";
        });
    </script>
</body>
</html>