<?php
// importar.php: permite cargar un archivo desde la terminal, sin usar el navegador.
// Uso:  php importar.php archivo.xls [--reemplazar]

declare(strict_types=1);

require_once __DIR__ . '/src/Liquidaciones.php';
require_once __DIR__ . '/src/helpers.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script solo se ejecuta desde la terminal.\n");
}

$ruta = $argv[1] ?? null;
if ($ruta === null || !is_file($ruta)) {
    exit("Uso: php importar.php <archivo.xls> [--reemplazar]\n");
}

$datos = Parser::parse($ruta);
$id = Liquidaciones::guardar($datos, basename($ruta), in_array('--reemplazar', $argv, true));

printf(
    "Cargado: %s | %s - %s | %d filas | id=%d\n",
    periodo($datos['anio'], $datos['mes']),
    $datos['rbd'],
    $datos['establecimiento'],
    count($datos['filas']),
    $id
);
