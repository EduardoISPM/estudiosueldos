<?php
// Db.php: se encarga de abrir la base de datos SQLite y crear las tablas.
// SQLite guarda todo en un solo archivo (data/subvenciones.sqlite), no necesitas
// instalar un servidor de base de datos.

declare(strict_types=1);

class Db
{
    private static ?PDO $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo === null) {
            $dir = dirname(__DIR__) . '/data';
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            self::$pdo = new PDO('sqlite:' . $dir . '/subvenciones.sqlite');
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::migrate(self::$pdo);
        }

        return self::$pdo;
    }

    // Crea las tablas si todavía no existen.
    private static function migrate(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS liquidacion (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                rbd TEXT NOT NULL,
                establecimiento TEXT NOT NULL,
                sostenedor TEXT NOT NULL,
                anio INTEGER NOT NULL,
                mes INTEGER NOT NULL,
                archivo TEXT NOT NULL,
                subido_en TEXT NOT NULL DEFAULT (datetime('now')),
                total_asistencia REAL NOT NULL DEFAULT 0,
                total_base INTEGER NOT NULL DEFAULT 0,
                total_ley INTEGER NOT NULL DEFAULT 0,
                UNIQUE (rbd, anio, mes)
            )
        SQL);

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS detalle (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                liquidacion_id INTEGER NOT NULL REFERENCES liquidacion(id) ON DELETE CASCADE,
                cod_ens INTEGER NOT NULL,
                grado INTEGER NOT NULL,
                jec TEXT NOT NULL,
                letra TEXT NOT NULL,
                ens INTEGER NOT NULL,
                nivel INTEGER NOT NULL,
                glosa TEXT NOT NULL,
                asistencia REAL NOT NULL,
                factor_use REAL NOT NULL,
                subv_base INTEGER NOT NULL,
                subv_ley INTEGER NOT NULL,
                subv_zona INTEGER NOT NULL,
                subv_rural INTEGER NOT NULL,
                total_ley INTEGER NOT NULL
            )
        SQL);

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_detalle_liq ON detalle(liquidacion_id)');
    }
}
