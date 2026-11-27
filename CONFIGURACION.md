# Tercer avance: solicitantes y registro de equipos

El módulo `solicitantes.php` permite registrar, consultar y editar solicitantes. Para demostrar `DELETE`, también permite eliminar registros con un número institucional que empiece por `PRUEBA-`, siempre que no tengan préstamos asociados. El listado muestra el nombre del departamento mediante un `JOIN`.

El módulo `equipos.php` permite registrar y consultar equipos. Las categorías se cargan de MariaDB y el número de inventario es único. La base asigna el estado disponible y deja el equipo activo al registrarlo.

## Preparar la base de datos

1. Selecciona la base asignada en MariaDB.
2. Si está vacía, ejecuta en ella una copia de `base_datos.sql` a partir del primer `CREATE TABLE`. Deja fuera `CREATE DATABASE` y `USE prestamos_equipo` para que el script trabaje sobre la base seleccionada.
3. Agrega al menos un departamento para poder registrar solicitantes. En una base de prueba puedes ejecutar:

   ```sql
   INSERT INTO departamentos (nombre) VALUES ('Soporte técnico');
   INSERT INTO categorias (nombre) VALUES ('Computadoras'), ('Proyectores'), ('Accesorios');
   ```

## Configurar PHP

En Debian, crea `/etc/tbd/database.ini` fuera del directorio de publicación. La cuenta que ejecuta PHP debe poder leer el archivo. En la VM de pruebas, Apache se ejecuta como `www-data`. El contenido sigue este formato:

```ini
host = "localhost"
nombre = "NOMBRE_DE_LA_BASE"
usuario = "USUARIO_DE_MARIADB"
contrasena = "CONTRASENA_DE_MARIADB"
```

Usa `localhost` cuando MariaDB y PHP corran en la misma máquina. Si el servidor ya proporciona un archivo de configuración en otra ruta, puedes indicarla con la variable de entorno `TBD_DB_CONFIG`. Las credenciales reales se guardan únicamente en la configuración del servidor.

## Probar en el navegador

1. Publica `index.html`, `index.php`, `estilos.css`, `conexion.php`, `sesion.php`, `login.php`, `logout.php`, `solicitantes.php` y `equipos.php` juntos en un directorio servido por Apache con PHP 8, `pdo_mysql` y `mbstring`.
2. Desde el inicio, abre **Solicitantes** y pulsa **Registrar y consultar solicitantes**.
3. Registra un solicitante con número institucional `PRUEBA-001`, un nombre ficticio y un departamento. Comprueba que el departamento se muestra por su nombre.
4. Pulsa **Editar**, cambia el nombre o departamento y guarda.
5. Pulsa **Eliminar** y confirma. El registro de prueba debe desaparecer del listado.
6. Regresa a **Equipos → Registrar y consultar equipos**. Captura un número de inventario libre, la categoría, la descripción y la condición física. Pulsa **Guardar equipo** y comprueba que aparece en el listado.

Este avance se ejecuta en el entorno de prácticas. Los formularios de préstamos y devoluciones y las tablas de la sección Consultas siguen mostrando la interfaz inicial.

## Crear cuentas de la plataforma

Las cuentas se guardan en la tabla `usuarios`. El perfil `administrador` puede guardar cambios y el perfil `consulta` puede ver los listados. Las contraseñas de estas cuentas se guardan en forma de hash.

Desde Debian, conserva `crear_usuario.php` y `conexion.php` juntos en un directorio privado. Con Bash, ejecuta lo siguiente desde ese directorio:

```bash
sudo -v
read -rs -p 'Contraseña de la plataforma: ' clave_tbd
printf '\n'
printf '%s\n' "$clave_tbd" | sudo php crear_usuario.php admin19 'Administrador de prácticas' administrador
unset clave_tbd
```

Para crear una cuenta de lectura, usa un nombre de usuario distinto y sustituye el último argumento por `consulta`. Abre `index.php` desde el navegador e inicia sesión con la cuenta creada.
