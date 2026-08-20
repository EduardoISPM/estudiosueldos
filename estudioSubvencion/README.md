# estudioSubvencion

Aplicación web sencilla (PHP + SQLite) para **subir las liquidaciones mensuales de subvención
escolar del Mineduc** (archivo "Anexo Escolaridad · Subvención Normal", que se descarga con
extensión `.xls` pero por dentro es HTML), guardarlas y **compararlas mes a mes y año a año**.

## Cómo ejecutarla

Necesitas PHP 8 con las extensiones `pdo_sqlite`, `dom` y `mbstring` (vienen en `php-cli`,
`php-sqlite3`, `php-xml`, `php-mbstring`).

```bash
cd estudioSubvencion
php -S localhost:8000
```

Luego abre <http://localhost:8000> en el navegador.

La base de datos se crea sola en `estudioSubvencion/data/subvenciones.sqlite`
(esa carpeta está en `.gitignore`, así que tus datos no se suben a GitHub).

También puedes cargar un archivo desde la terminal:

```bash
php importar.php ruta/al/Subvencion_Normal_Anexo_Detalle_Escolaridad_RBD_10618_202601.xls
```

## Qué hace cada archivo (guía de estudio)

| Archivo | Para qué sirve |
| --- | --- |
| `index.php` | Página de inicio: lista los meses cargados y un gráfico de barras con el total pagado. |
| `subir.php` | Formulario para subir el archivo del mes; llama al parser y guarda en la base. |
| `detalle.php` | Muestra un mes: totales, resumen por código de enseñanza y detalle curso por curso. |
| `comparar.php` | Tabla y gráfico comparando los mismos meses entre distintos años. |
| `importar.php` | Lo mismo que `subir.php`, pero desde la terminal. |
| `src/Parser.php` | Lee el HTML del Mineduc y lo convierte en filas de datos. |
| `src/Db.php` | Abre SQLite y crea las tablas la primera vez. |
| `src/Liquidaciones.php` | Todas las consultas SQL (guardar, listar, resumir). |
| `src/helpers.php` | Funciones cortas para mostrar pesos, decimales y meses en español. |
| `partials/` | Encabezado y pie de página comunes a todas las vistas. |

## Cómo se leen los datos del archivo del Mineduc

1. El archivo es una página HTML con una tabla grande.
2. Del texto se extraen: **sostenedor**, **establecimiento (RBD)** y **MES PAGO** (por ejemplo
   "ENERO 2026"). Si no aparecen, se usa el `AAAAMM` del nombre del archivo.
3. De la tabla se toman solo las filas de **14 columnas** que empiezan con un número: esas son
   las filas de detalle. Las filas "Total ..." se ignoran, porque los totales se recalculan.
4. Los montos vienen como `$ 7.636.750` (se convierten a `7636750`) y los decimales como
   `78,9344` (coma decimal chilena → `78.9344`).

## Modelo de datos

```
liquidacion (un registro por mes cargado)
  id, rbd, establecimiento, sostenedor, anio, mes, archivo,
  total_asistencia, total_base, total_ley
      │
      └── detalle (una fila por curso/código de subvención)
            cod_ens, grado, jec, letra, ens, nivel, glosa,
            asistencia, factor_use, subv_base, subv_ley,
            subv_zona, subv_rural, total_ley
```

Hay una restricción `UNIQUE (rbd, anio, mes)`: no se puede cargar dos veces el mismo mes por
error. Si quieres volver a cargarlo, marca la casilla **"Reemplazar"**.

## Ideas para los próximos pasos

- Exportar a Excel/CSV los comparativos.
- Agregar otros anexos del Mineduc (SEP, Pro Retención, mantenimiento).
- Calcular el ingreso por alumno y compararlo con el gasto en remuneraciones.
