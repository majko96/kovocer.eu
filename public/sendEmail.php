<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

header('Content-Type: application/json; charset=utf-8');

function respond(bool $ok, string $message): void
{
    http_response_code($ok ? 200 : 400);
    echo json_encode(['status' => $ok ? 'success' : 'failed', 'response' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Nesprávna metóda požiadavky.');
}

// Honeypot — spamboty vyplnia skryté pole, ľudia nie.
if (!empty($_POST['website'] ?? '')) {
    respond(true, 'Vaša správa bola odoslaná.');
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));

if ($name === '' || $email === '' || $message === '') {
    respond(false, 'Vyplňte prosím všetky polia.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Zadajte platnú e-mailovú adresu.');
}

// Zdroje konfigurácie, v poradí priority:
//   1. Skutočné environment variables (ak by ich hosting niekedy nastavoval priamo pre PHP)
//   2. .env súbor zapísaný build krokom (bežne o úroveň vyššie než public_html — PHP
//      ho samo nečíta, treba ho ručne naparsovať)
//   3. smtp-config.php (o úroveň vyššie, prípadne vedľa seba, chránený cez .htaccess)
function envOrNull(string $key): ?string
{
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }
    return (isset($_ENV[$key]) && $_ENV[$key] !== '') ? (string) $_ENV[$key] : null;
}

/** @return array<string, string> */
function parseDotEnv(string $path): array
{
    $values = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (strlen($value) >= 2 && (
            ($value[0] === '"' && $value[-1] === '"') ||
            ($value[0] === "'" && $value[-1] === "'")
        )) {
            $value = substr($value, 1, -1);
        }
        $values[$key] = $value;
    }
    return $values;
}

$dotEnv = [];
foreach ([__DIR__ . '/../.env', __DIR__ . '/.env'] as $dotEnvPath) {
    if (is_file($dotEnvPath)) {
        $dotEnv = parseDotEnv($dotEnvPath);
        break;
    }
}

function configOrNull(string $key, array $dotEnv): ?string
{
    return envOrNull($key) ?? (isset($dotEnv[$key]) && $dotEnv[$key] !== '' ? $dotEnv[$key] : null);
}

$host = configOrNull('SMTP_HOST', $dotEnv);

