<?php
// tests/verify_whatsapp.php

// Mock environment
define('OPENAI_API_KEY', ''); // Trigger fallback
define('BASE_URL', 'http://localhost:8000');

require_once __DIR__ . '/../services/IAService.php';
require_once __DIR__ . '/../controllers/WhatsAppController.php';

// Mock POST data
$_POST['Body'] = "Art. 1º Fica instituído o Programa...";
$_POST['MediaUrl0'] = null;

// Capture output
ob_start();
$controller = new WhatsAppController();
$controller->webhook();
$output = ob_get_clean();

echo "Output:\n" . $output . "\n";

// Verify XML structure
if (strpos($output, '<Response>') !== false && strpos($output, '<Message>') !== false) {
    echo "\nSUCCESS: Valid TwiML response generated.\n";
} else {
    echo "\nFAILURE: Invalid TwiML response.\n";
    exit(1);
}

// Verify fallback content
if (strpos($output, 'Simplifica.gov') !== false || strpos($output, 'Simplifica ponto gov') !== false) {
    echo "SUCCESS: Fallback content found.\n";
} else {
    echo "FAILURE: Fallback content not found.\n";
    exit(1);
}
