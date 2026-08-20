<?php
// detalle.php: muestra el detalle de un mes cargado (por curso y por nivel).

declare(strict_types=1);

require_once __DIR__ . '/src/Liquidaciones.php';
require_once __DIR__ . '/src/helpers.php';

$id = (int) ($_GET['id'] ?? 0);
$liq = Liquidaciones::buscar($id);
if ($liq === null) {
    http_response_code(404);
    $titulo = 'No encontrado';
    require __DIR__ . '/partials/header.php';
    echo '<div class="alert alert-warning">Esa liquidación no existe.</div>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

$detalle = Liquidaciones::detalle($id);
$porEnsenanza = Liquidaciones::resumenPorEnsenanza($id);

$titulo = periodo((int) $liq['anio'], (int) $liq['mes']);
require __DIR__ . '/partials/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success"><?= e((string) $_GET['ok']) ?></div>
<?php endif; ?>

<h1 class="h3"><?= e(periodo((int) $liq['anio'], (int) $liq['mes'])) ?></h1>
<p class="text-muted">
    <?= e($liq['rbd'] . ' - ' . $liq['establecimiento']) ?><br>
    <?= e($liq['sostenedor']) ?><br>
    Archivo: <?= e($liq['archivo']) ?>
</p>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center"><div class="card-body">
            <div class="text-muted small">Asistencia promedio</div>
            <div class="fs-4"><?= decimal($liq['total_asistencia']) ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card text-center"><div class="card-body">
            <div class="text-muted small">Subvención base</div>
            <div class="fs-4"><?= pesos($liq['total_base']) ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card text-center"><div class="card-body">
            <div class="text-muted small">Total pagado</div>
            <div class="fs-4"><?= pesos((int) $liq['total_base'] + (int) $liq['total_ley']) ?></div>
        </div></div>
    </div>
</div>

<h2 class="h5">Resumen por código de enseñanza</h2>
<div class="table-responsive card mb-4">
    <table class="table mb-0">
        <thead class="table-light">
        <tr>
            <th>Cód. Enseñanza</th>
            <th class="text-end">Asistencia</th>
            <th class="text-end">Subvención base</th>
            <th class="text-end">Total Ley 19.933</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($porEnsenanza as $fila): ?>
            <tr>
                <td><?= (int) $fila['cod_ens'] ?></td>
                <td class="text-end"><?= decimal($fila['asistencia']) ?></td>
                <td class="text-end"><?= pesos($fila['subv_base']) ?></td>
                <td class="text-end"><?= pesos($fila['total_ley']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h2 class="h5">Detalle por curso (<?= count($detalle) ?> filas)</h2>
<div class="table-responsive card">
    <table class="table table-sm table-striped mb-0">
        <thead class="table-light">
        <tr>
            <th>Cód.</th><th>Grado</th><th>JEC</th><th>Letra</th><th>Ens.</th>
            <th>Glosa</th>
            <th class="text-end">Asistencia</th>
            <th class="text-end">Factor USE</th>
            <th class="text-end">Base</th>
            <th class="text-end">Ley 19.933</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($detalle as $fila): ?>
            <tr>
                <td><?= (int) $fila['cod_ens'] ?></td>
                <td><?= (int) $fila['grado'] ?></td>
                <td><?= e($fila['jec']) ?></td>
                <td><?= e($fila['letra']) ?></td>
                <td><?= (int) $fila['ens'] ?></td>
                <td class="small"><?= e($fila['glosa']) ?></td>
                <td class="text-end"><?= decimal($fila['asistencia'], 4) ?></td>
                <td class="text-end"><?= decimal($fila['factor_use'], 5) ?></td>
                <td class="text-end"><?= pesos($fila['subv_base']) ?></td>
                <td class="text-end"><?= pesos($fila['total_ley']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
