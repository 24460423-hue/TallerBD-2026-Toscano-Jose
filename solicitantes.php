<?php
declare(strict_types=1);

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/sesion.php';

const PREFIJO_PRUEBA = 'PRUEBA-';

iniciarSesionTbd();

function escapar(string|int $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function datoTexto(mixed $valor): string
{
    return is_string($valor) ? trim($valor) : '';
}

function idValido(mixed $valor): int|false
{
    return filter_var($valor, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 4294967295],
    ]);
}

function volverAlFormulario(string $mensaje, bool $correcto, array $datos = [], int|false $id = false): never
{
    $_SESSION['aviso_solicitantes'] = [
        'mensaje' => $mensaje,
        'correcto' => $correcto,
        'datos' => $datos,
    ];

    $destino = $id === false ? 'solicitantes.php' : "solicitantes.php?editar=$id";
    header("Location: $destino", true, 303);
    exit;
}

$aviso = $_SESSION['aviso_solicitantes'] ?? null;
unset($_SESSION['aviso_solicitantes']);

$departamentos = [];
$solicitantes = [];
$datos = ['numero_institucional' => '', 'nombre' => '', 'id_departamento' => ''];
$idEditar = false;
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
            exit('La sesión del formulario expiró. Vuelve a Solicitantes y recarga la página.');
        }

        $accion = $_POST['accion'] ?? '';
        $id = idValido($_POST['id_solicitante'] ?? null);

        if (!in_array($accion, ['crear', 'editar', 'eliminar'], true)
            || ($accion !== 'crear' && $id === false)) {
            volverAlFormulario('La operación solicitada no es válida.', false);
        }

        $idFormulario = false;
        if ($accion === 'editar') {
            $idFormulario = $id;
        }

        try {
            if ($accion === 'eliminar') {
                // La eliminación es solo para pruebas. La clave foránea protege el historial.
                $consulta = $pdo->prepare(
                    'DELETE FROM solicitantes
                     WHERE id_solicitante = :id AND BINARY numero_institucional LIKE :prefijo'
                );
                $consulta->execute(['id' => $id, 'prefijo' => PREFIJO_PRUEBA . '%']);
                if ($consulta->rowCount() === 0) {
                    volverAlFormulario('No se encontró un solicitante de prueba que se pueda eliminar.', false);
                }
                volverAlFormulario('Solicitante eliminado.', true);
            }

            $departamento = idValido($_POST['id_departamento'] ?? null);
            $datos = [
                'numero_institucional' => datoTexto($_POST['numero_institucional'] ?? null),
                'nombre' => datoTexto($_POST['nombre'] ?? null),
                'id_departamento' => $departamento === false ? '' : (string) $departamento,
            ];

            if ($datos['numero_institucional'] === '' || mb_strlen($datos['numero_institucional']) > 30
                || $datos['nombre'] === '' || mb_strlen($datos['nombre']) > 150 || $departamento === false) {
                volverAlFormulario('Revisa los campos: el número y el nombre son obligatorios y debes elegir un departamento.', false, $datos, $idFormulario);
            }

            // Los valores del formulario se envían como parámetros de PDO.
            if ($accion === 'crear') {
                $consulta = $pdo->prepare(
                    'INSERT INTO solicitantes (numero_institucional, nombre, id_departamento)
                     VALUES (:numero_institucional, :nombre, :id_departamento)'
                );
                $consulta->execute($datos);
                volverAlFormulario('Solicitante registrado.', true);
            }

            $consulta = $pdo->prepare(
                'UPDATE solicitantes
                 SET numero_institucional = :numero_institucional,
                     nombre = :nombre,
                     id_departamento = :id_departamento
                 WHERE id_solicitante = :id'
            );
            $consulta->execute($datos + ['id' => $id]);

            if ($consulta->rowCount() === 0) {
                $existe = $pdo->prepare('SELECT id_solicitante FROM solicitantes WHERE id_solicitante = :id');
                $existe->execute(['id' => $id]);
                if (!$existe->fetch()) {
                    volverAlFormulario('El solicitante ya no existe.', false);
                }
            }

            volverAlFormulario('Solicitante actualizado.', true);
        } catch (PDOException $error) {
            $mensaje = match ($error->errorInfo[1] ?? 0) {
                1062 => 'Ese número institucional ya está registrado.',
                1451 => 'El solicitante tiene préstamos asociados y debe conservarse en el historial.',
                1452 => 'El departamento seleccionado ya no está disponible.',
                default => 'MariaDB no pudo guardar el cambio. Intenta de nuevo.',
            };
            volverAlFormulario($mensaje, false, $datos, $idFormulario);
        }
    }

    if ($esAdministrador && isset($_GET['editar'])) {
        $idEditar = idValido($_GET['editar']);
        if ($idEditar === false) {
            volverAlFormulario('Identificador de solicitante inválido.', false);
        }

        $consulta = $pdo->prepare(
            'SELECT numero_institucional, nombre, id_departamento
             FROM solicitantes WHERE id_solicitante = :id'
        );
        $consulta->execute(['id' => $idEditar]);
        $registro = $consulta->fetch();
        if (!$registro) {
            volverAlFormulario('El solicitante ya no existe.', false);
        }
        $datos = $registro;
    }

    // La lista muestra el nombre del departamento mediante la relación del DDL.
    $solicitantes = $pdo->query(
        'SELECT s.id_solicitante, s.numero_institucional, s.nombre, d.nombre AS departamento
         FROM solicitantes AS s
         INNER JOIN departamentos AS d ON d.id_departamento = s.id_departamento
         ORDER BY s.nombre, s.id_solicitante'
    )->fetchAll();
    if ($esAdministrador) {
        $departamentos = $pdo->query(
            'SELECT id_departamento, nombre FROM departamentos ORDER BY nombre'
        )->fetchAll();
    }
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
    <title>Solicitantes | Préstamos de equipo</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <header>
        <h1>Solicitantes</h1>
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
            <section aria-labelledby="titulo-formulario">
                <h2 id="titulo-formulario"><?= $idEditar === false ? 'Registrar solicitante' : 'Editar solicitante' ?></h2>
                <?php if ($aviso !== null): ?>
                    <p role="<?= $aviso['correcto'] ? 'status' : 'alert' ?>"><?= escapar($aviso['mensaje']) ?></p>
                <?php endif; ?>
                <?php if ($departamentos === []): ?>
                    <p>No hay departamentos registrados. Agrega uno en MariaDB para continuar.</p>
                <?php endif; ?>

                <form class="formulario" action="solicitantes.php" method="post">
                    <input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>">
                    <input type="hidden" name="accion" value="<?= $idEditar === false ? 'crear' : 'editar' ?>">
                    <?php if ($idEditar !== false): ?>
                        <input type="hidden" name="id_solicitante" value="<?= escapar($idEditar) ?>">
                    <?php endif; ?>
                    <div class="campo">
                        <label for="nombre">Nombre completo</label>
                        <input id="nombre" name="nombre" type="text" maxlength="150" value="<?= escapar($datos['nombre']) ?>" required>
                    </div>
                    <div class="campo">
                        <label for="numero-institucional">Número institucional</label>
                        <input id="numero-institucional" name="numero_institucional" type="text" maxlength="30" value="<?= escapar($datos['numero_institucional']) ?>" required>
                    </div>
                    <div class="campo">
                        <label for="departamento">Departamento</label>
                        <select id="departamento" name="id_departamento" required>
                            <option value="">Selecciona un departamento</option>
                            <?php foreach ($departamentos as $departamentoFila): ?>
                                <option value="<?= escapar($departamentoFila['id_departamento']) ?>"
                                    <?= (string) $departamentoFila['id_departamento'] === (string) $datos['id_departamento'] ? 'selected' : '' ?>>
                                    <?= escapar($departamentoFila['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="acciones-formulario">
                        <button type="submit" <?= $departamentos === [] ? 'disabled' : '' ?>>
                            <?= $idEditar === false ? 'Guardar solicitante' : 'Guardar cambios' ?>
                        </button>
                        <?php if ($idEditar !== false): ?>
                            <a href="solicitantes.php">Cancelar edición</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>
            <?php endif; ?>

            <section aria-labelledby="titulo-lista">
                <h2 id="titulo-lista">Solicitantes registrados</h2>
                <?php if ($esAdministrador): ?>
                    <p>Solo se pueden eliminar registros de prueba cuyo número institucional empieza con <code><?= escapar(PREFIJO_PRUEBA) ?></code>.</p>
                <?php else: ?>
                    <p>Tu perfil permite consultar estos registros.</p>
                <?php endif; ?>
                <?php if ($solicitantes === []): ?>
                    <p>Aún no hay solicitantes registrados.</p>
                <?php else: ?>
                    <div class="consulta tabla-contenedor" tabindex="0" role="region" aria-labelledby="titulo-lista">
                        <table>
                            <thead>
                                <tr>
                                    <th scope="col">Número institucional</th>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Departamento</th>
                                    <?php if ($esAdministrador): ?>
                                        <th scope="col">Acciones</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($solicitantes as $solicitante): ?>
                                    <tr>
                                        <td><?= escapar($solicitante['numero_institucional']) ?></td>
                                        <td><?= escapar($solicitante['nombre']) ?></td>
                                        <td><?= escapar($solicitante['departamento']) ?></td>
                                        <?php if ($esAdministrador): ?>
                                            <td>
                                                <a href="solicitantes.php?editar=<?= escapar($solicitante['id_solicitante']) ?>"
                                                    aria-label="Editar a <?= escapar($solicitante['nombre']) ?>">Editar</a>
                                                <?php if (str_starts_with($solicitante['numero_institucional'], PREFIJO_PRUEBA)): ?>
                                                    <details>
                                                        <summary>Eliminar</summary>
                                                        <form class="acciones-formulario" action="solicitantes.php" method="post">
                                                            <input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>">
                                                            <input type="hidden" name="accion" value="eliminar">
                                                            <input type="hidden" name="id_solicitante" value="<?= escapar($solicitante['id_solicitante']) ?>">
                                                            <button type="submit">Confirmar eliminación</button>
                                                        </form>
                                                    </details>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
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
