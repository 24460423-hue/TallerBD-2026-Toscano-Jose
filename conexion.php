<?php
declare(strict_types=1);

/**
 * Lee la configuración del servidor. El archivo INI queda fuera del sitio web
 * y del repositorio para que las credenciales no se publiquen.
 */
function conectarBaseDeDatos(): PDO
{
    $ruta = getenv('TBD_DB_CONFIG') ?: '/etc/tbd/database.ini';

    if (!is_readable($ruta)) {
        throw new RuntimeException('No se encontró la configuración de MariaDB.');
    }

    $config = @parse_ini_file($ruta, false, INI_SCANNER_RAW);
    if (!is_array($config)) {
        throw new RuntimeException('La configuración de MariaDB no es válida.');
    }

    foreach (['host', 'nombre', 'usuario', 'contrasena'] as $clave) {
        if (!isset($config[$clave]) || !is_string($config[$clave]) || $config[$clave] === '') {
            throw new RuntimeException('Faltan datos de conexión a MariaDB.');
        }
    }

    $dsn = "mysql:host={$config['host']};dbname={$config['nombre']};charset=utf8mb4";

    return new PDO($dsn, $config['usuario'], $config['contrasena'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
