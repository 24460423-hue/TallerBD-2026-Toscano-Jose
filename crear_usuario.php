<?php
declare(strict_types=1);

// Ejecutar desde la terminal de Debian. Esta operación no se ofrece por HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/conexion.php';

if ($argc !== 4) {
    fwrite(STDERR, "Uso: php crear_usuario.php usuario 'Nombre completo' administrador|consulta\n");
    exit(1);
}

[, $usuario, $nombre, $perfil] = $argv;
$nombre = trim($nombre);
if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/D', $usuario) ||
    $nombre === '' || mb_strlen($nombre, 'UTF-8') > 150 ||
    !in_array($perfil, ['administrador', 'consulta'], true)) {
    fwrite(STDERR, "Revisa el usuario, el nombre y el perfil.\n");
    exit(1);
}

// La contraseña llega por entrada estándar para evitar guardarla en los argumentos.
$contrasena = rtrim(fgets(STDIN) ?: '', "\r\n");
if (strlen($contrasena) < 12 || strlen($contrasena) > 72 || str_contains($contrasena, "\0")) {
    fwrite(STDERR, "La contraseña debe tener entre 12 y 72 bytes.\n");
    exit(1);
}

try {
    $pdo = conectarBaseDeDatos();
    $consulta = $pdo->prepare(
        'INSERT INTO usuarios (nombre, usuario, contrasena_hash, perfil)
         VALUES (:nombre, :usuario, :hash, :perfil)'
    );
    $consulta->execute([
        'nombre' => $nombre,
        'usuario' => $usuario,
        'hash' => password_hash($contrasena, PASSWORD_DEFAULT),
        'perfil' => $perfil,
    ]);
    echo "Cuenta creada.\n";
} catch (RuntimeException $error) {
    fwrite(STDERR, "No se pudo crear la cuenta. Verifica la conexión y que el usuario no exista.\n");
    exit(1);
}