if ($host !== null) {
    $config = [
        'host' => $host,
        'port' => (int) (configOrNull('SMTP_PORT', $dotEnv) ?? 465),
        'secure' => configOrNull('SMTP_SECURE', $dotEnv) ?? 'ssl',
        'username' => configOrNull('SMTP_USERNAME', $dotEnv) ?? '',
        'password' => configOrNull('SMTP_PASSWORD', $dotEnv) ?? '',
        'from_email' => configOrNull('SMTP_FROM_EMAIL', $dotEnv) ?? configOrNull('SMTP_USERNAME', $dotEnv) ?? '',
        'from_name' => configOrNull('SMTP_FROM_NAME', $dotEnv) ?? 'KOVOCER.eu — web',
        'to_email' => configOrNull('SMTP_TO_EMAIL', $dotEnv) ?? '',
    ];
} else {
    $configPath = __DIR__ . '/../smtp-config.php';
    if (!is_file($configPath)) {
        $configPath = __DIR__ . '/smtp-config.php';
    }
    if (!is_file($configPath)) {
        respond(false, 'Mailer nie je nakonfigurovaný.');
    }
    /** @var array<string, mixed> $config */
    $config = require $configPath;
}

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->CharSet = 'UTF-8';
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->Port = (int) $config['port'];
    $mail->SMTPSecure = $config['secure'];

    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($config['to_email']);
    $mail->addReplyTo($email, $name);

    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safePhone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    $siteUrl = 'https://www.kovocer.eu';

    $phoneDisplay = $safePhone !== '' ? $safePhone : '—';

    $mail->isHTML(true);
    $mail->Subject = 'Dopyt z webu — ' . $name;
    $mail->Body = <<<HTML
    <!doctype html>
    <html lang="sk">
      <body style="margin:0;padding:0;background-color:#f4f4f3;font-family:Arial,Helvetica,sans-serif;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f3;padding:32px 16px;">
          <tr>
            <td align="center">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background-color:#ffffff;">
                <tr>
                  <td style="background-color:#0B171C;padding:24px 32px;">
                    <span style="font-size:20px;letter-spacing:.2em;font-weight:bold;color:#C9B57E;">KOVOCER.eu</span>
                  </td>
                </tr>
                <tr>
                  <td style="height:4px;background-color:#B39D60;line-height:4px;font-size:0;">&nbsp;</td>
                </tr>
                <tr>
                  <td style="padding:32px;">
                    <p style="margin:0 0 4px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#B39D60;font-weight:bold;">Nový dopyt z webu</p>
                    <h1 style="margin:0 0 24px;font-size:22px;line-height:1.3;color:#0a0a0a;">Niekto vám napísal cez kovocer.eu</h1>

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                      <tr>
                        <td style="padding:12px 16px;background-color:#f4f4f3;border-left:4px solid #B39D60;">
                          <p style="margin:0;font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#8a8a86;">Meno a priezvisko</p>
                          <p style="margin:4px 0 0;font-size:15px;color:#0a0a0a;font-weight:bold;">{$safeName}</p>
                        </td>
                      </tr>
                      <tr><td style="height:12px;line-height:12px;font-size:0;">&nbsp;</td></tr>
                      <tr>
                        <td style="padding:12px 16px;background-color:#f4f4f3;border-left:4px solid #B39D60;">
                          <p style="margin:0;font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#8a8a86;">E-mail</p>
                          <p style="margin:4px 0 0;font-size:15px;"><a href="mailto:{$safeEmail}" style="color:#B39D60;text-decoration:none;font-weight:bold;">{$safeEmail}</a></p>
                        </td>
                      </tr>
                      <tr><td style="height:12px;line-height:12px;font-size:0;">&nbsp;</td></tr>
                      <tr>
                        <td style="padding:12px 16px;background-color:#f4f4f3;border-left:4px solid #B39D60;">
                          <p style="margin:0;font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#8a8a86;">Telefón</p>
                          <p style="margin:4px 0 0;font-size:15px;color:#0a0a0a;font-weight:bold;">{$phoneDisplay}</p>
                        </td>
                      </tr>
                      <tr><td style="height:12px;line-height:12px;font-size:0;">&nbsp;</td></tr>
                      <tr>
                        <td style="padding:12px 16px;background-color:#f4f4f3;border-left:4px solid #B39D60;">
                          <p style="margin:0;font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#8a8a86;">Správa</p>
                          <p style="margin:4px 0 0;font-size:15px;color:#0a0a0a;line-height:1.6;">{$safeMessage}</p>
                        </td>
                      </tr>
                    </table>

                    <a href="mailto:{$safeEmail}" style="display:inline-block;background-color:#B39D60;color:#ffffff;text-decoration:none;font-weight:bold;font-size:13px;letter-spacing:.04em;text-transform:uppercase;padding:14px 28px;">Odpovedať {$safeName}</a>
                  </td>
                </tr>
                <tr>
                  <td style="padding:20px 32px;background-color:#f4f4f3;">
                    <p style="margin:0;font-size:12px;color:#8a8a86;">Táto správa prišla z kontaktného formulára na <a href="{$siteUrl}" style="color:#8a8a86;">kovocer.eu</a>.</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </body>
    </html>
    HTML;
    $mail->AltBody = "Meno: {$name}\nEmail: {$email}\nTelefon: {$phone}\n\nSprava:\n{$message}";

    $mail->send();
    respond(true, 'Vaša správa bola odoslaná.');
} catch (PHPMailerException $e) {
    error_log('sendEmail.php PHPMailer error: ' . $mail->ErrorInfo);
    respond(false, 'Správu sa nepodarilo odoslať, skúste to prosím neskôr.');
} catch (Throwable $e) {
    error_log('sendEmail.php error: ' . $e->getMessage());
    respond(false, 'Správu sa nepodarilo odoslať, skúste to prosím neskôr.');
}
