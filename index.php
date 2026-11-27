<?php
declare(strict_types=1);

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/sesion.php';

iniciarSesionTbd();
try {
    $usuarioActual = requerirUsuarioTbd(conectarBaseDeDatos());
} catch (RuntimeException $error) {
    http_response_code(503);
    exit('No se pudo conectar con MariaDB. Intenta de nuevo.');
}
$nombreActual = htmlspecialchars($usuarioActual['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$perfilActual = $usuarioActual['perfil'] === 'administrador' ? 'Administrador' : 'Consulta';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Préstamos de equipo</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <header>
        <h1>Sistema de préstamos de equipo</h1>
        <div class="sesion-actual">
            <p><?= $nombreActual ?> · <?= $perfilActual ?></p>
            <form action="logout.php" method="post" class="acciones-formulario">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit">Cerrar sesión</button>
            </form>
        </div>
        <nav aria-label="Menú principal">
            <ul>
                <li><a href="#inicio">Inicio</a></li>
                <li><a href="#equipos">Equipos</a></li>
                <li><a href="#solicitantes">Solicitantes</a></li>
                <li><a href="#prestamos">Préstamos y devoluciones</a></li>
                <li><a href="#consultas">Consultas</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section id="inicio">
            <h2>Inicio</h2>
            <p>Consulta la disponibilidad de los equipos y registra préstamos y devoluciones.</p>
        </section>

        <section id="equipos">
            <h2>Equipos</h2>
            <p><a href="equipos.php">Registrar y consultar equipos</a></p>
        </section>

        <section id="solicitantes">
            <h2>Solicitantes</h2>
            <p><a href="solicitantes.php">Registrar y consultar solicitantes</a></p>
        </section>

        <section id="prestamos">
            <h2>Préstamos y devoluciones</h2>
            <h3 id="titulo-registro-prestamo">Registrar préstamo</h3>
            <form class="formulario" aria-labelledby="titulo-registro-prestamo">
                <div class="campo">
                    <label for="equipo-prestamo">Equipo disponible</label>
                    <select id="equipo-prestamo" name="id_equipo" required>
                        <option value="">Selecciona un equipo</option>
                        <option value="1">INV-001 · Computadora portátil</option>
                        <option value="2">INV-002 · Proyector</option>
                    </select>
                </div>

                <div class="campo">
                    <label for="solicitante-prestamo">Solicitante</label>
                    <select id="solicitante-prestamo" name="id_solicitante" required>
                        <option value="">Selecciona un solicitante</option>
                        <option value="1">María Pérez (ejemplo)</option>
                        <option value="2">Luis Ramírez (ejemplo)</option>
                    </select>
                </div>

                <div class="campo">
                    <label for="fecha-prestamo">Fecha y hora de entrega</label>
                    <input id="fecha-prestamo" name="fecha_prestamo" type="datetime-local" required>
                </div>

                <div class="campo">
                    <label for="fecha-prevista">Fecha y hora previstas de devolución</label>
                    <input id="fecha-prevista" name="fecha_prevista" type="datetime-local" required>
                </div>

                <div class="acciones-formulario">
                    <button type="submit" disabled>Guardar préstamo</button>
                    <p>La lista usa datos de ejemplo. El guardado se habilitará al conectar la base de datos.</p>
                </div>
            </form>

            <h3 id="titulo-registro-devolucion">Registrar devolución</h3>
            <form class="formulario" aria-labelledby="titulo-registro-devolucion">
                <div class="campo">
                    <label for="prestamo-devolucion">Préstamo activo</label>
                    <select id="prestamo-devolucion" name="id_prestamo" required>
                        <option value="">Selecciona un préstamo</option>
                        <option value="101">#101 · INV-003 · Kit de electrónica · María Pérez</option>
                        <option value="102">#102 · INV-004 · Monitor · Luis Ramírez</option>
                    </select>
                </div>

                <div class="campo">
                    <label for="fecha-devolucion">Fecha y hora de devolución</label>
                    <input id="fecha-devolucion" name="fecha_devolucion" type="datetime-local" required>
                </div>

                <div class="campo">
                    <label for="condicion-devolucion">Condición física al regresar</label>
                    <input id="condicion-devolucion" name="condicion_devolucion" type="text" maxlength="200" required>
                </div>

                <div class="campo">
                    <label for="estado-devolucion">Estado del equipo tras la devolución</label>
                    <select id="estado-devolucion" name="estado_equipo" required>
                        <option value="">Selecciona un estado</option>
                        <option value="disponible">Disponible</option>
                        <option value="mantenimiento">Mantenimiento</option>
                    </select>
                </div>

                <div class="acciones-formulario">
                    <button type="submit" disabled>Guardar devolución</button>
                    <p>Los préstamos son de ejemplo. El guardado se habilitará al conectar la base de datos.</p>
                </div>
            </form>
        </section>

        <section id="consultas">
            <h2>Consultas</h2>
            <p>Datos de ejemplo. La búsqueda del historial se habilitará al conectar la base de datos.</p>

            <details class="consulta" open>
                <summary id="consulta-inventario">Inventario general</summary>
                <div class="tabla-contenedor" role="region" aria-labelledby="consulta-inventario" tabindex="0">
                    <table aria-labelledby="consulta-inventario">
                        <thead>
                            <tr>
                                <th scope="col">Inventario</th>
                                <th scope="col">Equipo</th>
                                <th scope="col">Categoría</th>
                                <th scope="col">Condición</th>
                                <th scope="col">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>INV-001</td>
                                <td>Computadora portátil</td>
                                <td>Computadoras</td>
                                <td>Buena</td>
                                <td>Disponible</td>
                            </tr>
                            <tr>
                                <td>INV-002</td>
                                <td>Proyector</td>
                                <td>Proyectores</td>
                                <td>Buena</td>
                                <td>Disponible</td>
                            </tr>
                            <tr>
                                <td>INV-003</td>
                                <td>Kit de electrónica</td>
                                <td>Accesorios</td>
                                <td>Buena</td>
                                <td>Prestado</td>
                            </tr>
                            <tr>
                                <td>INV-004</td>
                                <td>Monitor</td>
                                <td>Accesorios</td>
                                <td>Buena</td>
                                <td>Prestado</td>
                            </tr>
                            <tr>
                                <td>INV-005</td>
                                <td>Computadora portátil</td>
                                <td>Computadoras</td>
                                <td>Batería dañada</td>
                                <td>Mantenimiento</td>
                            </tr>
                            <tr>
                                <td>INV-006</td>
                                <td>Proyector</td>
                                <td>Proyectores</td>
                                <td>Lámpara agotada</td>
                                <td>Baja</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </details>

            <details class="consulta">
                <summary id="consulta-disponibles">Equipos disponibles</summary>
                <div class="tabla-contenedor" role="region" aria-labelledby="consulta-disponibles" tabindex="0">
                    <table aria-labelledby="consulta-disponibles">
                        <thead>
                            <tr>
                                <th scope="col">Inventario</th>
                                <th scope="col">Equipo</th>
                                <th scope="col">Categoría</th>
                                <th scope="col">Condición</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>INV-001</td>
                                <td>Computadora portátil</td>
                                <td>Computadoras</td>
                                <td>Buena</td>
                            </tr>
                            <tr>
                                <td>INV-002</td>
                                <td>Proyector</td>
                                <td>Proyectores</td>
                                <td>Buena</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </details>

            <details class="consulta">
                <summary id="consulta-activos">Préstamos activos</summary>
                <div class="tabla-contenedor" role="region" aria-labelledby="consulta-activos" tabindex="0">
                    <table aria-labelledby="consulta-activos">
                        <thead>
                            <tr>
                                <th scope="col">Préstamo</th>
                                <th scope="col">Equipo</th>
                                <th scope="col">Solicitante</th>
                                <th scope="col">Entrega</th>
                                <th scope="col">Devolución prevista</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>101</td>
                                <td>INV-003 · Kit de electrónica</td>
                                <td>María Pérez</td>
                                <td>17/11/2026 10:00</td>
                                <td>19/11/2026 14:00</td>
                            </tr>
                            <tr>
                                <td>102</td>
                                <td>INV-004 · Monitor</td>
                                <td>Luis Ramírez</td>
                                <td>19/11/2026 11:30</td>
                                <td>25/11/2026 16:00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </details>

            <details class="consulta">
                <summary id="consulta-vencidos">Préstamos vencidos</summary>
                <div class="tabla-contenedor" role="region" aria-labelledby="consulta-vencidos" tabindex="0">
                    <table aria-labelledby="consulta-vencidos">
                        <thead>
                            <tr>
                                <th scope="col">Préstamo</th>
                                <th scope="col">Equipo</th>
                                <th scope="col">Solicitante</th>
                                <th scope="col">Entrega</th>
                                <th scope="col">Devolución prevista</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>101</td>
                                <td>INV-003 · Kit de electrónica</td>
                                <td>María Pérez</td>
                                <td>17/11/2026 10:00</td>
                                <td>19/11/2026 14:00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </details>

            <details class="consulta">
                <summary id="consulta-historial">Historial de préstamos</summary>
                <form class="formulario filtro-historial" aria-label="Filtros del historial">
                    <div class="campo">
                        <label for="inventario-historial">Número de inventario</label>
                        <input id="inventario-historial" name="numero_inventario" type="text" maxlength="50">
                    </div>
                    <div class="campo">
                        <label for="solicitante-historial">Nombre del solicitante</label>
                        <input id="solicitante-historial" name="nombre_solicitante" type="text" maxlength="150">
                    </div>
                    <div class="acciones-formulario">
                        <button type="submit" disabled>Buscar en el historial</button>
                    </div>
                </form>
                <div class="tabla-contenedor" role="region" aria-labelledby="consulta-historial" tabindex="0">
                    <table aria-labelledby="consulta-historial">
                        <thead>
                            <tr>
                                <th scope="col">Préstamo</th>
                                <th scope="col">Equipo</th>
                                <th scope="col">Solicitante</th>
                                <th scope="col">Entrega</th>
                                <th scope="col">Devolución real</th>
                                <th scope="col">Condición al regresar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>99</td>
                                <td>INV-001 · Computadora portátil</td>
                                <td>María Pérez</td>
                                <td>13/11/2026 10:00</td>
                                <td>16/11/2026 12:30</td>
                                <td>Buena</td>
                            </tr>
                            <tr>
                                <td>100</td>
                                <td>INV-002 · Proyector</td>
                                <td>Luis Ramírez</td>
                                <td>16/11/2026 09:00</td>
                                <td>18/11/2026 15:30</td>
                                <td>Buena</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </details>
        </section>
    </main>
</body>
</html>
