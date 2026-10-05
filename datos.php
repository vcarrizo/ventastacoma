<?php
require __DIR__ . '/lib/sesion.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (empty($_SESSION['ok'])) {
    http_response_code(401);
    echo json_encode(['error' => 'La sesión venció. Volvé a ingresar.']);
    exit;
}
session_write_close();
$f = __DIR__ . '/data/ventas.json';
if (is_file($f)) {
    readfile($f);
} else {
    echo '{"facturas":{},"cargas":[]}';
}
