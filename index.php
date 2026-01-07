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
        .table-hover tbody tr:hover { background-color: #f1f1f1; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 10px; border-radius: 5px; max-height: 200px; overflow-y: auto; }
        .info-value { word-break: break-all; }
    </style>
</head>
<body>

<div class="container">
    <div class="text-center mb-5">
        <h1 class="display-5">Dados de Acesso e Navegador</h1>
        <p class="lead">Informações coletadas via PHP (Server-Side) e JavaScript (Client-Side)</p>
        <div class="badge bg-secondary">PHP Version: <?php echo phpversion(); ?></div>
    </div>

    <div class="row">
        <!-- Coluna da Esquerda: Dados Principais -->
        <div class="col-lg-6">
            
            <!-- Dados do Cliente via PHP -->
            <div class="card">
                <div class="card-header">📍 Identificação Básica (PHP)</div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <tbody>
                            <tr>
                                <th>Endereço IP</th>
                                <td class="info-value"><?php echo $_SERVER['REMOTE_ADDR'] ?? 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>Porta Remota</th>
                                <td class="info-value"><?php echo $_SERVER['REMOTE_PORT'] ?? 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>Método de Requisição</th>
                                <td class="info-value"><span class="badge bg-success"><?php echo $_SERVER['REQUEST_METHOD'] ?? 'N/A'; ?></span></td>
                            </tr>
                            <tr>
                                <th>Protocolo</th>
                                <td class="info-value"><?php echo $_SERVER['SERVER_PROTOCOL'] ?? 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>User Agent (Raw)</th>
                                <td class="info-value small"><?php echo $_SERVER['HTTP_USER_AGENT'] ?? 'N/A'; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

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

        </div>

        <!-- Coluna da Direita: Headers e Globais -->
        <div class="col-lg-6">
            
            <!-- Headers HTTP -->
            <div class="card">
                <div class="card-header">📨 Cabeçalhos da Requisição (Headers)</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Chave</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $headers = function_exists('getallheaders') ? getallheaders() : [];
                            // Fallback para servidores que não suportam getallheaders (ex: nginx com fpm em alguns casos, embora apache suporte)
                            if (empty($headers)) {
                                foreach ($_SERVER as $name => $value) {
                                    if (substr($name, 0, 5) == 'HTTP_') {
                                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                                    }
                                }
                            }
                            
                            foreach ($headers as $key => $value): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($key); ?></td>
                                    <td class="info-value small"><?php echo htmlspecialchars($value); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Dump Completo $_SERVER -->
            <div class="card">
                <div class="card-header">⚙️ Variável $_SERVER Completa</div>
                <div class="card-body">
                    <p class="card-text small text-muted">Use isso para ver variáveis de ambiente específicas do servidor.</p>
                    <pre><?php print_r($_SERVER); ?></pre>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
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
            { label: "Cores do Navegador", value: navigator.userAgentData ? "N/A" : navigator.vendor }, // Fallback simples
            { label: "Memória do Dispositivo (aprox.)", value: navigator.deviceMemory ? navigator.deviceMemory + " GB" : "Não disponível" },
            { label: "Cores do Sistema", value: window.matchMedia('(prefers-color-scheme: dark)').matches ? "Dark Mode" : "Light Mode" },
            { label: "Conexão (Network API)", value: navigator.connection ? navigator.connection.effectiveType : "Não suportado" }
        ];

        const tbody = document.querySelector("#js-data-table tbody");
        tbody.innerHTML = "";

        jsData.forEach(item => {
            const tr = document.createElement("tr");
            tr.innerHTML = `<th>${item.label}</th><td class="info-value">${item.value}</td>`;
            tbody.appendChild(tr);
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
