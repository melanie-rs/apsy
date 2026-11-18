<?php
/**
 * includes/funciones.php
 * Funciones de ayuda reutilizables en todas las páginas.
 * (En las próximas etapas se agregan aquí las validaciones y la paginación.)
 */

/**
 * Escapa texto antes de mostrarlo en HTML.
 * Convierte < > " ' & en entidades, así un dato como <script> se muestra
 * como texto y no se ejecuta (protección contra XSS).
 */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Dibuja un ícono SVG "inline" leyendo el archivo de assets/img/iconos.
 * Los íconos son de la librería Lucide (los mismos del diseño de Figma) y usan
 * stroke="currentColor": toman el color del texto que los rodea, por eso se
 * pueden pintar desde CSS.
 *
 * Uso:  <?= icono('users', 19) ?>
 */
function icono(string $nombre, int $tamano = 18, string $clase = ''): string
{
    static $cache = [];                      // evita leer el mismo archivo varias veces

    // Solo letras minúsculas, números y guiones: impide rutas como "../../x".
    if (!preg_match('/^[a-z0-9-]+$/', $nombre)) {
        return '';
    }

    if (!isset($cache[$nombre])) {
        $ruta = __DIR__ . '/../assets/img/iconos/' . $nombre . '.svg';
        $svg  = is_file($ruta) ? (string) file_get_contents($ruta) : '';
        $cache[$nombre] = trim(preg_replace('/<!--.*?-->/s', '', $svg));   // saca el comentario de licencia
    }

    $svg = $cache[$nombre];
    if ($svg === '') {
        return '';
    }

    $svg = preg_replace('/\sclass="[^"]*"/', '', $svg, 1);                       // quita la clase original
    $svg = preg_replace('/\swidth="24"/',  ' width="'  . $tamano . '"', $svg, 1);  // ajusta el tamaño
    $svg = preg_replace('/\sheight="24"/', ' height="' . $tamano . '"', $svg, 1);
    $svg = preg_replace('/<svg/', '<svg class="icono ' . e($clase) . '" aria-hidden="true" focusable="false"', $svg, 1);

    return $svg;
}

/** Iniciales para el avatar: "Mariana", "López" → "ML". */
function iniciales(string $nombre, string $apellido): string
{
    return mb_strtoupper(mb_substr($nombre, 0, 1) . mb_substr($apellido, 0, 1));
}

/** "Buen día", "Buenas tardes" o "Buenas noches" según la hora. */
function saludo_segun_hora(): string
{
    $hora = (int) date('G');          // 0 a 23, con la zona horaria de conexion.php
    if ($hora < 12) {
        return 'Buen día';
    }
    return $hora < 20 ? 'Buenas tardes' : 'Buenas noches';
}

/** Fecha en español: "Martes 27 de octubre de 2026". */
function fecha_larga(?DateTimeInterface $fecha = null): string
{
    $fecha ??= new DateTime();

    $dias   = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $meses  = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
               'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    return ucfirst($dias[(int) $fecha->format('w')])
         . ' ' . $fecha->format('j')
         . ' de ' . $meses[(int) $fecha->format('n') - 1]
         . ' de ' . $fecha->format('Y');
}

/* =====================================================================
   Etapa 3 (Clientes): lectura de datos, mensajes, paginación y validaciones
   ===================================================================== */

/** Lee un parámetro de la URL (?q=...) como texto. Si falta o viene como arreglo, devuelve ''. */
function get_texto(string $clave): string
{
    $valor = $_GET[$clave] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

/** Lee un campo de un formulario (POST) como texto, sin espacios sobrantes. */
function post_texto(string $clave): string
{
    $valor = $_POST[$clave] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

/**
 * Mensaje "flash": se guarda en la sesión, sobrevive a UNA redirección y
 * se muestra una sola vez (por ejemplo "Cliente guardado").
 */
function flash(string $tipo, string $texto): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];   // tipo: 'ok' o 'error'
}

