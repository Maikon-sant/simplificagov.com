<?php
// services/IAService.php

class IAService
{
    public static function gerarTraducaoCompleta(string $textoOriginal, string $fonteUrl = ''): array
    {
        $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
        $apiBase = defined('OPENAI_API_BASE') ? OPENAI_API_BASE : 'https://api.openai.com/v1';

        if (empty($apiKey)) {
            // Fallback para stub
            return [
                'titulo_simples' => 'Resumo Simulado',
                'roteiro_audio_whatsapp' => 'Olá! Aqui é o Simplifica ponto gov. O texto original foi truncado: ' . mb_substr($textoOriginal, 0, 100) . '... Basicamente, isso impacta seu bolso e seus direitos. Compartilhe para saber mais!',
                'cards_visuais' => [
                    'impacto_bolso' => 'Impacto no bolso simulado',
                    'impacto_direitos' => 'Novo direito simulado',
                    'status_projeto' => 'Em análise'
                ],
                'auditoria_ia_responsavel' => [
                    'nota_complexidade_original' => 50,
                    'fonte_citada' => 'Simulação sem chave de API configurada.',
                    'alerta_vies' => null
                ],
                'tags_mapa_afetos' => ['Simulação']
            ];
        }

        $prompt = <<<EOT
ROLE & IDENTIDADE
Você é o "Simplifica.gov", uma Inteligência Artificial especialista em Democracia, Linguagem Simples (Plain Language/Legal Design) e Direito Constitucional Brasileiro. 

Sua persona é inspirada em um "vizinho bem informado e confiável": alguém que explica coisas difíceis de forma calma, acolhedora e sem usar palavras complicadas, mas sem perder a precisão dos fatos.

MISSÃO CRÍTICA
Sua tarefa é receber textos legislativos complexos (Projetos de Lei, Decretos, PECs) e traduzi-los para a realidade da "Dona Maria" — uma persona que representa a cidadã brasileira média, trabalhadora, com pouco tempo e que pode ter baixa escolaridade ou dificuldade com leitura.

CONTEXTO LEGAL (A "ALAVANCA" DE VIABILIDADE)
Você opera em conformidade direta com a **Política Nacional de Linguagem Simples**. Seu objetivo não é apenas resumir, mas garantir o direito do cidadão de entender o que o Estado está decidindo.

DIRETRIZES DE IA RESPONSÁVEL (Obrigatoriedade do Hackathon)
1. **Neutralidade Radical:** Você é um intérprete, não um opinador. Apresente os fatos. Se um projeto tem controvérsia, explique: "Quem apoia diz X, quem critica diz Y". Jamais tome partido.
2. **Aterramento (Grounding):** Responda APENAS com base no texto fornecido. Se a informação não estiver no texto, diga que não sabe. Não invente (Alucinação Zero).
3. **Caixa Aberta (Transparência):** Toda afirmação de impacto deve ser rastreável. Você deve indicar em qual artigo ou parágrafo encontrou aquela informação.
4. **Mapeamento de Afetos:** Analise o texto buscando conexões emocionais e pragmáticas: "Isso muda o preço da comida?", "Isso muda a regra da aposentadoria?", "Isso afeta o transporte no bairro?".

REGRAS DE LINGUAGEM (TONE & VOICE)
- **Nível de Leitura:** 5ª série do ensino fundamental.
- **Vocabulário:** Substitua "concessão de benefício pecuniário" por "pagamento em dinheiro". Substitua "trâmite em caráter de urgência" por "votação rápida".
- **Estrutura:** Frases curtas. Voz ativa ("O governo pagará" em vez de "Será pago pelo governo").
- **Empatia:** Use "Você", "Seu bairro", "Seu bolso".

FORMATO DE SAÍDA (JSON OBRIGATÓRIO)
Você deve analisar o input e gerar SEMPRE um JSON estruturado com os seguintes campos para o frontend processar:

{
  "titulo_simples": "Uma frase curta e chamativa sobre o tema (ex: 'Mudança no Preço do Pão').",
  
  "roteiro_audio_whatsapp": "Texto conversacional, pronto para ser lido por uma IA (TTS). Deve ter saudação, explicação do impacto direto na vida da pessoa e despedida. Máximo 40 segundos de fala. Use pontuação para dar ritmo de fala natural.",
  
  "cards_visuais": {
    "impacto_bolso": "Frase curta sobre custos/impostos (ou 'Sem impacto financeiro direto').",
    "impacto_direitos": "Frase curta sobre o que a pessoa ganha ou perde de direito.",
    "status_projeto": "Em votação / Aprovado / Em debate."
  },

  "auditoria_ia_responsavel": {
    "nota_complexidade_original": (Inteiro de 0 a 100, onde 100 é texto incompreensível/juridiquês extremo),
    "fonte_citada": "Ex: 'Baseado no Artigo 2º, parágrafo único do texto enviado'.",
    "alerta_vies": "Se o texto original excluir algum grupo minoritário, aponte aqui de forma técnica. Caso contrário, null."
  },

  "tags_mapa_afetos": ["Lista de tags para notificação (ex: Saúde, Idosos, Transporte, Educação)"]
}

INPUT DO USUÁRIO
Abaixo está o texto legislativo ou resumo oficial que você deve processar:

TEXTO ORIGINAL: $textoOriginal
FONTE: $fonteUrl
EOT;

        $ch = curl_init($apiBase . '/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'gpt-4o', // Solicitado no prompt, mas pode ser gpt-3.5-turbo se 4o não estiver disponível/configurado
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Você é o Simplifica.gov, especialista em Democracia e Linguagem Simples.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0,
                'max_tokens' => 1000
            ]),
            CURLOPT_TIMEOUT => 60
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            error_log("Erro ao gerar tradução completa: " . ($error ?: "HTTP $httpCode"));
            return [
                'titulo_simples' => 'Erro na IA',
                'roteiro_audio_whatsapp' => 'Desculpe, tive um problema técnico. Tente novamente.',
                'cards_visuais' => [],
                'auditoria_ia_responsavel' => [],
                'tags_mapa_afetos' => []
            ];
        }

        $data = json_decode($response, true);
        if (isset($data['choices'][0]['message']['content'])) {
            $content = trim($data['choices'][0]['message']['content']);
            // Limpeza de markdown
            $content = preg_replace('/```json\s*/', '', $content);
            $content = preg_replace('/```\s*/', '', $content);

            $json = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                return $json;
            }
        }

        error_log("Erro ao parsear resposta da OpenAI: " . $response);
        return [
            'titulo_simples' => 'Erro de Formato',
            'roteiro_audio_whatsapp' => 'Não consegui entender a resposta da IA.',
            'cards_visuais' => [],
            'auditoria_ia_responsavel' => [],
            'tags_mapa_afetos' => []
        ];
    }

    public static function gerarAudioExplicativo(string $textoScript): string
    {
        $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
        $apiBase = defined('OPENAI_API_BASE') ? OPENAI_API_BASE : 'https://api.openai.com/v1';

        if (empty($apiKey)) {
            return 'erro_audio_stub.mp3';
        }

        $filename = 'audio_' . uniqid() . '.mp3';
        $filepath = __DIR__ . '/../static/' . $filename;

        // Garantir que diretório static existe
        if (!is_dir(__DIR__ . '/../static')) {
            mkdir(__DIR__ . '/../static', 0755, true);
        }

        $ch = curl_init($apiBase . '/audio/speech');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'tts-1',
                'input' => $textoScript,
                'voice' => 'nova'
            ]),
            CURLOPT_TIMEOUT => 60
        ]);

        $audioData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            error_log("Erro ao gerar áudio TTS: " . ($error ?: "HTTP $httpCode"));
            return 'erro_audio.mp3';
        }

        file_put_contents($filepath, $audioData);
        return $filename;
    }

    // Mantendo compatibilidade com LeiController (que será atualizado ou já foi para usar gerarTraducaoCompleta)
    // Mas note que a estrutura de retorno mudou novamente, então LeiController precisa ser ajustado se ainda estiver usando a estrutura anterior.
    // Como o user pediu para adaptar o código Python, assumo que o foco agora é o WhatsApp, mas LeiController pode quebrar se não ajustarmos.
    // Vou ajustar LeiController em seguida.
    public static function gerarResumoSimples(string $textoOriginal): string
    {
        $resultado = self::gerarTraducaoCompleta($textoOriginal);
        return $resultado['roteiro_audio_whatsapp'] ?? 'Resumo indisponível.';
    }

    public static function gerarToolkitCompleto(string $textoOriginal, string $tituloOriginal = ''): array
    {
        return self::gerarTraducaoCompleta($textoOriginal);
    }
}
