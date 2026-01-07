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

// Helper para extrair valor do array de clientData
function getClientValue($data, $labelStart) {
    foreach ($data as $item) {
        if (strpos($item['label'], $labelStart) !== false) {
            return trim($item['value']); // Adicionado trim()
        }
    }
    return '';
}

// --- Extração de Dados ---
$ip = $_SERVER['REMOTE_ADDR'];
$ua = $_SERVER['HTTP_USER_AGENT'];

// Extraindo do JS
$platformJS = getClientValue($clientData, 'Plataforma');
$resolution = getClientValue($clientData, 'Resolução');
$timezoneJS = getClientValue($clientData, 'Fuso Horário');
$canvasHash = getClientValue($clientData, 'Canvas Hash');
$webglVendor = getClientValue($clientData, 'GPU Vendor');
$webglRenderer = getClientValue($clientData, 'GPU Renderer');
$webdriver = getClientValue($clientData, 'Automação (Webdriver)');
$batteryLevel = getClientValue($clientData, 'Bateria (Nível)');
$batteryCharging = getClientValue($clientData, 'Bateria (Carregando)');

// Extraindo do Geo/PHP
$timezoneGeo = $geoData['timezone'] ?? 'N/A';
$countryGeo = $geoData['country'] ?? 'N/A';
$isp = $geoData['isp'] ?? 'N/A';
$isMobile = isset($geoData['mobile']) && $geoData['mobile'] ? 1 : 0;

// --- MOTOR DE DETECÇÃO DE BOT FARM (Heurística Robusta) ---
$flags = []; 

// 1. Verificação de Automação Explícita
if (strpos($webdriver, 'DETECTADO') !== false || strpos(strtolower($webdriver), 'true') !== false) {
    $flags[] = "Navegador reportou navigator.webdriver = true";
}

// 2. Verificação de Timezone (Padrão TV Box China) - CORRIGIDO
// Verifica se país contém 'brazil'/'brasil' E fuso contém 'Asia' ou 'Shanghai'
$countryLower = strtolower($countryGeo);
$tzLower = strtolower($timezoneJS);

if ((strpos($countryLower, 'brazil') !== false || strpos($countryLower, 'brasil') !== false) && 
    (strpos($tzLower, 'asia') !== false || strpos($tzLower, 'shanghai') !== false)) {
    $flags[] = "IP Brasileiro ($countryGeo) com Fuso Horário Asiático ($timezoneJS)";
}

// 3. Verificação de WebGL (Renderizadores de Software/Emuladores)
$softwareRenderers = ['SwiftShader', 'llvmpipe', 'VirtualBox', 'VMware', 'Mesa OffScreen', 'Android Emulator'];
foreach ($softwareRenderers as $soft) {
    if (stripos($webglRenderer, $soft) !== false) { // Usando stripos para case-insensitive
        $flags[] = "Renderizador WebGL de Software/VM detectado: $webglRenderer";
        break;
    }
}

// 4. Verificação de Bateria (Padrão Farm: Sempre 100% e na tomada)
if ($isMobile && $batteryLevel == '100%' && ($batteryCharging == 'Sim' || $batteryCharging == 'true')) {
    $flags[] = "Comportamento de Farm: Mobile com 100% de bateria e carregando";
}

// 5. User Agent Headless
if (stripos($ua, 'headless') !== false) {
    $flags[] = "User-Agent contém 'Headless'";
}

// 6. Inconsistência de Plataforma (Emulador vs Real)
// Ex: UserAgent diz "Android", mas Plataforma diz "Win32" (Emulador rodando no Windows)
if (stripos($ua, 'Android') !== false && stripos($platformJS, 'Win') !== false) {
    $flags[] = "Inconsistência: User-Agent Android rodando em Plataforma Windows ($platformJS)";
}

// 7. Resolução Anômala (Janelas Headless minúsculas)
$resParts = explode('x', str_replace(' ', '', $resolution));
if (count($resParts) == 2 && ((int)$resParts[0] < 300 || (int)$resParts[1] < 300)) {
    $flags[] = "Resolução de tela suspeita/muito pequena: $resolution";
}

// --- Fim da Detecção ---

$isSuspicious = count($flags) > 0 ? 1 : 0;

// Adicionar flags ao raw_data para exibição no Dashboard
$input['suspicious_flags'] = $flags;

// Salvar no Banco
$dbClass = new Database();
$db = $dbClass->getDb();

$stmt = $db->prepare("INSERT INTO visits (ip_address, user_agent, platform, screen_resolution, timezone_js, timezone_geo, country_geo, isp, canvas_hash, webgl_renderer, is_mobile, is_suspicious, raw_data) VALUES (:ip, :ua, :plat, :res, :tzjs, :tzgeo, :country, :isp, :canvas, :webgl, :mob, :susp, :raw)");

$stmt->bindValue(':ip', $ip);
$stmt->bindValue(':ua', $ua);
$stmt->bindValue(':plat', $platformJS);
$stmt->bindValue(':res', $resolution);
$stmt->bindValue(':tzjs', $timezoneJS);
$stmt->bindValue(':tzgeo', $timezoneGeo);
$stmt->bindValue(':country', $countryGeo);
$stmt->bindValue(':isp', $isp);
$stmt->bindValue(':canvas', $canvasHash);
$stmt->bindValue(':webgl', $webglRenderer);
$stmt->bindValue(':mob', $isMobile);
$stmt->bindValue(':susp', $isSuspicious);
$stmt->bindValue(':raw', json_encode($input));

$result = $stmt->execute();

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Dados registrados. Suspeito: ' . ($isSuspicious ? 'SIM' : 'NÃO')]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar no banco.']);
}

$dbClass->close();
