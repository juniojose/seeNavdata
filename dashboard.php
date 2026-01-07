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
              ORDER BY created_at DESC LIMIT 100";
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
                        Critério: IP Brasil + Timezone China (Asia/Shanghai)
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
                        <th>Plataforma</th>
                        <th>Canvas Hash</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $results->fetchArray(SQLITE3_ASSOC)): 
                        $isSusp = $row['is_suspicious'] == 1;
                        $statusBadge = $isSusp 
                            ? '<span class="badge bg-danger">Suspeito</span>' 
                            : '<span class="badge bg-success">Normal</span>';
                        $rowClass = $isSusp ? 'suspicious-row' : '';
                    ?>
                    <tr class="<?php echo $rowClass; ?>">
                        <td><?php echo date('d/m H:i', strtotime($row['created_at'])); ?></td>
                        <td><?php echo $row['ip_address']; ?></td>
                        <td><?php echo $row['country_geo']; ?></td>
                        <td>
                            <?php echo $row['timezone_js']; ?>
                            <?php if($row['timezone_js'] != $row['timezone_geo'] && $row['timezone_geo'] != 'N/A') echo '<br><small class="text-muted">IP: '.$row['timezone_geo'].'</small>'; ?>
                        </td>
                        <td class="small"><?php echo $row['platform']; ?></td>
                        <td class="small text-monospace"><?php echo substr($row['canvas_hash'], 0, 10); ?>...</td>
                        <td><?php echo $statusBadge; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
<?php $dbClass->close(); ?>
