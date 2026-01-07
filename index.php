<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Dados do Usuário e Navegador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-top: 20px; }
        .card { margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: none; }
        .card-header { background-color: #0d6efd; color: white; font-weight: bold; }
        .card-header.geo { background-color: #198754; } /* Cor verde para Geo */
        .table-hover tbody tr:hover { background-color: #f1f1f1; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 10px; border-radius: 5px; max-height: 200px; overflow-y: auto; }
        .info-value { word-break: break-all; }
    </style>
</head>
<body>

<?php
// --- Lógica de Geolocalização (Server-Side) ---

function getUserIP() {
    // Prioriza headers de proxy confiáveis para pegar o IP real
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) return $_SERVER['HTTP_X_REAL_IP'];
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Pode retornar lista de IPs, pega o primeiro
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ipList[0]);
    }
    return $_SERVER['REMOTE_ADDR'];
}

$userIP = getUserIP();
// Timeout curto para não travar o carregamento da página se a API demorar
$context = stream_context_create(['http' => ['timeout' => 3]]); 
$geoApiUrl = "http://ip-api.com/json/{$userIP}?lang=pt-BR&fields=status,message,country,countryCode,regionName,city,zip,lat,lon,timezone,isp,org,as,query,mobile,proxy,hosting";
$geoJson = @file_get_contents($geoApiUrl, false, $context);
$geoData = $geoJson ? json_decode($geoJson, true) : null;
?>

<div class="container">
    <div class="text-center mb-5">
        <h1 class="display-5">Dados de Acesso e Navegador</h1>
        <p class="lead">Informações coletadas via PHP (Server-Side), API Externa e JavaScript (Client-Side)</p>
        <div class="mb-3">
            <div class="badge bg-secondary">PHP Version: <?php echo phpversion(); ?></div>
            <div class="badge bg-success">IP Detectado: <?php echo $userIP; ?></div>
        </div>
        <button id="btn-send-email" class="btn btn-primary btn-lg">
            📧 Enviar Relatório por E-mail
        </button>
        <div id="email-status" class="mt-2"></div>
    </div>

    <div class="row">
        <!-- Coluna da Esquerda -->
        <div class="col-lg-6">
            
            <!-- Dados de Geolocalização -->
            <div class="card">
                <div class="card-header geo">🌍 Geolocalização (IP-API)</div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <tbody>
                            <?php if ($geoData && $geoData['status'] == 'success'): ?>
                                <tr><th>País</th><td class="info-value"><?php echo $geoData['country'] . " (" . $geoData['countryCode'] . ")"; ?></td></tr>
                                <tr><th>Região/Estado</th><td class="info-value"><?php echo $geoData['regionName']; ?></td></tr>
                                <tr><th>Cidade</th><td class="info-value"><?php echo $geoData['city']; ?></td></tr>
                                <tr><th>Fuso Horário (ISP)</th><td class="info-value"><?php echo $geoData['timezone']; ?></td></tr>
                                <tr><th>Provedor (ISP)</th><td class="info-value"><?php echo $geoData['isp']; ?></td></tr>
                                <tr><th>Organização</th><td class="info-value"><?php echo $geoData['org']; ?></td></tr>
                                <tr><th>Latitude/Longitude</th><td class="info-value"><?php echo $geoData['lat'] . ", " . $geoData['lon']; ?></td></tr>
                                <tr><th>Conexão Móvel?</th><td class="info-value"><?php echo $geoData['mobile'] ? 'Sim' : 'Não'; ?></td></tr>
                                <tr><th>Proxy/VPN?</th><td class="info-value"><?php echo $geoData['proxy'] ? 'Sim' : 'Não'; ?></td></tr>
                            <?php else: ?>
                                <tr><td colspan="2" class="text-center text-danger">Não foi possível obter dados de geolocalização.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Dados do Cliente via PHP -->
            <div class="card">
                <div class="card-header">📍 Identificação Básica (PHP)</div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <tbody>
                            <tr><th>Endereço IP (Remoto)</th><td class="info-value"><?php echo $_SERVER['REMOTE_ADDR'] ?? 'N/A'; ?></td></tr>
                            <tr><th>Porta Remota</th><td class="info-value"><?php echo $_SERVER['REMOTE_PORT'] ?? 'N/A'; ?></td></tr>
                            <tr><th>Método de Requisição</th><td class="info-value"><span class="badge bg-success"><?php echo $_SERVER['REQUEST_METHOD'] ?? 'N/A'; ?></span></td></tr>
                            <tr><th>Protocolo</th><td class="info-value"><?php echo $_SERVER['SERVER_PROTOCOL'] ?? 'N/A'; ?></td></tr>
                            <tr><th>User Agent</th><td class="info-value small"><?php echo $_SERVER['HTTP_USER_AGENT'] ?? 'N/A'; ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Fingerprinting Avançado -->
            <div class="card">
                <div class="card-header bg-warning text-dark">🕵️ Fingerprinting Avançado</div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0" id="fp-data-table">
                        <tbody>
                            <tr><td colspan="2" class="text-center p-3"><div class="spinner-border text-warning" role="status"></div><br>Gerando assinatura digital...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Coluna da Direita -->
        <div class="col-lg-6">
            
             <!-- Dados do Cliente via JS -->
             <div class="card">
                <div class="card-header">🖥️ Dados do Dispositivo (JavaScript)</div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0" id="js-data-table">
                        <tbody>
                            <tr><td colspan="2" class="text-center p-3"><div class="spinner-border text-primary" role="status"></div><br>Carregando dados do navegador...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Headers HTTP -->
            <div class="card">
                <div class="card-header">📨 Cabeçalhos da Requisição (Headers)</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead><tr><th>Chave</th><th>Valor</th></tr></thead>
                        <tbody>
                            <?php
                            $headers = function_exists('getallheaders') ? getallheaders() : [];
                            if (empty($headers)) {
                                foreach ($_SERVER as $name => $value) {
                                    if (substr($name, 0, 5) == 'HTTP_') {
                                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                                    }
                                }
                            }
                            foreach ($headers as $key => $value): ?>
                                <tr><td><?php echo htmlspecialchars($key); ?></td><td class="info-value small"><?php echo htmlspecialchars($value); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    
    <!-- Dump Completo $_SERVER (Oculto por padrão, expansível) -->
    <div class="card mt-3">
        <div class="card-header bg-dark text-white" style="cursor: pointer;" onclick="document.getElementById('server-dump').classList.toggle('d-none')">
            ⚙️ Variável $_SERVER Completa (Clique para expandir)
        </div>
        <div class="card-body d-none" id="server-dump">
            <pre><?php print_r($_SERVER); ?></pre>
        </div>
    </div>

