<?php
require __DIR__ . '/lib/sesion.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function salir(array $d, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['ok'])) {
    salir(['error' => 'La sesión venció. Volvé a ingresar.'], 401);
}
session_write_close();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    salir(['error' => 'Método no permitido.'], 405);
}
$cfg = require __DIR__ . '/config.php';
date_default_timezone_set($cfg['zona_horaria'] ?? 'America/Argentina/Buenos_Aires');

$raw = file_get_contents('php://input');
if ($raw === false || strlen($raw) > 40 * 1024 * 1024) {
    salir(['error' => 'El archivo es demasiado grande.'], 413);
}
$in = json_decode($raw, true);
if (!is_array($in) || !isset($in['facturas']) || !is_array($in['facturas'])) {
    salir(['error' => 'No llegaron datos de facturas válidos.'], 400);
}

$dir = __DIR__ . '/data';
if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
    salir(['error' => 'No se puede crear la carpeta data. Revisá los permisos.'], 500);
}
$f = "$dir/ventas.json";
$lock = fopen("$dir/.lock", 'c');
if (!$lock || !flock($lock, LOCK_EX)) {
    salir(['error' => 'No se pudo bloquear la base para guardar. Probá de nuevo.'], 500);
}

$db = is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
if (!is_array($db)) {
    $db = ['facturas' => [], 'cargas' => []];
}
$db['facturas'] = is_array($db['facturas'] ?? null) ? $db['facturas'] : [];

// Respaldo antes de tocar nada (se guardan los últimos 20)
if (is_file($f)) {
    copy($f, "$dir/respaldo-" . date('Ymd-His') . '.json');
    $resp = glob("$dir/respaldo-*.json") ?: [];
    sort($resp);
    foreach (array_slice($resp, 0, max(0, count($resp) - 20)) as $viejo) {
        @unlink($viejo);
    }
}

$modo = ($in['modo'] ?? '') === 'reemplazar' ? 'reemplazar' : 'combinar';
if ($modo === 'reemplazar') {
    $db['facturas'] = [];
}

$campos = ['id', 'num', 'fecha', 'venc', 'estado', 'cli', 'mon', 'tc', 'sub', 'tot', 'saldo', 'envio', 'desc', 'ajuste'];
$camposItem = ['n', 'd', 'sku', 'u', 'q', 'p', 't'];
$nuevas = $actualizadas = $quitadas = 0;

foreach ($in['facturas'] as $id => $r) {
    $id = (string)$id;
    if (!preg_match('/^[\w-]{1,40}$/', $id) || !is_array($r)) {
        continue;
    }
    $limpia = array_intersect_key($r, array_flip($campos));
    $limpia['id'] = $id;
    $limpia['items'] = [];
    foreach (($r['items'] ?? []) as $it) {
        if (is_array($it)) {
            $limpia['items'][] = array_intersect_key($it, array_flip($camposItem));
        }
    }
    if (isset($db['facturas'][$id])) {
        $actualizadas++;
    } else {
        $nuevas++;
    }
    $db['facturas'][$id] = $limpia;
}
foreach (($in['anuladas'] ?? []) as $id) {
    $id = (string)$id;
    if (isset($db['facturas'][$id])) {
        unset($db['facturas'][$id]);
        $quitadas++;
    }
}

$db['cargas'] = is_array($db['cargas'] ?? null) ? $db['cargas'] : [];
$db['cargas'][] = [
    'archivo'      => mb_substr(basename((string)($in['archivo'] ?? 'archivo.csv')), 0, 120),
    'fecha'        => date('c'),
    'modo'         => $modo,
    'facturas'     => count($in['facturas']),
    'nuevas'       => $nuevas,
    'actualizadas' => $actualizadas,
    'quitadas'     => $quitadas,
];
$db['cargas'] = array_slice($db['cargas'], -50);

$json = json_encode([
    'facturas' => $db['facturas'] ?: new stdClass(),
    'cargas'   => $db['cargas'],
], JSON_UNESCAPED_UNICODE);
$tmp = "$f.tmp";
if ($json === false || file_put_contents($tmp, $json) === false || !rename($tmp, $f)) {
    salir(['error' => 'No se pudo guardar la base. Revisá los permisos de la carpeta data.'], 500);
}
flock($lock, LOCK_UN);

salir([
    'ok'           => true,
    'nuevas'       => $nuevas,
    'actualizadas' => $actualizadas,
    'quitadas'     => $quitadas,
    'total'        => count($db['facturas']),
]);
