<?php
// Parser.php: lee el archivo .xls que entrega el Mineduc (que en realidad es una
// página HTML con una tabla) y lo transforma en datos que podemos guardar.

declare(strict_types=1);

class Parser
{
    private const MESES = [
        'ENERO' => 1, 'FEBRERO' => 2, 'MARZO' => 3, 'ABRIL' => 4,
        'MAYO' => 5, 'JUNIO' => 6, 'JULIO' => 7, 'AGOSTO' => 8,
        'SEPTIEMBRE' => 9, 'OCTUBRE' => 10, 'NOVIEMBRE' => 11, 'DICIEMBRE' => 12,
    ];

    public const NOMBRE_MES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    /**
     * Devuelve un arreglo con las claves:
     *   rbd, establecimiento, sostenedor, anio, mes, filas[]
     * Lanza RuntimeException si el archivo no tiene el formato esperado.
     */
    public static function parse(string $ruta): array
    {
        $html = file_get_contents($ruta);
        if ($html === false) {
            throw new RuntimeException('No se pudo leer el archivo.');
        }

        // El archivo puede venir en UTF-8 o en ISO-8859-1 (Latin1).
        if (!mb_check_encoding($html, 'UTF-8')) {
            $html = mb_convert_encoding($html, 'UTF-8', 'ISO-8859-1');
        }

        $texto = self::soloTexto($html);
        $meta = self::metadatos($texto, basename($ruta));
        $filas = self::filas($html);

        if ($filas === []) {
            throw new RuntimeException(
                'El archivo no contiene filas de detalle. ¿Es el Anexo de Escolaridad de Subvención Normal?'
            );
        }

        return $meta + ['filas' => $filas];
    }

    private static function soloTexto(string $html): string
    {
        $texto = preg_replace('/<(script|style)\b.*?<\/\1>/is', ' ', $html) ?? $html;
        $texto = preg_replace('/<[^>]+>/', "\n", $texto) ?? $texto;
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_replace('/[ \t]+/', ' ', $texto) ?? $texto;
    }

    private static function metadatos(string $texto, string $archivo): array
    {
        $mes = null;
        $anio = null;
        if (preg_match('/MES PAGO:\s*([A-ZÁÉÍÓÚÑ]+)\s+(\d{4})/ui', $texto, $m)) {
            $nombre = mb_strtoupper(self::sinAcentos(trim($m[1])), 'UTF-8');
            $mes = self::MESES[$nombre] ?? null;
            $anio = (int) $m[2];
        }
        // Respaldo: el nombre del archivo termina en _AAAAMM.
        if (($mes === null || $anio === null) && preg_match('/_(\d{4})(\d{2})/', $archivo, $m)) {
            $anio = (int) $m[1];
            $mes = (int) $m[2];
        }
        if ($mes === null || $anio === null || $mes < 1 || $mes > 12) {
            throw new RuntimeException('No se pudo determinar el mes y año del archivo.');
        }

        $rbd = '';
        $establecimiento = '';
        if (preg_match('/Establecimiento:\s*\n?\s*(\d+)\s*-\s*(.+)/u', $texto, $m)) {
            $rbd = trim($m[1]);
            $establecimiento = trim($m[2]);
        } elseif (preg_match('/RBD_(\d+)/', $archivo, $m)) {
            $rbd = $m[1];
        }

        $sostenedor = '';
        if (preg_match('/Sostenedor:\s*\n?\s*(.+)/u', $texto, $m)) {
            $sostenedor = trim($m[1]);
        }

        return [
            'rbd' => $rbd,
            'establecimiento' => $establecimiento,
            'sostenedor' => $sostenedor,
            'anio' => $anio,
            'mes' => $mes,
        ];
    }

    /** Extrae solo las filas de detalle (las que tienen las 14 columnas). */
    private static function filas(string $html): array
    {
        $doc = new DOMDocument();
        $previo = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previo);

        $filas = [];
        foreach ($doc->getElementsByTagName('tr') as $tr) {
            $celdas = [];
            foreach ($tr->childNodes as $hijo) {
                if ($hijo instanceof DOMElement && in_array(strtolower($hijo->tagName), ['td', 'th'], true)) {
                    $celdas[] = trim(preg_replace('/\s+/u', ' ', $hijo->textContent) ?? '');
                }
            }
            if (count($celdas) !== 14 || !ctype_digit($celdas[0])) {
                continue; // encabezados y subtotales
            }

            $filas[] = [
                'cod_ens' => (int) $celdas[0],
                'grado' => (int) $celdas[1],
                'jec' => $celdas[2],
                'letra' => $celdas[3],
                'ens' => (int) $celdas[4],
                'nivel' => (int) $celdas[5],
                'glosa' => $celdas[6],
                'asistencia' => self::numero($celdas[7]),
                'factor_use' => self::numero($celdas[8]),
                'subv_base' => self::pesos($celdas[9]),
                'subv_ley' => self::pesos($celdas[10]),
                'subv_zona' => self::pesos($celdas[11]),
                'subv_rural' => self::pesos($celdas[12]),
                'total_ley' => self::pesos($celdas[13]),
            ];
        }

        return $filas;
    }

    // "78,9344" -> 78.9344 (en Chile la coma es el separador decimal)
    private static function numero(string $valor): float
    {
        $limpio = str_replace(['.', ','], ['', '.'], trim($valor));

        return $limpio === '' ? 0.0 : (float) $limpio;
    }

    // "$ 7.636.750" -> 7636750
    private static function pesos(string $valor): int
    {
        $limpio = preg_replace('/[^\d\-]/', '', $valor) ?? '';

        return $limpio === '' ? 0 : (int) $limpio;
    }

    private static function sinAcentos(string $texto): string
    {
        return strtr($texto, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        ]);
    }
}
