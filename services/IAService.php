<?php
// services/IAService.php

class IAService
{
    public static function gerarResumoSimples(string $textoOriginal): string
    {
        $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
        $apiBase = defined('OPENAI_API_BASE') ? OPENAI_API_BASE : 'https://api.openai.com/v1';

        if (empty($apiKey)) {
            // Fallback para stub se não houver chave configurada
            return 'Resumo simples gerado pela IA (stub). Texto original truncado: ' . mb_substr($textoOriginal, 0, 200) . '...';
        }

        $prompt = "Traduza o seguinte texto jurídico para linguagem simples e acessível para cidadãos comuns. " .
                  "Mantenha o significado legal, mas use palavras do dia a dia. " .
                  "Seja claro e objetivo. Limite a resposta a no máximo 300 palavras.\n\n" .
                  "Texto jurídico:\n" . $textoOriginal;

        $ch = curl_init($apiBase . '/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Você é um tradutor especializado em simplificar textos jurídicos para cidadãos comuns, mantendo a precisão legal.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 500
            ]),
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Erro ao chamar OpenAI: " . $error);
            return 'Erro ao gerar resumo: ' . $error;
        }

        if ($httpCode !== 200) {
            error_log("OpenAI retornou código HTTP $httpCode: " . $response);
            return 'Erro ao gerar resumo. Código HTTP: ' . $httpCode;
        }

        $data = json_decode($response, true);
        if (isset($data['choices'][0]['message']['content'])) {
            return trim($data['choices'][0]['message']['content']);
        }

        error_log("Resposta inesperada da OpenAI: " . $response);
        return 'Erro ao processar resposta da OpenAI';
    }

    public static function gerarToolkitCompleto(string $textoOriginal, string $tituloOriginal = ''): array
    {
        $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
        $apiBase = defined('OPENAI_API_BASE') ? OPENAI_API_BASE : 'https://api.openai.com/v1';

        if (empty($apiKey)) {
            // Fallback para stub
            return [
                'titulo' => 'Sugestão de Título de Impacto',
                'resumo_curto' => mb_substr($textoOriginal, 0, 200) . '...',
                'card' => [
                    'cta' => 'Apoie esta mudança!',
                    'bullets' => ['Ponto 1', 'Ponto 2', 'Ponto 3'],
                ],
                'roteiro_video' => [
                    'gancho' => 'Você já foi lesado por uma taxa abusiva?',
                    'explicacao' => 'Em linguagem simples, esta proposta faz...',
                    'chamada_acao' => 'Compartilhe e apoie no portal.',
                ],
            ];
        }

        $prompt = "Com base no seguinte texto de projeto de lei, gere um toolkit completo de comunicação:\n\n" .
                  "Título original: " . ($tituloOriginal ?: 'Não informado') . "\n\n" .
                  "Texto: " . $textoOriginal . "\n\n" .
                  "Gere um JSON com a seguinte estrutura:\n" .
                  "{\n" .
                  "  \"titulo\": \"Título impactante e claro (máx 60 caracteres)\",\n" .
                  "  \"resumo_curto\": \"Resumo em 2-3 frases\",\n" .
                  "  \"card\": {\n" .
                  "    \"cta\": \"Chamada para ação (máx 30 caracteres)\",\n" .
                  "    \"bullets\": [\"Ponto principal 1\", \"Ponto principal 2\", \"Ponto principal 3\"]\n" .
                  "  },\n" .
                  "  \"roteiro_video\": {\n" .
                  "    \"gancho\": \"Pergunta ou frase de impacto para começar (máx 80 caracteres)\",\n" .
                  "    \"explicacao\": \"Explicação simples em 2-3 parágrafos\",\n" .
                  "    \"chamada_acao\": \"Chamada final para ação (máx 50 caracteres)\"\n" .
                  "  }\n" .
                  "}\n\n" .
                  "Retorne APENAS o JSON, sem markdown, sem explicações adicionais.";

        $ch = curl_init($apiBase . '/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Você é um especialista em comunicação pública e engajamento cívico. Gere sempre JSON válido.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.8,
                'max_tokens' => 1000
            ]),
            CURLOPT_TIMEOUT => 45
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            error_log("Erro ao gerar toolkit: " . ($error ?: "HTTP $httpCode"));
            // Retorna toolkit padrão em caso de erro
            return [
                'titulo' => $tituloOriginal ?: 'Projeto de Lei',
                'resumo_curto' => mb_substr($textoOriginal, 0, 200) . '...',
                'card' => [
                    'cta' => 'Apoie esta mudança!',
                    'bullets' => ['Ponto 1', 'Ponto 2', 'Ponto 3'],
                ],
                'roteiro_video' => [
                    'gancho' => 'Você conhece este projeto de lei?',
                    'explicacao' => 'Este projeto de lei trata de...',
                    'chamada_acao' => 'Compartilhe e apoie no portal.',
                ],
            ];
        }

        $data = json_decode($response, true);
        if (isset($data['choices'][0]['message']['content'])) {
            $content = trim($data['choices'][0]['message']['content']);
            // Remove markdown code blocks se existirem
            $content = preg_replace('/```json\s*/', '', $content);
            $content = preg_replace('/```\s*/', '', $content);
            $toolkit = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($toolkit)) {
                return $toolkit;
            }
        }

        // Fallback em caso de erro no parsing
        error_log("Erro ao parsear toolkit da OpenAI");
        return [
            'titulo' => $tituloOriginal ?: 'Projeto de Lei',
            'resumo_curto' => mb_substr($textoOriginal, 0, 200) . '...',
            'card' => ['cta' => 'Apoie!', 'bullets' => []],
            'roteiro_video' => ['gancho' => '', 'explicacao' => '', 'chamada_acao' => ''],
        ];
    }
}
