<?php
require __DIR__ . '/lib/sesion.php';
$cfg = require __DIR__ . '/config.php';

if (isset($_GET['salir'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: ./');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (clave_ok((string)($_POST['clave'] ?? ''), $cfg)) {
        session_regenerate_id(true);
        $_SESSION['ok'] = true;
        header('Location: ./');
        exit;
    }
    sleep(1);
    $error = 'La clave no coincide.';
}
$ok = !empty($_SESSION['ok']);
$v = '2.0';
?><!doctype html>
<html lang="es-AR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>Ventas · TACOMA</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌲</text></svg>">
<link rel="stylesheet" href="assets/app.css?v=<?= $v ?>">
</head>
<body>
<?php if (!$ok): ?>
<main class="ingreso">
  <form method="post" class="ingreso-caja">
    <p class="logo">TACOMA</p>
    <h1>Tablero de ventas</h1>
    <label for="clave">Clave</label>
    <input id="clave" name="clave" type="password" autocomplete="current-password" required autofocus>
    <?php if ($error): ?><p class="error" role="alert"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <button type="submit">Ingresar</button>
  </form>
</main>
<?php else: ?>
<header class="barra">
  <p class="marca"><span class="logo">TACOMA</span> <span class="marca-sub">Ventas</span></p>
  <div class="periodos" role="group" aria-label="Período">
    <button data-p="mes">Este mes</button>
    <button data-p="mes_ant">Mes anterior</button>
    <button data-p="3m">Últimos 3 meses</button>
    <button data-p="6m" aria-pressed="true">Últimos 6 meses</button>
    <button data-p="anio">Este año</button>
    <button data-p="todo">Todo</button>
    <label class="sel-mes">Mes
      <select id="mes-sel"><option value="">Elegir</option></select>
    </label>
  </div>
  <div class="acciones">
    <button id="cargar" class="primario">Cargar CSV de Zoho</button>
    <input type="file" id="archivo" accept=".csv,text/csv" hidden>
    <a href="?salir=1" class="salir">Salir</a>
  </div>
</header>

<div id="estado" class="estado" role="status" aria-live="polite"></div>

<main id="vacio" class="vacio" hidden>
  <div class="vacio-caja" id="vacio-caja">
    <h1>Soltá acá el CSV de facturas</h1>
    <p>En Zoho Books entrá a Ventas, Facturas, y usá Exportar facturas en formato CSV. Arrastrá el archivo a esta página o tocá el botón.</p>
    <button id="cargar2" class="primario">Elegir archivo</button>
  </div>
</main>

<main id="tablero" class="tablero" hidden>
  <section class="volumen" aria-labelledby="t-vol">
    <div class="vol-cifra">
      <h2 id="t-vol" class="vol-titulo">Cabos vendidos</h2>
      <p class="vol-num" id="cabos">—</p>
      <dl class="vol-datos">
        <div><dt>Madera en cabos</dt><dd id="m3">—</dd></div>
        <div><dt>Pies tablares equivalentes</dt><dd id="pt">—</dd></div>
        <div><dt>Precio promedio por cabo</dt><dd id="pcabo">—</dd></div>
        <div><dt>Contra el período anterior</dt><dd id="cabos-delta">—</dd></div>
      </dl>
    </div>
    <div class="vol-meses">
      <div class="unidades" role="group" aria-label="Unidad del gráfico">
        <button data-u="cabos" aria-pressed="true">Cabos</button>
        <button data-u="m3">m³</button>
        <button data-u="pt">Pies tablares</button>
      </div>
      <div class="tablones" id="tablones" role="list" aria-label="Volumen por mes"></div>
    </div>
  </section>

  <section class="cifras" id="cifras" aria-label="Resultados del período"></section>

  <section class="panel">
    <h2>Ventas netas por mes</h2>
    <div class="barras" id="barras"></div>
  </section>

  <div class="dos">
    <section class="panel">
      <h2>Clientes</h2>
      <div class="tabla-scroll"><table id="t-clientes"></table></div>
    </section>
    <section class="panel">
      <h2>Productos</h2>
      <div class="tabla-scroll"><table id="t-productos"></table></div>
    </section>
  </div>

  <section class="panel cobrar">
    <div class="panel-cab">
      <h2>Cuentas a cobrar</h2>
      <p class="nota" id="cobrar-nota"></p>
    </div>
    <div class="cobrar-cifras" id="cobrar-cifras"></div>
    <div class="tabla-scroll"><table id="t-vencidas"></table></div>
  </section>

  <footer class="pie" id="pie"></footer>
</main>

<div class="soltar" id="soltar" hidden><p>Soltá el CSV para actualizar las ventas</p></div>
<script src="assets/app.js?v=<?= $v ?>"></script>
<?php endif; ?>
</body>
</html>
