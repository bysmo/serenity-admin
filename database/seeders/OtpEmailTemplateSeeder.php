<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;

/**
 * OtpEmailTemplateSeeder
 *
 * Crée (ou met à jour) les templates d'emails essentiels pour :
 *  1. Vérification OTP à l'inscription d'un membre
 *  2. OTP pour opération sensible (changement de PIN, etc.)
 *  3. Réinitialisation de mot de passe membre
 *
 * Variables disponibles dans le moteur de templates :
 *   {{prenom}}       Prénom du membre
 *   {{nom}}          Nom du membre
 *   {{otp}}          Code OTP (6 chiffres)
 *   {{otp_expires}}  Délai d'expiration (ex: "10 minutes")
 *   {{nom_site}}     Nom de l'application (depuis AppSetting)
 *   {{annee}}        Année courante
 */
class OtpEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // ─────────────────────────────────────────────────────────────────────
        // 1. OTP Inscription membre (vérification email)
        // ─────────────────────────────────────────────────────────────────────
        EmailTemplate::updateOrCreate(
            ['nom' => 'OTP Inscription Membre'],
            [
                'nom'   => 'OTP Inscription Membre',
                'type'  => 'otp_inscription',
                'sujet' => '🔐 Votre code de vérification - {{nom_site}}',
                'actif' => true,
                'corps' => $this->templateOtpInscription(),
            ]
        );

        // ─────────────────────────────────────────────────────────────────────
        // 2. OTP Opération sensible (virement, changement PIN…)
        // ─────────────────────────────────────────────────────────────────────
        EmailTemplate::updateOrCreate(
            ['nom' => 'OTP Opération Sensible'],
            [
                'nom'   => 'OTP Opération Sensible',
                'type'  => 'otp_operation',
                'sujet' => '🔐 Code de confirmation pour votre opération - {{nom_site}}',
                'actif' => true,
                'corps' => $this->templateOtpOperation(),
            ]
        );

        // ─────────────────────────────────────────────────────────────────────
        // 3. Mise à jour du template "activation" existant avec le HTML OTP
        // ─────────────────────────────────────────────────────────────────────
        EmailTemplate::updateOrCreate(
            ['type' => 'activation'],
            [
                'nom'   => 'Activation de compte',
                'type'  => 'activation',
                'sujet' => '✅ Activez votre compte - {{nom_site}}',
                'actif' => true,
                'corps' => $this->templateActivation(),
            ]
        );

        $this->command->info('✅ Templates email OTP créés/mis à jour (otp_inscription, otp_operation, activation).');
    }

    // ─── Corps des templates ──────────────────────────────────────────────────

    private function templateOtpInscription(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vérification de votre compte</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:32px 0;">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

          <!-- En-tête -->
          <tr>
            <td style="background:linear-gradient(135deg,#1a1f3a 0%,#2d3561 100%);padding:32px 40px;text-align:center;">
              <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;letter-spacing:0.5px;">{{nom_site}}</h1>
              <p style="margin:8px 0 0;color:rgba(255,255,255,0.7);font-size:13px;">Bienvenue dans votre espace financier sécurisé</p>
            </td>
          </tr>

          <!-- Corps -->
          <tr>
            <td style="padding:40px;">
              <h2 style="margin:0 0 8px;color:#1a1f3a;font-size:18px;">Bonjour {{prenom}} 👋</h2>
              <p style="margin:0 0 24px;color:#555;font-size:15px;line-height:1.6;">
                Merci de vous être inscrit sur <strong>{{nom_site}}</strong>.<br>
                Pour finaliser la création de votre compte, veuillez entrer le code de vérification ci-dessous dans l'application :
              </p>

              <!-- Code OTP -->
              <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 28px;">
                <tr>
                  <td align="center">
                    <div style="display:inline-block;background:#f0f4ff;border:2px dashed #4a6cf7;border-radius:12px;padding:20px 40px;">
                      <p style="margin:0 0 4px;color:#888;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Votre code OTP</p>
                      <p style="margin:0;color:#1a1f3a;font-size:38px;font-weight:800;letter-spacing:10px;font-family:'Courier New',monospace;">{{otp}}</p>
                    </div>
                  </td>
                </tr>
              </table>

              <!-- Avertissement expiration -->
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#fff8e1;border-left:4px solid #f59e0b;border-radius:0 8px 8px 0;margin-bottom:24px;">
                <tr>
                  <td style="padding:12px 16px;">
                    <p style="margin:0;color:#92400e;font-size:13px;">
                      ⏱️ Ce code expire dans <strong>{{otp_expires}}</strong>. Ne le partagez avec personne.
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0;color:#888;font-size:13px;line-height:1.6;">
                Si vous n'avez pas créé de compte sur {{nom_site}}, ignorez cet email. Votre adresse ne sera pas utilisée.
              </p>
            </td>
          </tr>

          <!-- Pied de page -->
          <tr>
            <td style="background:#f8f9fa;padding:20px 40px;border-top:1px solid #e9ecef;text-align:center;">
              <p style="margin:0;color:#aaa;font-size:12px;">
                © {{annee}} {{nom_site}} — Tous droits réservés<br>
                Cet email a été envoyé automatiquement, merci de ne pas y répondre.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
HTML;
    }

    private function templateOtpOperation(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Code de confirmation</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:32px 0;">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

          <!-- En-tête orange/alerte -->
          <tr>
            <td style="background:linear-gradient(135deg,#b45309 0%,#f59e0b 100%);padding:32px 40px;text-align:center;">
              <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;">{{nom_site}}</h1>
              <p style="margin:8px 0 0;color:rgba(255,255,255,0.85);font-size:13px;">Confirmation de sécurité requise</p>
            </td>
          </tr>

          <tr>
            <td style="padding:40px;">
              <h2 style="margin:0 0 8px;color:#1a1f3a;font-size:18px;">Bonjour {{prenom}},</h2>
              <p style="margin:0 0 24px;color:#555;font-size:15px;line-height:1.6;">
                Une opération sensible a été initiée sur votre compte.<br>
                Utilisez le code ci-dessous pour la confirmer :
              </p>

              <!-- Code OTP -->
              <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 28px;">
                <tr>
                  <td align="center">
                    <div style="display:inline-block;background:#fff8e1;border:2px dashed #f59e0b;border-radius:12px;padding:20px 40px;">
                      <p style="margin:0 0 4px;color:#888;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Code de confirmation</p>
                      <p style="margin:0;color:#b45309;font-size:38px;font-weight:800;letter-spacing:10px;font-family:'Courier New',monospace;">{{otp}}</p>
                    </div>
                  </td>
                </tr>
              </table>

              <table width="100%" cellpadding="0" cellspacing="0" style="background:#fef2f2;border-left:4px solid #ef4444;border-radius:0 8px 8px 0;margin-bottom:24px;">
                <tr>
                  <td style="padding:12px 16px;">
                    <p style="margin:0;color:#991b1b;font-size:13px;">
                      🔒 Code valide <strong>{{otp_expires}}</strong>. Si vous n'êtes pas à l'origine de cette demande, sécurisez votre compte immédiatement.
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="background:#f8f9fa;padding:20px 40px;border-top:1px solid #e9ecef;text-align:center;">
              <p style="margin:0;color:#aaa;font-size:12px;">
                © {{annee}} {{nom_site}} — Cet email est automatique, ne pas répondre.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
HTML;
    }

    private function templateActivation(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Activation de votre compte</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:32px 0;">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

          <!-- En-tête vert succès -->
          <tr>
            <td style="background:linear-gradient(135deg,#065f46 0%,#10b981 100%);padding:32px 40px;text-align:center;">
              <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;">{{nom_site}}</h1>
              <p style="margin:8px 0 0;color:rgba(255,255,255,0.85);font-size:13px;">Activez votre compte</p>
            </td>
          </tr>

          <tr>
            <td style="padding:40px;">
              <h2 style="margin:0 0 8px;color:#1a1f3a;font-size:18px;">Bienvenue {{prenom}} {{nom}} 🎉</h2>
              <p style="margin:0 0 24px;color:#555;font-size:15px;line-height:1.6;">
                Votre compte <strong>{{nom_site}}</strong> a été créé avec succès.<br>
                Pour l'activer, saisissez ce code dans l'application :
              </p>

              <!-- Code OTP -->
              <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 28px;">
                <tr>
                  <td align="center">
                    <div style="display:inline-block;background:#f0fdf4;border:2px dashed #10b981;border-radius:12px;padding:20px 40px;">
                      <p style="margin:0 0 4px;color:#888;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Code d'activation</p>
                      <p style="margin:0;color:#065f46;font-size:38px;font-weight:800;letter-spacing:10px;font-family:'Courier New',monospace;">{{otp}}</p>
                    </div>
                  </td>
                </tr>
              </table>

              <table width="100%" cellpadding="0" cellspacing="0" style="background:#fff8e1;border-left:4px solid #f59e0b;border-radius:0 8px 8px 0;margin-bottom:24px;">
                <tr>
                  <td style="padding:12px 16px;">
                    <p style="margin:0;color:#92400e;font-size:13px;">
                      ⏱️ Ce code expire dans <strong>{{otp_expires}}</strong>.
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0;color:#888;font-size:13px;">
                Si vous n'avez pas créé ce compte, ignorez cet email.
              </p>
            </td>
          </tr>

          <tr>
            <td style="background:#f8f9fa;padding:20px 40px;border-top:1px solid #e9ecef;text-align:center;">
              <p style="margin:0;color:#aaa;font-size:12px;">
                © {{annee}} {{nom_site}} — Tous droits réservés
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
HTML;
    }
}