</div>

<script>
    // Funções de Fingerprinting
    function getCanvasFingerprint() {
        try {
            var canvas = document.createElement('canvas');
            var ctx = canvas.getContext('2d');
            var txt = 'seeNavdata-fingerprint-v1';
            ctx.textBaseline = "top";
            ctx.font = "14px 'Arial'";
            ctx.textBaseline = "alphabetic";
            ctx.fillStyle = "#f60";
            ctx.fillRect(125,1,62,20);
            ctx.fillStyle = "#069";
            ctx.fillText(txt, 2, 15);
            ctx.fillStyle = "rgba(102, 204, 0, 0.7)";
            ctx.fillText(txt, 4, 17);
            
            // Gerar hash simples da string base64 (CRC like)
            var str = canvas.toDataURL();
            var hash = 0;
            if (str.length === 0) return 'N/A';
            for (var i = 0; i < str.length; i++) {
                var char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash;
            }
            return hash.toString(16); // Hex
        } catch(e) { return "Erro: " + e.message; }
    }

    function getWebGLInfo() {
        try {
            var canvas = document.createElement('canvas');
            var gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            if (!gl) return { vendor: 'N/A', renderer: 'N/A' };
            var debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
            if (!debugInfo) return { vendor: 'N/A', renderer: 'N/A' };
            return {
                vendor: gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL),
                renderer: gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL)
            };
        } catch(e) { return { vendor: 'Erro', renderer: e.message }; }
    }

    document.addEventListener("DOMContentLoaded", function() {
        // Dados do Navegador
        const jsData = [
            { label: "Resolução da Tela", value: window.screen.width + " x " + window.screen.height },
            { label: "Área Disponível", value: window.screen.availWidth + " x " + window.screen.availHeight },
            { label: "Profundidade de Cor", value: window.screen.colorDepth + " bits" },
            { label: "Pixel Ratio", value: window.devicePixelRatio },
            { label: "Fuso Horário", value: Intl.DateTimeFormat().resolvedOptions().timeZone },
            { label: "Idioma Preferido", value: navigator.language },
            { label: "Idiomas Suportados", value: navigator.languages ? navigator.languages.join(", ") : "N/A" },
            { label: "Plataforma", value: navigator.platform },
            { label: "Cookies Ativados", value: navigator.cookieEnabled ? "Sim" : "Não" },
            { label: "Memória do Dispositivo (aprox.)", value: navigator.deviceMemory ? navigator.deviceMemory + " GB" : "Não disponível" },
            { label: "Cores do Sistema", value: window.matchMedia('(prefers-color-scheme: dark)').matches ? "Dark Mode" : "Light Mode" },
            { label: "Conexão (Network API)", value: navigator.connection ? navigator.connection.effectiveType : "Não suportado" }
        ];

        // Dados de Fingerprinting
        const webGL = getWebGLInfo();
        const fpData = [
            { label: "CPU Cores (Núcleos)", value: navigator.hardwareConcurrency || 'N/A' },
            { label: "Max Touch Points", value: navigator.maxTouchPoints || 0 },
            { label: "GPU Vendor (WebGL)", value: webGL.vendor },
            { label: "GPU Renderer (WebGL)", value: webGL.renderer },
            { label: "Canvas Hash (Assinatura)", value: getCanvasFingerprint() }
        ];

        // Renderizar Tabela JS (Básico)
        const tbodyJS = document.querySelector("#js-data-table tbody");
        tbodyJS.innerHTML = "";
        jsData.forEach(item => {
            const tr = document.createElement("tr");
            tr.innerHTML = `<th>${item.label}</th><td class="info-value">${item.value}</td>`;
            tbodyJS.appendChild(tr);
        });

        // Renderizar Tabela Fingerprint
        const tbodyFP = document.querySelector("#fp-data-table tbody");
        tbodyFP.innerHTML = "";
        fpData.forEach(item => {
            const tr = document.createElement("tr");
            tr.innerHTML = `<th>${item.label}</th><td class="info-value fw-bold text-dark">${item.value}</td>`;
            tbodyFP.appendChild(tr);
        });

        // Passar dados PHP (Geo) para JS
        const serverGeoData = <?php echo json_encode($geoData); ?>;

        // --- AUTOSAVE: Salvar dados no banco automaticamente ---
        const allClientDataForSave = jsData.concat(fpData);
        fetch('save_data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                clientData: allClientDataForSave, 
                geoData: serverGeoData 
            })
        }).then(res => console.log("Dados de acesso registrados.")).catch(err => console.error("Erro no autosave:", err));
        // -------------------------------------------------------

        // Lógica de Envio de E-mail
        const btnSend = document.getElementById('btn-send-email');
        const statusDiv = document.getElementById('email-status');

        btnSend.addEventListener('click', function() {
            btnSend.disabled = true;
            btnSend.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...';
            statusDiv.innerHTML = '';

            // Combinar todos os dados de cliente
            const allClientData = jsData.concat(fpData);

            fetch('send_mail.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    clientData: allClientData, // Envia JS Básico + Fingerprint juntos
                    geoData: serverGeoData 
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusDiv.innerHTML = `<div class="alert alert-success d-inline-block">${data.message}</div>`;
                } else {
                    statusDiv.innerHTML = `<div class="alert alert-danger d-inline-block">${data.message}</div>`;
                }
            })
            .catch(error => {
                statusDiv.innerHTML = `<div class="alert alert-danger d-inline-block">Erro de conexão: ${error}</div>`;
            })
            .finally(() => {
                btnSend.disabled = false;
                btnSend.innerHTML = '📧 Enviar Relatório por E-mail';
            });
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>