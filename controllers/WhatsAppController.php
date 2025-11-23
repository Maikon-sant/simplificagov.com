<?php
// controllers/WhatsAppController.php

class WhatsAppController
{
    public function webhook(): void
    {
        // 1. Receber input do Twilio
        $body = $_POST['Body'] ?? '';
        $mediaUrl = $_POST['MediaUrl0'] ?? null;

        // Log simples (em produção usar logger adequado)
        error_log("WhatsApp Msg Recebida: " . $body);

        // Inicializa resposta TwiML
        header('Content-Type: application/xml');

        if (empty($body) && empty($mediaUrl)) {
            echo $this->gerarTwiML("Olá! Envie um texto de lei ou link para eu simplificar.");
            return;
        }

        try {
            // 2. Chama o Agente de IA
            // Se for link ou texto, a IA processa igual (por enquanto assumimos texto direto ou link no corpo)
            $analise = IAService::gerarTraducaoCompleta($body, "WhatsApp Input");

            if (isset($analise['titulo_simples']) && $analise['titulo_simples'] === 'Erro na IA') {
                echo $this->gerarTwiML("Desculpe, não consegui ler esse projeto de lei. Tente enviar o texto novamente.");
                return;
            }

            // 3. Gera o Áudio Explicativo (TTS)
            $nomeArquivoAudio = IAService::gerarAudioExplicativo($analise['roteiro_audio_whatsapp']);

            // URL Base (definida no config ou .env)
            $baseUrl = defined('BASE_URL') ? BASE_URL : 'https://simplificagov.com';
            $urlAudio = "$baseUrl/static/$nomeArquivoAudio";

            // 4. Monta a Resposta Visual
            $respostaVisual = "📢 *" . $analise['titulo_simples'] . "*\n\n";

            if (!empty($analise['cards_visuais']['impacto_bolso'])) {
                $respostaVisual .= "💰 *No seu Bolso:* " . $analise['cards_visuais']['impacto_bolso'] . "\n\n";
            }

            if (!empty($analise['cards_visuais']['impacto_direitos'])) {
                $respostaVisual .= "⚖️ *Seus Direitos:*\n" . $analise['cards_visuais']['impacto_direitos'] . "\n\n";
            }

            $respostaVisual .= "🔍 *Auditoria Voz da Lei:*\n";
            $respostaVisual .= "Legibilidade Original: " . ($analise['auditoria_ia_responsavel']['nota_complexidade_original'] ?? 'N/A') . "/100\n";
            $respostaVisual .= "Fonte: " . ($analise['auditoria_ia_responsavel']['fonte_citada'] ?? 'N/A');

            // 5. Retorna XML com Texto e Áudio
            echo $this->gerarTwiML($respostaVisual, $urlAudio);

        } catch (Throwable $e) {
            error_log("Erro no WhatsApp Webhook: " . $e->getMessage());
            echo $this->gerarTwiML("Ocorreu um erro interno na Voz da Lei. Nossa equipe técnica já foi acionada.");
        }
    }

    private function gerarTwiML(string $mensagem, ?string $mediaUrl = null): string
    {
        $xml = new SimpleXMLElement('<Response/>');
        $msg = $xml->addChild('Message');
        $msg->addChild('Body', $mensagem);

        if ($mediaUrl) {
            $msg->addChild('Media', $mediaUrl);
        }

        return $xml->asXML();
    }
}
