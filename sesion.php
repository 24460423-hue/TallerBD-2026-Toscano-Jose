<?php
declare(strict_types=1);

function iniciarSesionTbd(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        $ruta = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        session_name('TBDSESION');
        session_start([
            'use_strict_mode' => true,
            'use_only_cookies' => true,
            'cookie_path' => rtrim($ruta, '/') . '/',
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
    }
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function usuarioAutenticadoTbd(PDO $pdo): ?array
{
    $id = $_SESSION['id_usuario_tbd'] ?? null;
    if (!is_int($id) || $id < 1) {
        return null;
    }

    // Verificamos la cuenta actual para aplicar los cambios de perfil o de vigencia.
    $consulta = $pdo->prepare(
        'SELECT id_usuario, nombre, perfil
         FROM usuarios
         WHERE id_usuario = :id AND activo = 1'
    );
    $consulta->execute(['id' => $id]);
    $usuario = $consulta->fetch();
    if (!$usuario) {
        unset($_SESSION['id_usuario_tbd']);
        return null;
    }
    return $usuario;
}

function requerirUsuarioTbd(PDO $pdo): array
{
    $usuario = usuarioAutenticadoTbd($pdo);
    if ($usuario === null) {
        header('Location: login.php', true, 303);
        exit;
    }
    return $usuario;
}
