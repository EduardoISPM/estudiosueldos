<?php
// comparar.php: compara los meses cargados, agrupados por año.

declare(strict_types=1);

require_once __DIR__ . '/src/Liquidaciones.php';
require_once __DIR__ . '/src/helpers.php';

$serie = Liquidaciones::serieMensual();

// Armamos una matriz: [año][mes] = total pagado
$matriz = [];
foreach ($serie as $fila) {
    $matriz[(int) $fila['anio']][(int) $fila['mes']] = $fila;
}
ksort($matriz);

$datasets = [];
$colores = ['#0d6efd', '#dc3545', '#198754', '#fd7e14', '#6f42c1'];
$i = 0;
foreach ($matriz as $anio => $meses) {
    $puntos = [];
    for ($mes = 1; $mes <= 12; $mes++) {
        $puntos[] = isset($meses[$mes]) ? (int) $meses[$mes]['total_pagado'] : null;
    }
    $datasets[] = [
        'label' => (string) $anio,
        'data' => $puntos,
        'borderColor' => $colores[$i % count($colores)],
        'backgroundColor' => $colores[$i % count($colores)],
        'spanGaps' => true,
    ];
    $i++;
}

$titulo = 'Comparar meses';
require __DIR__ . '/partials/header.php';
?>

<h1 class="h3 mb-3">Comparación mensual</h1>

<?php if ($serie === []): ?>
    <div class="alert alert-info">Aún no hay meses cargados.</div>
<?php else: ?>
    <div class="card mb-4"><div class="card-body">
        <canvas id="grafico" height="90"></canvas>
    </div></div>

    <div class="table-responsive card">
        <table class="table table-striped mb-0">
            <thead class="table-light">
            <tr>
                <th>Mes</th>
                <?php foreach (array_keys($matriz) as $anio): ?>
                    <th class="text-end"><?= (int) $anio ?></th>
                <?php endforeach; ?>
                <?php if (count($matriz) > 1): ?>
                    <th class="text-end">Variación</th>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php for ($mes = 1; $mes <= 12; $mes++): ?>
                <?php
                $valores = [];
                foreach ($matriz as $anio => $meses) {
                    $valores[$anio] = isset($meses[$mes]) ? (int) $meses[$mes]['total_pagado'] : null;
                }
                if (array_filter($valores, static fn ($v): bool => $v !== null) === []) {
                    continue;
                }
                $anios = array_keys($valores);
                $primero = $valores[$anios[0]];
                $ultimo = $valores[$anios[count($anios) - 1]];
                ?>
                <tr>
                    <td><?= e(nombreMes($mes)) ?></td>
                    <?php foreach ($valores as $valor): ?>
                        <td class="text-end"><?= $valor === null ? '—' : pesos($valor) ?></td>
                    <?php endforeach; ?>
                    <?php if (count($matriz) > 1): ?>
                        <td class="text-end">
                            <?php if ($primero && $ultimo): ?>
                                <?php $variacion = ($ultimo - $primero) / $primero * 100; ?>
                                <span class="<?= $variacion >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= decimal($variacion, 1) ?> %
                                </span>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endfor; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        new Chart(document.getElementById('grafico'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_values(Parser::NOMBRE_MES), JSON_UNESCAPED_UNICODE) ?>,
                datasets: <?= json_encode($datasets, JSON_UNESCAPED_UNICODE) ?>
            },
            options: {
                scales: {y: {ticks: {callback: v => '$ ' + v.toLocaleString('es-CL')}}}
            }
        });
    </script>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