function flash_obtener(): ?array
{
    $mensaje = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);          // se borra: así no vuelve a aparecer al recargar
    return $mensaje;
}

/**
 * Calcula los datos de paginación.
 * Si piden una página que no existe (0, negativa o mayor al total), la ajusta.
 * Devuelve: pagina, total_paginas, offset (cuántas filas saltear) y por_pagina.
 */
function paginar(int $total, int $por_pagina, int $solicitada): array
{
    $total_paginas = max(1, (int) ceil($total / $por_pagina));
    $pagina        = min(max(1, $solicitada), $total_paginas);

    return [
        'pagina'        => $pagina,
        'total_paginas' => $total_paginas,
        'por_pagina'    => $por_pagina,
        'offset'        => ($pagina - 1) * $por_pagina,
    ];
}

/**
 * Arma una URL de la página actual conservando los filtros de la URL y
 * cambiando solo lo indicado. Ej.: url_con(['pagina' => 2]) → "clientes.php?q=ana&pagina=2"
 */
function url_con(array $cambios): string
{
    $parametros = array_merge($_GET, $cambios);
    $parametros = array_filter($parametros, fn($v) => $v !== '' && $v !== null);

    return basename($_SERVER['PHP_SELF']) . ($parametros ? '?' . http_build_query($parametros) : '');
}

/** Deja solo los dígitos: "11 3456-7890" → "1134567890". */
function normalizar_telefono(string $telefono): string
{
    return preg_replace('/\D/', '', $telefono);
}

/** Muestra un teléfono de 10 dígitos como "11 3456-7890". */
function formatear_telefono(string $telefono): string
{
    if (!preg_match('/^\d{10}$/', $telefono)) {
        return $telefono;
    }
    return substr($telefono, 0, 2) . ' ' . substr($telefono, 2, 4) . '-' . substr($telefono, 6, 4);
}

/**
 * Valida un nombre o apellido de persona.
 * $campo es la palabra para el mensaje: 'nombre' o 'apellido'.
 * Devuelve el mensaje de error, o '' si está bien.
 */
function validar_nombre_persona(string $valor, string $campo): string
{
    if ($valor === '') {
        return "El $campo es obligatorio.";
    }
    if (mb_strlen($valor) > 60) {
        return "El $campo no puede superar los 60 caracteres.";
    }
    // \p{L} = cualquier letra (incluye tildes y ñ). Se permiten espacios, apóstrofes, puntos y guiones.
    if (!preg_match("/^\p{L}[\p{L}\s'.-]*$/u", $valor)) {
        return "El $campo solo puede tener letras, espacios y guiones.";
    }
    return '';
}

/** Valida un teléfono argentino de 10 dígitos (acepta espacios, guiones y paréntesis). */
function validar_telefono(string $valor): string
{
    if ($valor === '') {
        return 'El teléfono es obligatorio.';
    }
    // Cualquier carácter que no sea dígito, espacio, guion o paréntesis es inválido.
    if (preg_match('/[^\d\s()-]/', $valor) || !preg_match('/^\d{10}$/', normalizar_telefono($valor))) {
        return 'Ingresá un teléfono válido de 10 dígitos.';
    }
    return '';
}

/* =====================================================================
   Etapa 4 (Profesionales): especialidades
   ===================================================================== */

/**
 * Lista cerrada de especialidades que aparece en el desplegable.
 * Para agregar una nueva alcanza con sumarla acá.
 */
function especialidades(): array
{
    return ['Corte y peinado', 'Coloración', 'Tratamientos'];
}

/** Valida que la especialidad sea una de la lista. Devuelve el error o ''. */
function validar_especialidad(string $valor): string
{
    if ($valor === '') {
        return 'Elegí una especialidad.';
    }
    // in_array con true compara de forma estricta: el valor tiene que ser EXACTAMENTE uno de la lista.
    // Un usuario podría enviar cualquier texto manipulando el formulario, por eso se valida en el servidor.
    if (!in_array($valor, especialidades(), true)) {
        return 'Elegí una especialidad de la lista.';
    }
    return '';
}
