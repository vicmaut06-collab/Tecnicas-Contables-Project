<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$pdo = dbSinBase();

// En PostgreSQL la base se crea solo si no existe
$existe = $pdo->prepare('SELECT 1 FROM pg_database WHERE datname = ?');
$existe->execute([DB_NAME]);
if (!$existe->fetchColumn()) {
    $pdo->exec('CREATE DATABASE "' . DB_NAME . '"');
}

// A partir de aqui se trabaja sobre la base creada
$pdo = new PDO(
    'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME,
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$pdo->exec("SET client_encoding TO 'UTF8'");

function ejecutarScript(PDO $pdo, string $ruta): void
{
    $sql = file_get_contents($ruta);
    if ($sql === false) {
        throw new RuntimeException('No se pudo leer el archivo ' . $ruta);
    }
    $pdo->exec($sql);
}

$pasos = [
    '01_esquema.sql'  => 'Estructura de tablas',
    '02_catalogo.sql' => 'Catalogo de cuentas',
];

$log = [];
$todoOk = true;

foreach ($pasos as $archivo => $descripcion) {
    try {
        ejecutarScript($pdo, __DIR__ . '/database/' . $archivo);
        $log[] = ['ok' => true, 'texto' => $descripcion . ' (' . $archivo . ')'];
    } catch (Throwable $e) {
        $todoOk = false;
        $log[] = ['ok' => false, 'texto' => $descripcion . ': ' . $e->getMessage()];
    }
}

$totalCuentas = (int)$pdo->query('SELECT COUNT(*) FROM catalogo_cuentas')->fetchColumn();
$log[] = ['ok' => true, 'texto' => 'Cuentas registradas en el catalogo: ' . $totalCuentas];

$hojas = (int)$pdo->query('SELECT COUNT(*) FROM catalogo_cuentas WHERE es_hoja = 1')->fetchColumn();
$log[] = ['ok' => true, 'texto' => 'Cuentas de detalle (hojas): ' . $hojas];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Instalacion - Sistema Contable</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 820px;">
    <div class="card">
        <div class="card-header">Instalacion de la base de datos</div>
        <div class="card-body">
            <h5 class="mb-3">Base de datos: <code><?= e(DB_NAME) ?></code> en <code><?= e(DB_HOST) ?></code></h5>
            <ul class="list-group mb-3">
                <?php foreach ($log as $linea): ?>
                    <li class="list-group-item d-flex gap-2">
                        <span class="<?= $linea['ok'] ? 'cuadra' : 'no-cuadra' ?>"><?= $linea['ok'] ? 'OK' : 'ERROR' ?></span>
                        <span><?= e($linea['texto']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($todoOk): ?>
                <div class="alert alert-success">La base de datos quedo lista. Puede volver a instalar en cualquier momento: el catalogo no se duplica.</div>
                <a class="btn btn-success" href="<?= e(url('index.php')) ?>">Ir al sistema</a>
            <?php else: ?>
                <div class="alert alert-danger">Verifique que MySQL/MariaDB este encendido y que el usuario y contrasena de <code>config/config.php</code> sean correctos.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
