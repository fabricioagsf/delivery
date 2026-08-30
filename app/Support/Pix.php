<?php

namespace App\Support;

/**
 * Monta o payload "copia e cola" (BR Code / EMV) de um Pix estático
 * a partir da chave Pix da loja — tudo em PHP puro, sem biblioteca nova.
 *
 * O payload é gerado com os dados reais da loja (chave Pix, nome do
 * recebedor e cidade). Se faltar qualquer um deles, `copiaECola()` devolve
 * null e nenhum QR é exibido (regra: nunca mostrar dados falsos).
 */
class Pix
{
    public const GUI = 'BR.GOV.BCB.PIX';

    /**
     * Gera o payload "copia e cola". Retorna null quando os dados não são
     * suficientes para montar um Pix válido.
     */
    public static function copiaECola(string $chave, float $valor, string $nome, string $cidade): ?string
    {
        $chave = self::sanear(self::normalizarChave($chave));
        $nome = self::sanear($nome);
        $cidade = self::sanear($cidade);

        if ($chave === '' || $nome === '' || $cidade === '' || $valor < 0) {
            return null;
        }

        // Limites do padrão EMV/BR Code: nome do recebedor 25 e cidade 15
        // caracteres (o manual pede conteúdo ASCII em maiúsculas).
        $nome = strtoupper(mb_substr($nome, 0, 25));
        $cidade = strtoupper(mb_substr($cidade, 0, 15));

        $payload = '000201'
            . self::campo('26', '0014'.self::GUI.self::campo('01', $chave))
            . '52040000'
            . '5303986'
            . self::campo('54', number_format($valor, 2, '.', ''))
            . '5802BR'
            . self::campo('59', $nome)
            . self::campo('60', $cidade)
            . '62070503***';

        return $payload.'6304'.self::crc16($payload.'6304');
    }

    private static function campo(string $id, string $valor): string
    {
        return $id.str_pad((string) mb_strlen($valor), 2, '0', STR_PAD_LEFT).$valor;
    }

    private static function crc16(string $dados): string
    {
        $crc = 0xFFFF;

        foreach (str_split($dados) as $byte) {
            $crc ^= ord($byte) << 8;

            for ($i = 0; $i < 8; $i++) {
                $crc = ($crc & 0x8000) !== 0
                    ? (($crc << 1) ^ 0x1021) & 0xFFFF
                    : ($crc << 1) & 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    private static function normalizarChave(string $chave): string
    {
        // A chave é usada exatamente como cadastrada no banco — nunca
        // alteramos o formato (UUID/hífens, CPF/CNPJ, telefone ou e-mail).
        $chave = trim($chave);

        if (str_contains($chave, '@')) {
            // E-mail: só o domínio é insensível a maiúsculas/minúsculas.
            return mb_strtolower($chave);
        }

        if (preg_match('/^\d{11}$|^\d{14}$/', $chave)) {
            return $chave; // CPF ou CNPJ
        }

        return $chave;
    }

    private static function sanear(string $texto): string
    {
        $acentos = [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C', 'Ñ' => 'N',
        ];

        $texto = strtr($texto, $acentos);
        $texto = trim(preg_replace('/[^\x20-\x7E]/', '', $texto) ?? '');

        return $texto;
    }
}