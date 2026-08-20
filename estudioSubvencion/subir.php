<?php
// subir.php: formulario para subir el archivo del mes y guardarlo en la base de datos.

declare(strict_types=1);

require_once __DIR__ . '/src/Liquidaciones.php';
require_once __DIR__ . '/src/helpers.php';

$error = null;
$ok = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se recibió el archivo (revisa el tamaño máximo de subida).');
        }

        $nombre = basename($_FILES['archivo']['name']);
        $datos = Parser::parse($_FILES['archivo']['tmp_name']);
        $id = Liquidaciones::guardar($datos, $nombre, isset($_POST['reemplazar']));

        $ok = sprintf(
            'Se cargó %s con %d filas de detalle.',
            periodo($datos['anio'], $datos['mes']),
            count($datos['filas'])
        );
        header('Location: detalle.php?id=' . $id . '&ok=' . urlencode($ok));
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$titulo = 'Subir archivo';
require __DIR__ . '/partials/header.php';
?>

<h1 class="h3 mb-3">Subir la liquidación de un mes</h1>

<?php if ($error !== null): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label" for="archivo">Archivo descargado del Mineduc (.xls)</label>
                <input class="form-control" type="file" name="archivo" id="archivo" accept=".xls,.xlsx,.html,.htm" required>
                <div class="form-text">
                    Es el archivo "Subvencion_Normal_Anexo_Detalle_Escolaridad_RBD_XXXXX_AAAAMM.xls".
                    El mes y el año se leen automáticamente desde el archivo.
                </div>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="reemplazar" id="reemplazar" value="1">
                <label class="form-check-label" for="reemplazar">
                    Reemplazar si ese mes ya fue cargado
                </label>
            </div>
            <button class="btn btn-primary" type="submit">Cargar</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
