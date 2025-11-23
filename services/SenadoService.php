<?php
// services/SenadoService.php

class SenadoService
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

        // APIs do Senado retornam XML às vezes; aqui assumimos JSON;
        return json_decode($resp, true);
    }

    public static function buscarMateria(string $tipo, string $numero, string $ano): ?array
    {
        // Ordem de siglas para tentar
        $ordemSiglas = ($tipo === 'PL')
            ? ['PLC', 'PL', 'PLS']
            : [$tipo, 'PLC', 'PL', 'PLS'];

        $materia        = null;
        $tipoEncontrado = null;

        foreach ($ordemSiglas as $sigla) {
            $url  = "https://legis.senado.leg.br/dadosabertos/materia/pesquisa/lista?sigla={$sigla}&numero={$numero}&ano={$ano}";
            $resp = self::requestJson($url);

            if (!empty($resp['PesquisaBasicaMateria']['Materias']['Materia'])) {
                $materia        = $resp['PesquisaBasicaMateria']['Materias']['Materia'];
                $tipoEncontrado = $sigla;
                break;
            }
        }

        if (!$materia) {
            return null;
        }

        $cod    = $materia['CodigoMateria'];
        $detSen = self::requestJson("https://legis.senado.leg.br/dadosabertos/materia/{$cod}");
        $tram   = self::requestJson("https://legis.senado.leg.br/dadosabertos/materia/{$cod}/tramitacoes");
        $votos  = self::requestJson("https://legis.senado.leg.br/dadosabertos/materia/{$cod}/votacoes");

        return [
            'origem'         => 'Senado',
            'tipoEncontrado' => $tipoEncontrado,
            'codigoMateria'  => $cod,
            'descricao'      => $materia['Ementa'] ?? '',
            'urlInteiroTeor' => $materia['LinkInteiroTeor'] ?? '',
            'tramitacoes'    => $tram['Tramitacoes']['Tramitacao'] ?? [],
            'votacoes'       => $votos['Votacoes']['Votacao'] ?? [],
            'texto_original' => $materia['Ementa'] ?? null,
        ];
    }
}
