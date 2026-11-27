<?php
declare(strict_types=1);

require_once __DIR__ . '/sesion.php';

header('Cache-Control: no-store');
header('Content-Type: text/plain; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Usa el botón Cerrar sesión de la plataforma.');
}

iniciarSesionTbd();
$token = $_POST['csrf'] ?? null;
if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
    http_response_code(403);
    exit('El formulario caducó. Vuelve a la plataforma e inténtalo de nuevo.');
}

$parametros = session_get_cookie_params();
$_SESSION = [];
session_destroy();

if (ini_get('session.use_cookies')) {
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $parametros['path'],
        'domain' => $parametros['domain'],
        'secure' => $parametros['secure'],
        'httponly' => $parametros['httponly'],
        'samesite' => $parametros['samesite'],
    ]);
}

header('Location: login.php', true, 303);
exit;
