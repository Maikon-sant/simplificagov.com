<?php
/**
 * Teste Completo do Sistema SimplificaGov
 * 
 * Este arquivo testa todas as funcionalidades da API, incluindo:
 * - Autenticação
 * - Leis
 * - Favoritos
 * - Alertas (incluindo leitura)
 * - Preferências de Temas
 * - Parlamentares (incluindo analytics)
 * - Estatísticas
 * 
 * Uso: Acesse via navegador ou execute via CLI: php test_sistema_completo.php
 */

// Configurações
$baseUrl = 'https://api.simplificagov.com';
$testEmail = 'teste_' . time() . '@example.com';
$testSenha = 'senha123456';
$testNome = 'Usuário Teste';

// Cores para output CLI
$colors = [
    'reset' => "\033[0m",
    'green' => "\033[32m",
    'red' => "\033[31m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'cyan' => "\033[36m",
];

// Se executado via navegador, usar HTML
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Teste Sistema SimplificaGov</title>';
    echo '<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}';
    echo '.test{background:#fff;padding:15px;margin:10px 0;border-left:4px solid #007bff;border-radius:4px;}';
    echo '.success{background:#d4edda;border-color:#28a745;color:#155724;}';
    echo '.error{background:#f8d7da;border-color:#dc3545;color:#721c24;}';
    echo '.info{background:#d1ecf1;border-color:#17a2b8;color:#0c5460;}';
    echo 'h1{color:#333;}h2{color:#666;margin-top:30px;}pre{background:#f8f9fa;padding:10px;border-radius:4px;overflow-x:auto;}</style></head><body>';
    echo '<h1>🧪 Teste Completo do Sistema SimplificaGov</h1>';
}

// Função para fazer requisições HTTP
function makeRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init();
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $data ? json_encode($data) : null,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true),
        'raw' => $response
    ];
}

