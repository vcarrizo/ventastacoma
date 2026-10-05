# Tablero de ventas TACOMA — ventas.tacoma-maderas.com.ar

Tablero que se alimenta arrastrando el CSV de facturas exportado de Zoho Books.
El archivo se lee en el navegador y la base queda guardada en el servidor, así que
cualquiera que entre con la clave ve los mismos datos. Requiere PHP 7.4 o superior.

## Instalación
1. En el panel del hosting creá el subdominio `ventas` de `tacoma-maderas.com.ar`
   con su propia carpeta, y activá el SSL (AutoSSL / Let's Encrypt).
2. Subí todo el contenido de esta carpeta a la carpeta del subdominio.
3. Copiá `config.sample.php` como `config.php` y cambiá la clave.
4. Dale permisos de escritura a la carpeta `data` (755, o 775 si el hosting lo pide).
5. Entrá a https://ventas.tacoma-maderas.com.ar

## Uso
En Zoho Books: Ventas, Facturas, Exportar facturas, formato CSV. Arrastrá el archivo
a cualquier parte de la página (o tocá "Cargar CSV de Zoho").

- Cada carga combina con lo que ya había: las facturas se identifican por su ID de Zoho,
  así que si volvés a subir un período, se actualizan (por ejemplo los saldos cobrados)
  sin duplicarse. Las facturas anuladas que vengan en el archivo se quitan de la base.
- Para que los saldos a cobrar estén al día, exportá siempre un período que incluya
  todas las facturas que todavía tienen saldo.
- Antes de cada carga se guarda un respaldo en `data/respaldo-*.json` (los últimos 20).
  Para volver atrás, renombrá un respaldo como `data/ventas.json`.

## Cálculos
- Ventas netas: suma de renglones de producto, sin IVA ni envíos, menos la bonificación
  de cada factura. Los renglones llamados "Imp" se toman como impuesto y no suman.
  Para cambiar esa lista, editá `ITEMS_IMPUESTO` al principio de `assets/app.js`.
- Cabos: renglones cuyo nombre o descripción tiene el largo (1.20 mts, 1.35 mt o 130).
  El diámetro sale del nombre (23mm, 28 mm) o se asume 23 mm.
- Volumen: madera del cabo terminado, π × radio² × largo. Pies tablares = m³ / 0,002359737.
- Viruta: renglones que dicen "viruta"; se cuentan bolsas.
- Saldo a cobrar y vencido: columna Balance y fecha de vencimiento del último archivo,
  sin importar el período elegido.
