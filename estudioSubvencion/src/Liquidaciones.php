<?php
// Liquidaciones.php: todas las consultas a la base de datos en un solo lugar.

declare(strict_types=1);

require_once __DIR__ . '/Db.php';
require_once __DIR__ . '/Parser.php';

class Liquidaciones
{
    /**
     * Guarda (o reemplaza si $reemplazar es true) una liquidación mensual.
     * Devuelve el id de la liquidación guardada.
     */
    public static function guardar(array $datos, string $archivo, bool $reemplazar = false): int
    {
        $pdo = Db::conn();
        $existente = self::buscarPorPeriodo($datos['rbd'], $datos['anio'], $datos['mes']);
        if ($existente !== null && !$reemplazar) {
            throw new RuntimeException(sprintf(
                'Ya existe una carga para %s %d (RBD %s). Marca "reemplazar" si quieres sobrescribirla.',
                Parser::NOMBRE_MES[$datos['mes']],
                $datos['anio'],
                $datos['rbd']
            ));
        }

        $pdo->beginTransaction();
        try {
            if ($existente !== null) {
                $pdo->prepare('DELETE FROM liquidacion WHERE id = ?')->execute([$existente['id']]);
            }

            $totalAsistencia = 0.0;
            $totalBase = 0;
            $totalLey = 0;
            foreach ($datos['filas'] as $fila) {
                $totalAsistencia += $fila['asistencia'];
                $totalBase += $fila['subv_base'];
                $totalLey += $fila['total_ley'];
            }

            $pdo->prepare(
                'INSERT INTO liquidacion
                    (rbd, establecimiento, sostenedor, anio, mes, archivo,
                     total_asistencia, total_base, total_ley)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $datos['rbd'], $datos['establecimiento'], $datos['sostenedor'],
                $datos['anio'], $datos['mes'], $archivo,
                round($totalAsistencia, 4), $totalBase, $totalLey,
            ]);
            $id = (int) $pdo->lastInsertId();

            $insert = $pdo->prepare(
                'INSERT INTO detalle
                    (liquidacion_id, cod_ens, grado, jec, letra, ens, nivel, glosa,
                     asistencia, factor_use, subv_base, subv_ley, subv_zona, subv_rural, total_ley)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($datos['filas'] as $fila) {
                $insert->execute([
                    $id, $fila['cod_ens'], $fila['grado'], $fila['jec'], $fila['letra'],
                    $fila['ens'], $fila['nivel'], $fila['glosa'], $fila['asistencia'],
                    $fila['factor_use'], $fila['subv_base'], $fila['subv_ley'],
                    $fila['subv_zona'], $fila['subv_rural'], $fila['total_ley'],
                ]);
            }

            $pdo->commit();

            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function buscarPorPeriodo(string $rbd, int $anio, int $mes): ?array
    {
        $sql = 'SELECT * FROM liquidacion WHERE rbd = ? AND anio = ? AND mes = ?';
        $stmt = Db::conn()->prepare($sql);
        $stmt->execute([$rbd, $anio, $mes]);
        $fila = $stmt->fetch();

        return $fila === false ? null : $fila;
    }

    public static function buscar(int $id): ?array
    {
        $stmt = Db::conn()->prepare('SELECT * FROM liquidacion WHERE id = ?');
        $stmt->execute([$id]);
        $fila = $stmt->fetch();

        return $fila === false ? null : $fila;
    }

    public static function listar(): array
    {
        return Db::conn()
            ->query('SELECT * FROM liquidacion ORDER BY anio DESC, mes DESC')
            ->fetchAll();
    }

    public static function eliminar(int $id): void
    {
        Db::conn()->prepare('DELETE FROM liquidacion WHERE id = ?')->execute([$id]);
    }

    /** Detalle de una liquidación, ordenado por nivel y curso. */
    public static function detalle(int $liquidacionId): array
    {
        $stmt = Db::conn()->prepare(
            'SELECT * FROM detalle WHERE liquidacion_id = ? ORDER BY cod_ens, grado, letra, ens'
        );
        $stmt->execute([$liquidacionId]);

        return $stmt->fetchAll();
    }

    /** Totales por nivel de enseñanza (cod_ens) de una liquidación. */
    public static function resumenPorEnsenanza(int $liquidacionId): array
    {
        $stmt = Db::conn()->prepare(
            'SELECT cod_ens,
                    SUM(asistencia) AS asistencia,
                    SUM(subv_base) AS subv_base,
                    SUM(total_ley) AS total_ley
             FROM detalle WHERE liquidacion_id = ?
             GROUP BY cod_ens ORDER BY cod_ens'
        );
        $stmt->execute([$liquidacionId]);

        return $stmt->fetchAll();
    }

    /** Serie mensual para los gráficos y la comparación entre años. */
    public static function serieMensual(?int $anio = null): array
    {
        $sql = 'SELECT anio, mes, total_asistencia, total_base, total_ley,
                       (total_base + total_ley) AS total_pagado
                FROM liquidacion';
        $params = [];
        if ($anio !== null) {
            $sql .= ' WHERE anio = ?';
            $params[] = $anio;
        }
        $sql .= ' ORDER BY anio, mes';
        $stmt = Db::conn()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function anios(): array
    {
        return array_map(
            static fn (array $f): int => (int) $f['anio'],
            Db::conn()->query('SELECT DISTINCT anio FROM liquidacion ORDER BY anio DESC')->fetchAll()
        );
    }
}
