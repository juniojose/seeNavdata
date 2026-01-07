<?php
require_once 'database.php';

$dbClass = new Database();
$db = $dbClass->getDb();

// Filtros de Data (Padrão: Últimos 30 dias)
$startDate = $_GET['start'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end'] ?? date('Y-m-d');

// Consulta Estatísticas
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_suspicious = 1 THEN 1 ELSE 0 END) as suspicious
    FROM visits 
    WHERE date(created_at) BETWEEN :start AND :end";

$stmt = $db->prepare($statsQuery);
$stmt->bindValue(':start', $startDate);
$stmt->bindValue(':end', $endDate);
$stats = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

// Consulta Lista de Acessos
$listQuery = "SELECT * FROM visits 
              WHERE date(created_at) BETWEEN :start AND :end 
              ORDER BY canvas_hash ASC, created_at DESC LIMIT 100";
$stmtList = $db->prepare($listQuery);
$stmtList->bindValue(':start', $startDate);
$stmtList->bindValue(':end', $endDate);
$results = $stmtList->execute();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - seeNavdata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .metric-card { border-left: 5px solid #0d6efd; }
        .metric-card.danger { border-left-color: #dc3545; }
        .suspicious-row { background-color: #fff3cd !important; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <span class="navbar-brand mb-0 h1">🛡️ seeNavdata Dashboard</span>
        <a href="index.php" class="btn btn-outline-light btn-sm">Ir para Aplicação</a>
    </div>
</nav>

<div class="container">
    <!-- Filtros -->
    <form class="row g-3 mb-4 align-items-end" method="GET">
        <div class="col-auto">
            <label class="form-label fw-bold">Data Inicial</label>
            <input type="date" name="start" class="form-control" value="<?php echo $startDate; ?>">
        </div>
        <div class="col-auto">
            <label class="form-label fw-bold">Data Final</label>
            <input type="date" name="end" class="form-control" value="<?php echo $endDate; ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    <!-- Cards de Métricas -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card metric-card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-muted">Total de Acessos</h5>
                    <h2 class="display-4 fw-bold"><?php echo $stats['total']; ?></h2>
                    <p class="card-text text-muted">No período selecionado</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card metric-card danger shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-danger">Suspeitas de Bot Farm 🤖</h5>
                    <h2 class="display-4 fw-bold text-danger"><?php echo $stats['suspicious']; ?></h2>
                    <p class="card-text text-muted">
                        Baseado em Heurística Avançada (Fuso, Bateria, Hardware, Automação)
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Registros -->
    <div class="card shadow-sm">
        <div class="card-header bg-white fw-bold">
            📋 Últimos 100 Acessos do Período
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Data/Hora</th>
                        <th>IP</th>
                        <th>País</th>
                        <th>Fuso (JS)</th>
                        <th>Status</th>
                        <th>Motivos</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $results->fetchArray(SQLITE3_ASSOC)): 
                        $isSusp = $row['is_suspicious'] == 1;
                        $statusBadge = $isSusp 
                            ? '<span class="badge bg-danger">Suspeito</span>' 
                            : '<span class="badge bg-success">Normal</span>';
                        $rowClass = $isSusp ? 'suspicious-row' : '';
                        
                        // Extrair motivos do JSON raw_data
                        $rawArr = json_decode($row['raw_data'], true);
                        $flags = $rawArr['suspicious_flags'] ?? [];
                        $motivosHtml = '';
                        foreach ($flags as $f) {
                            $motivosHtml .= '<div class="small text-danger" style="font-size: 0.75rem;">• '.htmlspecialchars($f).'</div>';
                        }

                        $safeRawData = htmlspecialchars($row['raw_data'], ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr class="<?php echo $rowClass; ?>">
                        <td><?php echo date('d/m H:i', strtotime($row['created_at'])); ?></td>
                        <td><?php echo $row['ip_address']; ?></td>
                        <td class="small"><?php echo $row['country_geo']; ?></td>
                        <td class="small">
                            <?php echo $row['timezone_js']; ?>
                        </td>
                        <td><?php echo $statusBadge; ?></td>
                        <td><?php echo $motivosHtml; ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary btn-view-raw" data-raw='<?php echo $safeRawData; ?>'>
                                👁️ Ver Tudo
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Visualização de Dados Formatados -->
<div class="modal fade" id="rawUpdateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">🔍 Detalhes da Extração (Raio-X)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <div id="formatted-display" class="row">
                    <!-- Conteúdo gerado dinamicamente aqui -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = new bootstrap.Modal(document.getElementById('rawUpdateModal'));
        const display = document.getElementById('formatted-display');

        function createCard(title, dataArray, colorClass = 'bg-primary') {
            let rows = dataArray.map(item => `
                <tr>
                    <th style="width: 40%; font-size: 0.9rem;">${item.label || item.key}</th>
                    <td class="text-break" style="font-size: 0.9rem;">${item.value}</td>
                </tr>
            `).join('');

            return `
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header ${colorClass} text-white fw-bold">${title}</div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <tbody>${rows}</tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }

        document.querySelectorAll('.btn-view-raw').forEach(btn => {
            btn.addEventListener('click', function() {
                try {
                    const raw = JSON.parse(this.getAttribute('data-raw'));
                    display.innerHTML = ''; // Limpar

                    // 1. Geolocalização
                    if (raw.geoData && raw.geoData.status === 'success') {
                        const geo = raw.geoData;
                        const geoArray = [
                            {label: 'País', value: `${geo.country} (${geo.countryCode})`},
                            {label: 'Região', value: geo.regionName},
                            {label: 'Cidade', value: geo.city},
                            {label: 'Provedor', value: geo.isp},
                            {label: 'Timezone (IP)', value: geo.timezone},
                            {label: 'Mobile', value: geo.mobile ? 'Sim' : 'Não'},
                            {label: 'Proxy/VPN', value: geo.proxy ? 'Sim' : 'Não'}
                        ];
                        display.innerHTML += createCard('🌍 Geolocalização', geoArray, 'bg-success');
                    }

                    // 2. Dados do Cliente (Separar Fingerprint de Dados Básicos)
                    if (raw.clientData) {
                        const fpLabels = [
                            "CPU Cores (Núcleos)", 
                            "Max Touch Points", 
                            "GPU Vendor (WebGL)", 
                            "GPU Renderer (WebGL)", 
                            "Canvas Hash (Assinatura)"
                        ];

                        const fpItems = [];
                        const basicItems = [];

                        raw.clientData.forEach(item => {
                            if (fpLabels.includes(item.label)) {
                                fpItems.push(item);
                            } else {
                                basicItems.push(item);
                            }
                        });

                        // Exibir Card Amarelo (Fingerprint)
                        if (fpItems.length > 0) {
                            display.innerHTML += createCard('🕵️ Fingerprinting Avançado', fpItems, 'bg-warning text-dark');
                        }

                        // Exibir Card Azul (Dados Básicos JS)
                        if (basicItems.length > 0) {
                            display.innerHTML += createCard('🖥️ Dados do Dispositivo (JavaScript)', basicItems, 'bg-primary');
                        }
                    }

                    // 3. Dados do Contexto PHP (Novo)
                    if (raw.serverContext) {
                        // Identificação Básica
                        if (raw.serverContext.basicInfo) {
                            const basicArray = Object.entries(raw.serverContext.basicInfo).map(([key, value]) => ({ label: key, value: value }));
                            display.innerHTML += createCard('📍 Identificação Básica (PHP)', basicArray, 'bg-info');
                        }
                        
                        // Headers
                        if (raw.serverContext.headers) {
                            const headersArray = Object.entries(raw.serverContext.headers).map(([key, value]) => ({ label: key, value: value }));
                            display.innerHTML += createCard('📨 Cabeçalhos da Requisição (Headers)', headersArray, 'bg-secondary');
                        }
                    }

                    modal.show();
                } catch (e) {
                    console.error(e);
                    alert('Erro ao processar dados.');
                }
            });
        });
    });
</script>

</body>
</html>
<?php $dbClass->close(); ?>
