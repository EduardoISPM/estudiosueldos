<?php
/** @var string $titulo */
$titulo = $titulo ?? 'estudioSubvencion';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · estudioSubvencion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">estudioSubvencion</a>
        <div class="navbar-nav">
            <a class="nav-link" href="index.php">Inicio</a>
            <a class="nav-link" href="subir.php">Subir archivo</a>
            <a class="nav-link" href="comparar.php">Comparar meses</a>
        </div>
    </div>
</nav>
<div class="container pb-5">
