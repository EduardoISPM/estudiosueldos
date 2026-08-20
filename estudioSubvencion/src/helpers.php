<?php
// helpers.php: funciones cortas de apoyo para las vistas.

declare(strict_types=1);

require_once __DIR__ . '/Parser.php';

/** Escapa texto antes de imprimirlo en HTML (evita romper la página o inyectar código). */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/** 7636750 -> "$ 7.636.750" */
function pesos(int|float|string|null $monto): string
{
    return '$ ' . number_format((float) $monto, 0, ',', '.');
}

/** 78.9344 -> "78,93" */
function decimal(int|float|string|null $valor, int $decimales = 2): string
{
    return number_format((float) $valor, $decimales, ',', '.');
}

function nombreMes(int $mes): string
{
    return Parser::NOMBRE_MES[$mes] ?? (string) $mes;
}

function periodo(int $anio, int $mes): string
{
    return nombreMes($mes) . ' ' . $anio;
}