// Função para exibir resultado
function showResult($title, $result, $expectedCode = 200, $isCli = false) {
    global $colors;
    
    $success = $result['code'] === $expectedCode;
    $status = $success ? '✅ SUCESSO' : '❌ ERRO';
    $color = $success ? ($isCli ? $colors['green'] : 'success') : ($isCli ? $colors['red'] : 'error');
    
    if ($isCli) {
        echo $color . $status . $colors['reset'] . " - $title\n";
        echo "   Código HTTP: " . $result['code'] . " (esperado: $expectedCode)\n";
        if (!$success && isset($result['body']['message'])) {
            echo "   Mensagem: " . $result['body']['message'] . "\n";
        }
        if (isset($result['body']['data'])) {
            echo "   Dados retornados: " . (is_array($result['body']['data']) ? count($result['body']['data']) . ' itens' : 'Sim') . "\n";
        }
    } else {
        $class = $success ? 'success' : 'error';
        echo "<div class='test $class'>";
        echo "<strong>$status</strong> - $title<br>";
        echo "<small>Código HTTP: " . $result['code'] . " (esperado: $expectedCode)</small><br>";
        if (!$success && isset($result['body']['message'])) {
            echo "<small>Mensagem: " . htmlspecialchars($result['body']['message']) . "</small><br>";
        }
        if (isset($result['body']['data'])) {
            echo "<details><summary>Ver dados</summary><pre>" . htmlspecialchars(json_encode($result['body']['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre></details>";
        }
        echo "</div>";
    }
    
    return $success;
}

// Variáveis globais para armazenar dados dos testes
$token = null;
$cidadaoId = null;
$leiId = null;
$alertaId = null;
$parlamentarId = null;

echo $isCli ? "\n" . $colors['cyan'] . "=== INICIANDO TESTES ===\n" . $colors['reset'] : "<h2>🔐 1. Testes de Autenticação</h2>";

// ============================================
// 1. TESTES DE AUTENTICAÇÃO
// ============================================

echo $isCli ? "\n" . $colors['blue'] . "1. AUTENTICAÇÃO\n" . $colors['reset'] : "";

// 1.1 Registro
$result = makeRequest("$baseUrl/auth/register", 'POST', [
    'nome' => $testNome,
    'email' => $testEmail,
    'senha' => $testSenha,
    'contato' => $testEmail,
    'regiao' => 'Sudeste',
    'preferencia_midia' => 'texto'
]);
if (showResult('Registro de novo usuário', $result, 201, $isCli) && isset($result['body']['data']['token'])) {
    $token = $result['body']['data']['token'];
    $cidadaoId = $result['body']['data']['cidadao']['cidadao_id'] ?? null;
}

// 1.2 Login
$result = makeRequest("$baseUrl/auth/login", 'POST', [
    'email' => $testEmail,
    'senha' => $testSenha
]);
if (showResult('Login', $result, 200, $isCli) && isset($result['body']['data']['token'])) {
    $token = $result['body']['data']['token'];
    $cidadaoId = $result['body']['data']['cidadao']['cidadao_id'] ?? null;
}

// 1.3 Me (dados do usuário)
if ($token) {
    $result = makeRequest("$baseUrl/auth/me", 'GET', null, $token);
    showResult('Obter dados do usuário autenticado', $result, 200, $isCli);
}

// 1.4 Refresh Token
if ($token) {
    $result = makeRequest("$baseUrl/auth/refresh", 'POST', null, $token);
    if (showResult('Renovar token', $result, 200, $isCli) && isset($result['body']['data']['token'])) {
        $token = $result['body']['data']['token'];
    }
}

// ============================================
// 2. TESTES DE LEIS
// ============================================

echo $isCli ? "\n" . $colors['blue'] . "2. LEIS\n" . $colors['reset'] : "<h2>📜 2. Testes de Leis</h2>";

// 2.1 Listar leis
$result = makeRequest("$baseUrl/leis?limit=5&offset=0", 'GET', null, $token);
if (showResult('Listar leis', $result, 200, $isCli) && isset($result['body']['data'][0]['pl_id'])) {
    $leiId = $result['body']['data'][0]['pl_id'];
}

// 2.2 Busca avançada
$result = makeRequest("$baseUrl/leis?busca=educação&ordenar=relevancia_score&direcao=DESC&limit=5", 'GET');
showResult('Busca avançada de leis', $result, 200, $isCli);

// 2.3 Detalhes da lei
if ($leiId) {
    $result = makeRequest("$baseUrl/leis/$leiId", 'GET');
    showResult('Detalhes de uma lei', $result, 200, $isCli);
}

// ============================================
// 3. TESTES DE FAVORITOS
// ============================================

echo $isCli ? "\n" . $colors['blue'] . "3. FAVORITOS\n" . $colors['reset'] : "<h2>⭐ 3. Testes de Favoritos</h2>";

if ($token && $leiId) {
    // 3.1 Listar favoritos
    $result = makeRequest("$baseUrl/favoritos", 'GET', null, $token);
    showResult('Listar favoritos', $result, 200, $isCli);
    
    // 3.2 Adicionar favorito
    $result = makeRequest("$baseUrl/favoritos/$leiId", 'POST', null, $token);
    showResult('Adicionar favorito', $result, 201, $isCli);
    
    // 3.3 Verificar favorito
    $result = makeRequest("$baseUrl/favoritos/verificar/$leiId", 'GET', null, $token);
    showResult('Verificar se é favorito', $result, 200, $isCli);
    
    // 3.4 Remover favorito
    $result = makeRequest("$baseUrl/favoritos/$leiId", 'DELETE', null, $token);
    showResult('Remover favorito', $result, 200, $isCli);
}

// ============================================
// 4. TESTES DE ALERTAS
// ============================================

echo $isCli ? "\n" . $colors['blue'] . "4. ALERTAS\n" . $colors['reset'] : "<h2>🔔 4. Testes de Alertas</h2>";

if ($token && $leiId) {
    // 4.1 Listar alertas
    $result = makeRequest("$baseUrl/alertas", 'GET', null, $token);
    showResult('Listar alertas', $result, 200, $isCli);
    
    // 4.2 Criar alerta
    $result = makeRequest("$baseUrl/alertas", 'POST', [
        'tipo_alerta' => 'tramitacao',
        'titulo' => 'Teste de Alerta',
        'descricao' => 'Alerta criado para teste',
        'pl_id' => $leiId,
        'filtros_json' => ['tema' => 'Educação']
    ], $token);
    if (showResult('Criar alerta', $result, 201, $isCli) && isset($result['body']['data']['alerta_id'])) {
        $alertaId = $result['body']['data']['alerta_id'];
    }
    
    // 4.3 Detalhes do alerta
    if ($alertaId) {
        $result = makeRequest("$baseUrl/alertas/$alertaId", 'GET', null, $token);
        showResult('Detalhes do alerta', $result, 200, $isCli);
    }
    
    // 4.4 Marcar alerta como lido (NOVO)
    if ($alertaId) {
        $result = makeRequest("$baseUrl/alertas/$alertaId/read", 'POST', null, $token);
        showResult('Marcar alerta como lido', $result, 200, $isCli);
    }
    
    // 4.5 Ativar alerta
    if ($alertaId) {
        $result = makeRequest("$baseUrl/alertas/$alertaId/ativar", 'POST', null, $token);
        showResult('Ativar alerta', $result, 200, $isCli);
    }
    
    // 4.6 Desativar alerta
    if ($alertaId) {
        $result = makeRequest("$baseUrl/alertas/$alertaId/desativar", 'POST', null, $token);
        showResult('Desativar alerta', $result, 200, $isCli);
    }
    
    // 4.7 Atualizar alerta
    if ($alertaId) {
        $result = makeRequest("$baseUrl/alertas/$alertaId", 'PUT', [
            'titulo' => 'Alerta Atualizado',
            'descricao' => 'Descrição atualizada'
        ], $token);
        showResult('Atualizar alerta', $result, 200, $isCli);
    }
}

// ============================================
// 5. TESTES DE PREFERÊNCIAS DE TEMAS
// ============================================

echo $isCli ? "\n" . $colors['blue'] . "5. PREFERÊNCIAS DE TEMAS\n" . $colors['reset'] : "<h2>🎯 5. Testes de Preferências de Temas</h2>";

if ($token) {
    // 5.1 Listar preferências
    $result = makeRequest("$baseUrl/preferencias-temas", 'GET', null, $token);
    showResult('Listar preferências de temas', $result, 200, $isCli);
    
    // 5.2 Adicionar preferência
    $result = makeRequest("$baseUrl/preferencias-temas", 'POST', [
        'tema' => 'Educação',
        'nivel_interesse' => 'alto'
    ], $token);
    showResult('Adicionar preferência de tema', $result, 201, $isCli);
    
    // 5.3 Atualizar preferência
    $result = makeRequest("$baseUrl/preferencias-temas/Educação", 'PUT', [
        'nivel_interesse' => 'medio'
    ], $token);
    showResult('Atualizar preferência de tema', $result, 200, $isCli);
}

// ============================================
// 6. TESTES DE PARLAMENTARES
// ============================================

echo $isCli ? "\n" . $colors['blue'] . "6. PARLAMENTARES\n" . $colors['reset'] : "<h2>👥 6. Testes de Parlamentares</h2>";

// 6.1 Listar parlamentares
$result = makeRequest("$baseUrl/parlamentares?limit=5", 'GET');
if (showResult('Listar parlamentares', $result, 200, $isCli) && isset($result['body']['data'][0]['parlamentar_id'])) {
    $parlamentarId = $result['body']['data'][0]['parlamentar_id'];
}

// 6.2 Detalhes do parlamentar
if ($parlamentarId) {
    $result = makeRequest("$baseUrl/parlamentares/$parlamentarId", 'GET');
    showResult('Detalhes do parlamentar', $result, 200, $isCli);
}

// 6.3 Detalhes com analytics
if ($parlamentarId) {
    $result = makeRequest("$baseUrl/parlamentares/$parlamentarId?analytics=1", 'GET');
    showResult('Detalhes do parlamentar com analytics', $result, 200, $isCli);
}

// 6.4 Analytics do parlamentar (NOVO)
if ($parlamentarId) {
    $result = makeRequest("$baseUrl/parlamentares/$parlamentarId/analytics", 'GET');
    showResult('Analytics do parlamentar', $result, 200, $isCli);
}

// 6.5 Analytics com recálculo forçado
if ($parlamentarId) {
    $result = makeRequest("$baseUrl/parlamentares/$parlamentarId/analytics?recalcular=1", 'GET');
    showResult('Analytics com recálculo forçado', $result, 200, $isCli);
}

// 6.6 Criar parlamentar (requer autenticação)
if ($token) {
    $result = makeRequest("$baseUrl/parlamentares", 'POST', [
        'nome' => 'Deputado Teste',
        'nome_civil' => 'Teste da Silva',
        'partido' => 'PT',
        'uf' => 'SP',
        'casa' => 'Camara',
        'cargo' => 'Deputado Federal',
        'focus' => [
            'areas' => ['Educação', 'Saúde'],
            'prioridades' => ['Educação']
        ]
    ], $token);
    if (showResult('Criar parlamentar', $result, 201, $isCli) && isset($result['body']['data']['parlamentar_id'])) {
        $novoParlamentarId = $result['body']['data']['parlamentar_id'];
        
        // Associar lei ao parlamentar
        if ($novoParlamentarId && $leiId) {
            $result = makeRequest("$baseUrl/parlamentares/$novoParlamentarId/leis/$leiId", 'POST', [
                'tipo_relacao' => 'autor'
            ], $token);
            showResult('Associar lei ao parlamentar', $result, 200, $isCli);
        }
    }
}

// ============================================
// 7. TESTES DE ESTATÍSTICAS
// ============================================

echo $isCli ? "\n" . $colors['blue'] . "7. ESTATÍSTICAS\n" . $colors['reset'] : "<h2>📊 7. Testes de Estatísticas</h2>";

// 7.1 Estatísticas gerais
$result = makeRequest("$baseUrl/estatisticas", 'GET');
showResult('Estatísticas gerais', $result, 200, $isCli);

// 7.2 Estatísticas de leis
$result = makeRequest("$baseUrl/estatisticas/leis", 'GET');
showResult('Estatísticas de leis', $result, 200, $isCli);

// 7.3 Estatísticas de cidadãos
$result = makeRequest("$baseUrl/estatisticas/cidadaos", 'GET');
showResult('Estatísticas de cidadãos', $result, 200, $isCli);

// ============================================
// RESUMO FINAL
// ============================================

echo $isCli ? "\n" . $colors['cyan'] . "=== TESTES CONCLUÍDOS ===\n" . $colors['reset'] : "<h2>✅ Resumo dos Testes</h2>";

if ($isCli) {
    echo "\n" . $colors['yellow'] . "Nota: Alguns testes podem falhar se o banco de dados não estiver configurado corretamente.\n" . $colors['reset'];
    echo $colors['yellow'] . "Certifique-se de executar o script database_updates.sql antes de rodar os testes.\n" . $colors['reset'];
} else {
    echo "<div class='test info'>";
    echo "<strong>ℹ️ Informações:</strong><br>";
    echo "<ul>";
    echo "<li>Alguns testes podem falhar se o banco de dados não estiver configurado corretamente</li>";
    echo "<li>Certifique-se de executar o script database_updates.sql antes de rodar os testes</li>";
    echo "<li>O email de teste usado foi: <strong>$testEmail</strong></li>";
    if ($token) {
        echo "<li>Token gerado com sucesso: " . substr($token, 0, 20) . "...</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='test info'>";
    echo "<strong>📝 Endpoints Testados:</strong><br>";
    echo "<ul>";
    echo "<li>✅ Autenticação (register, login, me, refresh)</li>";
    echo "<li>✅ Leis (listar, buscar, detalhes)</li>";
    echo "<li>✅ Favoritos (listar, adicionar, verificar, remover)</li>";
    echo "<li>✅ Alertas (listar, criar, detalhes, marcar como lido, ativar, desativar, atualizar)</li>";
    echo "<li>✅ Preferências de Temas (listar, adicionar, atualizar)</li>";
    echo "<li>✅ Parlamentares (listar, detalhes, analytics, criar, associar lei)</li>";
    echo "<li>✅ Estatísticas (gerais, leis, cidadãos)</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "</body></html>";
}

