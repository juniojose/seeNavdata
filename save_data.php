<?php
require_once 'database.php';

header('Content-Type: application/json');

// Receber dados
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Nenhum dado recebido.']);
    exit;
}

$clientData = $input['clientData'] ?? [];
$geoData = $input['geoData'] ?? [];

// Helper para extrair valor do array de clientData (que vem no formato {label: 'x', value: 'y'})
function getClientValue($data, $labelStart) {
    foreach ($data as $item) {
        if (strpos($item['label'], $labelStart) !== false) {
            return $item['value'];
        }
    }
    return 'N/A';
}

// Extração de Dados
$ip = $_SERVER['REMOTE_ADDR'];
$ua = $_SERVER['HTTP_USER_AGENT'];
$platform = getClientValue($clientData, 'Plataforma');
$resolution = getClientValue($clientData, 'Resolução');
$timezoneJS = getClientValue($clientData, 'Fuso Horário');
$canvasHash = getClientValue($clientData, 'Canvas Hash');
$webgl = getClientValue($clientData, 'GPU Renderer');

$timezoneGeo = $geoData['timezone'] ?? 'N/A';
$countryGeo = $geoData['country'] ?? 'N/A';
$isp = $geoData['isp'] ?? 'N/A';
$isMobile = isset($geoData['mobile']) && $geoData['mobile'] ? 1 : 0;

// Lógica de Detecção de "Bot Farm" (Simples)
// Regra: IP Brasileiro mas Timezone Asiático (Padrão TV Box/Android Genérico)
$isSuspicious = 0;
if ($countryGeo == 'Brazil' && $timezoneJS == 'Asia/Shanghai') {
    $isSuspicious = 1;
}
// Regra: Headless Chrome
if (strpos(strtolower($ua), 'headless') !== false) {
    $isSuspicious = 1;
}

// Salvar no Banco
$dbClass = new Database();
$db = $dbClass->getDb();

$stmt = $db->prepare("INSERT INTO visits (ip_address, user_agent, platform, screen_resolution, timezone_js, timezone_geo, country_geo, isp, canvas_hash, webgl_renderer, is_mobile, is_suspicious, raw_data) VALUES (:ip, :ua, :plat, :res, :tzjs, :tzgeo, :country, :isp, :canvas, :webgl, :mob, :susp, :raw)");

$stmt->bindValue(':ip', $ip);
$stmt->bindValue(':ua', $ua);
$stmt->bindValue(':plat', $platform);
$stmt->bindValue(':res', $resolution);
$stmt->bindValue(':tzjs', $timezoneJS);
$stmt->bindValue(':tzgeo', $timezoneGeo);
$stmt->bindValue(':country', $countryGeo);
$stmt->bindValue(':isp', $isp);
$stmt->bindValue(':canvas', $canvasHash);
$stmt->bindValue(':webgl', $webgl);
$stmt->bindValue(':mob', $isMobile);
$stmt->bindValue(':susp', $isSuspicious);
$stmt->bindValue(':raw', json_encode($input));

$result = $stmt->execute();

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Dados registrados com sucesso.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar no banco.']);
}

$dbClass->close();
