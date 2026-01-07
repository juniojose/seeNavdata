<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'config.php';

header('Content-Type: application/json');

// Receber dados do JSON enviado pelo JS
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Nenhum dado recebido.']);
    exit;
}

$clientData = $input['clientData'] ?? [];
$geoData = $input['geoData'] ?? null;

// Coletar dados do servidor novamente
$serverData = [
    'IP' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
    'Porta' => $_SERVER['REMOTE_PORT'] ?? 'N/A',
    'Método' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
    'User Agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
    'Data/Hora' => date('Y-m-d H:i:s')
];

// Montar corpo do e-mail HTML
$mailContent = "<h1>Relatório de Diagnóstico seeNavdata</h1>";
$mailContent .= "<p>Dados coletados em: <strong>{$serverData['Data/Hora']}</strong></p>";

// Seção Geolocalização
if ($geoData && isset($geoData['status']) && $geoData['status'] == 'success') {
    $mailContent .= "<h2>🌍 Geolocalização (IP-API)</h2><table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    $geoFields = [
        'País' => $geoData['country'] . " (" . $geoData['countryCode'] . ")",
        'Região/Estado' => $geoData['regionName'],
        'Cidade' => $geoData['city'],
        'Fuso Horário (ISP)' => $geoData['timezone'],
        'Provedor (ISP)' => $geoData['isp'],
        'Organização' => $geoData['org'],
        'Latitude/Longitude' => $geoData['lat'] . ", " . $geoData['lon'],
        'Conexão Móvel?' => isset($geoData['mobile']) && $geoData['mobile'] ? 'Sim' : 'Não',
        'Proxy/VPN?' => isset($geoData['proxy']) && $geoData['proxy'] ? 'Sim' : 'Não'
    ];
    foreach ($geoFields as $key => $val) {
        $mailContent .= "<tr><td style='background-color: #e8f5e9; width: 30%;'><strong>$key</strong></td><td>$val</td></tr>";
    }
    $mailContent .= "</table>";
} else {
    $mailContent .= "<h2>🌍 Geolocalização</h2><p>Não foi possível obter dados de geolocalização ou a API falhou.</p>";
}

// Seção Cliente JS
$mailContent .= "<h2>🖥️ Dados do Cliente (JS)</h2><table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
foreach ($clientData as $item) {
    $mailContent .= "<tr><td style='background-color: #f2f2f2; width: 30%;'><strong>" . htmlspecialchars($item['label']) . "</strong></td><td>" . htmlspecialchars($item['value']) . "</td></tr>";
}
$mailContent .= "</table>";

// Seção Servidor PHP
$mailContent .= "<h2>📍 Dados do Servidor (PHP)</h2><table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
foreach ($serverData as $key => $val) {
    $mailContent .= "<tr><td style='background-color: #f2f2f2; width: 30%;'><strong>$key</strong></td><td>$val</td></tr>";
}
$mailContent .= "</table>";

$mailContent .= "<h3>Headers Completos</h3><pre>" . print_r(getallheaders(), true) . "</pre>";

// Configurar e enviar PHPMailer
$mail = new PHPMailer(true);

try {
    // Configurações do Servidor
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    // Destinatários
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress(EMAIL_TO);

    // Conteúdo
    $mail->isHTML(true);
    $mail->Subject = 'Relatório seeNavdata - ' . $serverData['IP'];
    $mail->Body    = $mailContent;
    $mail->AltBody = 'Seu cliente de e-mail não suporta HTML.';

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Relatório enviado com sucesso!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => "Erro ao enviar e-mail: {$mail->ErrorInfo}"]);
}