<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation de mot de passe</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f9; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f6f9; padding:20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
                    <tr>
                        <td align="center" style="padding:30px 20px 10px 20px;">
                            <img src="{{ asset('logo-softbridge.png') }}" alt="SoftBridge" style="height:60px; margin-bottom:15px;">
                            <h1 style="color:#0C3A7A; font-size:24px; margin:0;">Réinitialisation de mot de passe</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 30px; color:#333333; font-size:16px; line-height:1.6;">
                            <p>Bonjour,</p>
                            <p>Vous avez demandé la réinitialisation de votre mot de passe SoftBridge. Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe :</p>
                            <p style="text-align:center; margin:30px 0;">
                                <a href="{{ $url }}" style="display:inline-block; background-color:#1572E8; color:#ffffff; text-decoration:none; padding:14px 35px; border-radius:8px; font-weight:bold;">Réinitialiser mon mot de passe</a>
                            </p>
                            <p>Ce lien est valable pendant <strong>15 minutes</strong>. Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#0C3A7A; padding:20px; text-align:center; color:#ffffff; font-size:14px;">
                            © {{ date('Y') }} SoftBridge - Tous droits réservés
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>