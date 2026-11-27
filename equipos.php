<?php
declare(strict_types=1);

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/sesion.php';

iniciarSesionTbd();

function escapar(string|int $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function volverAEquipos(string $mensaje, bool $correcto, array $datos = []): never
{
    $_SESSION['aviso_equipos'] = [
        'mensaje' => $mensaje,
        'correcto' => $correcto,
        'datos' => $datos,
    ];
    header('Location: equipos.php', true, 303);
    exit;
}

$aviso = $_SESSION['aviso_equipos'] ?? null;
unset($_SESSION['aviso_equipos']);

$campos = [
    'numero_inventario' => ['Número de inventario', 50, true],
    'descripcion' => ['Descripción', 200, true],
    'condicion' => ['Condición física', 200, true],
    'marca' => ['Marca', 60, false],
    'modelo' => ['Modelo', 80, false],
];
$datos = array_fill_keys(array_keys($campos), '') + ['id_categoria' => ''];
$categorias = [];
$equipos = [];
$errorServidor = null;

try {
    $pdo = conectarBaseDeDatos();
    $usuarioActual = requerirUsuarioTbd($pdo);
    $esAdministrador = $usuarioActual['perfil'] === 'administrador';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$esAdministrador) {
            http_response_code(403);
            exit('No tienes permiso para realizar esta operación.');
        }

        $csrf = $_POST['csrf'] ?? '';
        if (!is_string($csrf) || !hash_equals($_SESSION['csrf'], $csrf)) {
            http_response_code(403);
            exit('La sesión del formulario expiró. Vuelve a Equipos y recarga la página.');
        }

        $errorFormulario = null;
        foreach ($campos as $campo => [$etiqueta, $limite, $obligatorio]) {
            $valor = $_POST[$campo] ?? '';
            if (!is_string($valor) || !mb_check_encoding($valor, 'UTF-8')) {
                $errorFormulario ??= "El campo «$etiqueta» debe contener texto válido.";
                continue;
            }

            $valor = trim($valor);
            $datos[$campo] = mb_substr($valor, 0, $limite, 'UTF-8');
            if ($obligatorio && $valor === '') {
                $errorFormulario ??= "El campo «$etiqueta» es obligatorio.";
            } elseif (mb_strlen($valor, 'UTF-8') > $limite) {
                $errorFormulario ??= "El campo «$etiqueta» admite hasta $limite caracteres.";
            }
        }

        $categoria = filter_var($_POST['id_categoria'] ?? '', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 4294967295],
        ]);
        if ($categoria === false) {
            $errorFormulario ??= 'Selecciona una categoría.';
        } else {
            $datos['id_categoria'] = (string) $categoria;
        }

        if ($errorFormulario !== null) {
            volverAEquipos($errorFormulario, false, $datos);
        }

        // MariaDB asigna los valores iniciales de estado y activo.
        $insertar = $pdo->prepare(
            'INSERT INTO equipos (numero_inventario, descripcion, marca, modelo, condicion, id_categoria)
             VALUES (:numero_inventario, :descripcion, :marca, :modelo, :condicion, :id_categoria)'
        );
        try {
            $insertar->execute([
                'numero_inventario' => $datos['numero_inventario'],
                'descripcion' => $datos['descripcion'],
                'marca' => $datos['marca'] === '' ? null : $datos['marca'],
                'modelo' => $datos['modelo'] === '' ? null : $datos['modelo'],
                'condicion' => $datos['condicion'],
                'id_categoria' => $categoria,
            ]);
        } catch (PDOException $error) {
            $mensaje = match ($error->errorInfo[1] ?? 0) {
                1062 => 'Ese número de inventario ya está registrado.',
                1452 => 'La categoría seleccionada ya no está disponible.',
                default => 'MariaDB no pudo registrar el equipo. Intenta de nuevo.',
            };
            volverAEquipos($mensaje, false, $datos);
        }
        volverAEquipos('Equipo registrado.', true);
    }

    if ($esAdministrador) {
        $categorias = $pdo->query(
            'SELECT id_categoria, nombre FROM categorias ORDER BY nombre'
        )->fetchAll();
    }
    $equipos = $pdo->query(
        'SELECT e.numero_inventario, e.descripcion, e.condicion, e.estado, e.activo, c.nombre AS categoria
         FROM equipos AS e
         INNER JOIN categorias AS c ON c.id_categoria = e.id_categoria
         ORDER BY e.numero_inventario'
    )->fetchAll();
} catch (RuntimeException $error) {
    http_response_code(503);
    $errorServidor = 'No se pudo consultar MariaDB. Revisa la configuración y que las tablas estén instaladas.';
}

