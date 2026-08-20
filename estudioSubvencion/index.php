<?php
// index.php: lista los meses ya cargados y muestra un gráfico con el total pagado.

declare(strict_types=1);

require_once __DIR__ . '/src/Liquidaciones.php';
require_once __DIR__ . '/src/helpers.php';

$liquidaciones = Liquidaciones::listar();
$serie = Liquidaciones::serieMensual();
$etiquetas = array_map(
    static fn (array $f): string => periodo((int) $f['anio'], (int) $f['mes']),
    $serie
);
$montos = array_map(static fn (array $f): int => (int) $f['total_pagado'], $serie);

$titulo = 'Inicio';
require __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Meses cargados</h1>
    <a class="btn btn-primary" href="subir.php">Subir un mes</a>
</div>

<?php if ($liquidaciones === []): ?>
    <div class="alert alert-info">
        Todavía no hay datos. Empieza subiendo el archivo <code>.xls</code> que descargas
        desde Liquidación Web del Mineduc (Anexo Escolaridad · Subvención Normal).
    </div>
<?php else: ?>
    <div class="card mb-4">
        <div class="card-body">
            <canvas id="grafico" height="90"></canvas>
        </div>
    </div>

    <div class="table-responsive card">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead class="table-light">
            <tr>
                <th>Periodo</th>
                <th>Establecimiento</th>
                <th class="text-end">Asistencia promedio</th>
                <th class="text-end">Subvención base</th>
                <th class="text-end">Total Ley 19.933</th>
                <th class="text-end">Total pagado</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($liquidaciones as $liq): ?>
                <tr>
                    <td><?= e(periodo((int) $liq['anio'], (int) $liq['mes'])) ?></td>
                    <td><?= e($liq['rbd'] . ' - ' . $liq['establecimiento']) ?></td>
                    <td class="text-end"><?= decimal($liq['total_asistencia']) ?></td>
                    <td class="text-end"><?= pesos($liq['total_base']) ?></td>
                    <td class="text-end"><?= pesos($liq['total_ley']) ?></td>
                    <td class="text-end fw-semibold">
                        <?= pesos((int) $liq['total_base'] + (int) $liq['total_ley']) ?>
                    </td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="detalle.php?id=<?= (int) $liq['id'] ?>">Ver detalle</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<?php if ($liquidaciones !== []): ?>
<script>
    new Chart(document.getElementById('grafico'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($etiquetas, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: 'Total pagado ($)',
                data: <?= json_encode($montos) ?>,
                backgroundColor: '#0d6efd'
            }]
        },
        options: {
            plugins: {legend: {display: false}},
            scales: {y: {ticks: {callback: v => '$ ' + v.toLocaleString('es-CL')}}}
        }
    });
</script>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
