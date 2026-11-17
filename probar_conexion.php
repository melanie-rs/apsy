<?php
/**
 * probar_conexion.php  ·  ARCHIVO TEMPORAL DE PRUEBA
 * Sirve solo para comprobar que la base y la conexión funcionan.
 * Borralo antes de la entrega.
 */
require_once 'includes/conexion.php';

function fila(string $texto, bool $ok): void {
    echo '<p>' . ($ok ? '✅' : '❌') . ' ' . htmlspecialchars($texto) . '</p>';
}

echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">'
   . '<title>Prueba APSY</title></head>'
   . '<body style="font-family:sans-serif;max-width:720px;margin:2rem auto">';
echo '<h1>Prueba de conexión · APSY</h1>';

// 1) Conexión
fila('Conectado a MySQL ' . $conexion->server_info . ' · base: ' . DB_NOMBRE, true);

// 2) Cantidad de filas por tabla
echo '<h2>Filas por tabla</h2><ul>';
foreach (['usuarios', 'clientes', 'profesionales', 'servicios', 'turnos'] as $tabla) {
    $total = $conexion->query("SELECT COUNT(*) AS total FROM $tabla")->fetch_assoc()['total'];
    echo "<li><strong>$tabla</strong>: $total</li>";
}
echo '</ul>';

// 3) Login simulado con password_verify()
$usuario = 'mariana.lopez';
$clave   = 'apsy2026';
$stmt = $conexion->prepare('SELECT password_hash FROM usuarios WHERE usuario = ?');
$stmt->bind_param('s', $usuario);
$stmt->execute();
$fila = $stmt->get_result()->fetch_assoc();
fila('password_verify() con la contraseña de prueba',
     $fila && password_verify($clave, $fila['password_hash']));
fila('password_verify() rechaza una contraseña incorrecta',
     $fila && !password_verify('incorrecta', $fila['password_hash']));

// 4) JOIN: demuestra que las claves foráneas relacionan las 4 tablas
echo '<h2>Turnos de hoy (JOIN de 4 tablas)</h2>';
$sql = "SELECT t.hora_inicio, t.hora_fin,
               CONCAT(c.nombre, ' ', c.apellido) AS cliente,
               CONCAT(p.nombre, ' ', p.apellido) AS profesional,
               s.nombre AS servicio, t.estado
        FROM turnos t
        INNER JOIN clientes c      ON c.id = t.cliente_id
        INNER JOIN profesionales p ON p.id = t.profesional_id
        INNER JOIN servicios s     ON s.id = t.servicio_id
        WHERE t.fecha = CURDATE()
        ORDER BY t.hora_inicio";
$resultado = $conexion->query($sql);

echo '<table border="1" cellpadding="6" cellspacing="0">'
   . '<tr><th>Hora</th><th>Cliente</th><th>Profesional</th><th>Servicio</th><th>Estado</th></tr>';
while ($t = $resultado->fetch_assoc()) {
    echo '<tr><td>' . substr($t['hora_inicio'], 0, 5) . '–' . substr($t['hora_fin'], 0, 5) . '</td>'
       . '<td>' . htmlspecialchars($t['cliente'])     . '</td>'
       . '<td>' . htmlspecialchars($t['profesional']) . '</td>'
       . '<td>' . htmlspecialchars($t['servicio'])    . '</td>'
       . '<td>' . htmlspecialchars($t['estado'])      . '</td></tr>';
}
echo '</table>';

// 5) La clave foránea realmente protege: intentar borrar un cliente con turnos
echo '<h2>Prueba de integridad referencial</h2>';
try {
    $conexion->query('DELETE FROM clientes WHERE id = 1');
    fila('Se pudo borrar un cliente con turnos (algo está mal)', false);
} catch (mysqli_sql_exception $e) {
    fila('MySQL impidió borrar un cliente con turnos (clave foránea RESTRICT)', true);
}

echo '<p><em>Recordá borrar este archivo antes de entregar.</em></p></body></html>';