if (!empty($aviso['datos'])) {
    $datos = $aviso['datos'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipos | Préstamos de equipo</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <header>
        <h1>Equipos</h1>
        <nav aria-label="Navegación del módulo">
            <ul><li><a href="index.php">Volver a inicio</a></li></ul>
        </nav>
    </header>

    <main>
        <?php if ($errorServidor !== null): ?>
            <section aria-labelledby="titulo-error">
                <h2 id="titulo-error">Conexión a MariaDB</h2>
                <p role="alert"><?= escapar($errorServidor) ?></p>
            </section>
        <?php else: ?>
            <?php if ($esAdministrador): ?>
            <section aria-labelledby="titulo-registro-equipo">
                <h2 id="titulo-registro-equipo">Registrar equipo</h2>
                <?php if ($aviso !== null): ?>
                    <p role="<?= $aviso['correcto'] ? 'status' : 'alert' ?>"><?= escapar($aviso['mensaje']) ?></p>
                <?php endif; ?>
                <?php if ($categorias === []): ?>
                    <p>Agrega una categoría en MariaDB para poder registrar equipos.</p>
                <?php endif; ?>

                <form class="formulario" action="equipos.php" method="post">
                    <input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>">
                    <div class="campo">
                        <label for="numero-inventario">Número de inventario</label>
                        <input id="numero-inventario" name="numero_inventario" type="text" maxlength="50" value="<?= escapar($datos['numero_inventario']) ?>" required>
                    </div>
                    <div class="campo">
                        <label for="categoria-equipo">Categoría</label>
                        <select id="categoria-equipo" name="id_categoria" required>
                            <option value="">Selecciona una categoría</option>
                            <?php foreach ($categorias as $categoriaFila): ?>
                                <option value="<?= escapar($categoriaFila['id_categoria']) ?>"
                                    <?= (string) $categoriaFila['id_categoria'] === $datos['id_categoria'] ? 'selected' : '' ?>>
                                    <?= escapar($categoriaFila['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo">
                        <label for="descripcion-equipo">Descripción</label>
                        <input id="descripcion-equipo" name="descripcion" type="text" maxlength="200" value="<?= escapar($datos['descripcion']) ?>" required>
                    </div>
                    <div class="campo">
                        <label for="condicion-equipo">Condición física</label>
                        <input id="condicion-equipo" name="condicion" type="text" maxlength="200" value="<?= escapar($datos['condicion']) ?>" required>
                    </div>
                    <div class="campo">
                        <label for="marca-equipo">Marca (opcional)</label>
                        <input id="marca-equipo" name="marca" type="text" maxlength="60" value="<?= escapar($datos['marca']) ?>">
                    </div>
                    <div class="campo">
                        <label for="modelo-equipo">Modelo (opcional)</label>
                        <input id="modelo-equipo" name="modelo" type="text" maxlength="80" value="<?= escapar($datos['modelo']) ?>">
                    </div>
                    <div class="acciones-formulario">
                        <button type="submit" <?= $categorias === [] ? 'disabled' : '' ?>>Guardar equipo</button>
                    </div>
                </form>
            </section>
            <?php endif; ?>

            <section aria-labelledby="titulo-lista-equipos">
                <h2 id="titulo-lista-equipos">Equipos registrados</h2>
                <?php if (!$esAdministrador): ?>
                    <p>Tu perfil permite consultar estos registros.</p>
                <?php endif; ?>
                <?php if ($equipos === []): ?>
                    <p>Aún no hay equipos registrados.</p>
                <?php else: ?>
                    <div class="consulta tabla-contenedor" tabindex="0" role="region" aria-labelledby="titulo-lista-equipos">
                        <table>
                            <thead>
                                <tr>
                                    <th scope="col">Número de inventario</th>
                                    <th scope="col">Descripción</th>
                                    <th scope="col">Categoría</th>
                                    <th scope="col">Condición física</th>
                                    <th scope="col">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($equipos as $equipo): ?>
                                    <tr>
                                        <td><?= escapar($equipo['numero_inventario']) ?></td>
                                        <td><?= escapar($equipo['descripcion']) ?></td>
                                        <td><?= escapar($equipo['categoria']) ?></td>
                                        <td><?= escapar($equipo['condicion']) ?></td>
                                        <td><?= escapar((int) $equipo['activo'] === 1 ? ucfirst($equipo['estado']) : 'Baja') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
