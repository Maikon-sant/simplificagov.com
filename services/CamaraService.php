<?php
// services/CamaraService.php

class CamaraService
{
    private static function requestJson(string $url): ?array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 20,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);

        if ($resp === false) {
            return null;
        }

        return json_decode($resp, true);
    }

    public static function buscarProposicao(string $tipo, string $numero, string $ano): ?array
    {
        $urlCamara = "https://dadosabertos.camara.leg.br/api/v2/proposicoes?siglaTipo={$tipo}&numero={$numero}&ano={$ano}";
        $camaraResp = self::requestJson($urlCamara);

        if (empty($camaraResp['dados'])) {
            return null;
        }

        $info     = $camaraResp['dados'][0];
        $idCamara = $info['id'];

        $det   = self::requestJson("https://dadosabertos.camara.leg.br/api/v2/proposicoes/{$idCamara}");
        $tram  = self::requestJson("https://dadosabertos.camara.leg.br/api/v2/proposicoes/{$idCamara}/tramitacoes");
        $votos = self::requestJson("https://dadosabertos.camara.leg.br/api/v2/proposicoes/{$idCamara}/votacoes");

        $origemSenado = false;
        if (!empty($det['dados']['origem']) && str_contains(strtolower($det['dados']['origem']), 'senado')) {
            $origemSenado = true;
        }

        return [
            'origem'         => 'Camara',
            'origem_senado'  => $origemSenado,
            'id'             => $det['dados']['id'] ?? null,
            'siglaTipo'      => $det['dados']['siglaTipo'] ?? null,
            'numero'         => $det['dados']['numero'] ?? null,
            'ano'            => $det['dados']['ano'] ?? null,
            'ementa'         => $det['dados']['ementa'] ?? null,
            'dataApresentacao' => $det['dados']['dataApresentacao'] ?? null,
            'status'         => $det['dados']['statusProposicao'] ?? [],
            'tramitacoes'    => $tram['dados'] ?? [],
            'votacoes'       => $votos['dados'] ?? [],
            'pdf'            => $det['dados']['urlInteiroTeor'] ?? null,
            'texto_original' => $det['dados']['ementa'] ?? null, // você pode trocar por inteiro teor extraído de PDF depois
        ];
    }
}
