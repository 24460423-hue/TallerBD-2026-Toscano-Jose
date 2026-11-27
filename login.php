<?php
declare(strict_types=1);

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/sesion.php';

iniciarSesionTbd();
header('Cache-Control: no-store');

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($metodo, ['GET', 'HEAD', 'POST'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD, POST');
    exit;
}

$usuario = '';
$error = '';

if ($metodo === 'POST') {
    $token = $_POST['csrf'] ?? null;
    if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        $error = 'El formulario caducó. Vuelve a cargar la página e inténtalo de nuevo.';
    }
}

try {
    if ($error === '') {
        $pdo = conectarBaseDeDatos();
        if (usuarioAutenticadoTbd($pdo) !== null) {
            header('Location: index.php', true, 303);
            exit;
        }

        if ($metodo === 'POST') {
            $usuario = is_string($_POST['usuario'] ?? null) ? trim($_POST['usuario']) : '';
            $contrasena = is_string($_POST['contrasena'] ?? null) ? $_POST['contrasena'] : '';

            if ($usuario === '' || strlen($usuario) > 200 || $contrasena === '' || strlen($contrasena) > 4096) {
                $error = 'Usuario o contraseña incorrectos.';
            } else {
                $consulta = $pdo->prepare(
                    'SELECT id_usuario, contrasena_hash FROM usuarios WHERE usuario = :usuario AND activo = 1'
                );
                $consulta->execute(['usuario' => $usuario]);
                $cuenta = $consulta->fetch();

                // Realiza la misma comprobación aunque el nombre de usuario no exista.
                $hash = $cuenta === false
                    ? '$2y$10$p8IxhfkIKRRgkgL95T2/g.p6zNe6YToT1TdgOHYe3zQFCTZe1AKle'
                    : $cuenta['contrasena_hash'];
                $valida = password_verify($contrasena, $hash);
                if (!$valida || $cuenta === false) {
                    $error = 'Usuario o contraseña incorrectos.';
                } elseif (!session_regenerate_id(true)) {
                    http_response_code(503);
                    $error = 'No se pudo iniciar la sesión. Inténtalo de nuevo.';
                } else {
                    $_SESSION = [
                        'id_usuario_tbd' => (int) $cuenta['id_usuario'],
                        'csrf' => bin2hex(random_bytes(32)),
                    ];
                    header('Location: index.php', true, 303);
                    exit;
                }
            }
        }
    }
} catch (RuntimeException $excepcion) {
    http_response_code(503);
    $error = 'No se pudo conectar con la base de datos. Inténtalo más tarde.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | Préstamos de equipo</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        .contenido-acceso { max-width: 28rem; }
        .formulario-acceso { grid-template-columns: minmax(0, 1fr); margin-top: 1rem; }
    </style>
</head>
<body>
    <header>
        <h1>Sistema de préstamos de equipo</h1>
    </header>
    <main class="contenido-acceso">
        <section aria-labelledby="titulo-acceso">
            <h2 id="titulo-acceso">Iniciar sesión</h2>
            <?php if ($error !== ''): ?>
                <p role="alert"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <?php endif; ?>
            <form class="formulario formulario-acceso" method="post" action="login.php">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="campo">
                    <label for="usuario">Usuario</label>
                    <input type="text" name="usuario" id="usuario" autocomplete="username" maxlength="50"
                        value="<?= htmlspecialchars($usuario, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
                </div>
                <div class="campo">
                    <label for="contrasena">Contraseña</label>
                    <input type="password" name="contrasena" id="contrasena" autocomplete="current-password" required>
                </div>
                <div class="acciones-formulario">
                    <button type="submit">Iniciar sesión</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
