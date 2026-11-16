# TallerBD-2026-Toscano-Jose
Proyecto integrador de Taller de Base de Datos: sistema de préstamos de equipo tecnológico.

## Propósito

Sistema web para controlar préstamos de equipo tecnológico.
Permitirá registrar equipos y solicitantes, realizar préstamos
y devoluciones y consultar disponibilidad e historial.

## Modelo de datos

La base de datos prestamos_equipo contiene seis tablas:

- categorias: tipos de equipo.
- departamentos: áreas de los solicitantes.
- solicitantes: personas que reciben equipos.
- equipos: información e identificación de cada equipo.
- usuarios: cuentas de acceso a la aplicación.
- prestamos: información de las entregas y devoluciones.

Una categoría tiene muchos equipos y un departamento tiene
muchos solicitantes. Cada préstamo relaciona un equipo,
un solicitante y los usuarios responsables de la entrega
y la recepción.

## Avance actual

Se crearon las seis tablas con sus claves primarias,
claves foráneas y restricciones.
La estructura inicial se encuentra en base_datos.sql.
