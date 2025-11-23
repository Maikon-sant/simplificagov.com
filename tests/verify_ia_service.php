<?php
// tests/verify_ia_service.php

require_once __DIR__ . '/../services/IAService.php';

// Mock constants if not defined
if (!defined('OPENAI_API_KEY')) {
    define('OPENAI_API_KEY', ''); // Empty key to trigger fallback
}
if (!defined('OPENAI_API_BASE')) {
    define('OPENAI_API_BASE', 'https://api.openai.com/v1');
}

echo "Testing IAService::gerarTraducaoCompleta...\n";

$texto = "Art. 1º Fica instituído o Programa Nacional de Simplificação.";
$resultado = IAService::gerarTraducaoCompleta($texto, 'http://example.com');

echo "Result:\n";
print_r($resultado);

$expectedKeys = ['resumo_audio', 'pontos_impacto', 'score_clareza_original', 'auditoria'];
$missingKeys = [];

foreach ($expectedKeys as $key) {
    if (!array_key_exists($key, $resultado)) {
        $missingKeys[] = $key;
    }
}

if (empty($missingKeys)) {
    echo "\nSUCCESS: All expected keys are present.\n";
} else {
    echo "\nFAILURE: Missing keys: " . implode(', ', $missingKeys) . "\n";
    exit(1);
}
