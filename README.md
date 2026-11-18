# APSY — Sistema de gestión de peluquería

Aplicación web desarrollada en PHP y MySQL para administrar la actividad de una peluquería. Permite gestionar clientes, profesionales y turnos, y consultar la información general desde un panel principal.

## Requisitos

- XAMPP instalado.
- Apache y MySQL (o MariaDB) disponibles desde el panel de control de XAMPP.
- Un navegador web.

No se requiere ejecutar `npm install` ni instalar dependencias mediante Composer para poner en marcha esta versión del proyecto.

## Instalación en XAMPP

1. Copiar la carpeta del proyecto dentro de `htdocs` de XAMPP. Por ejemplo:

   `C:\xampp\htdocs\apsy-v2\`

2. Abrir el panel de control de XAMPP e iniciar los módulos **Apache** y **MySQL**.

3. Abrir phpMyAdmin desde:

   `http://localhost/phpmyadmin/`

4. Entrar en la pestaña **Importar**.

5. Seleccionar el archivo `sql/apsy.sql` que se encuentra dentro de la carpeta del proyecto.

6. Ejecutar la importación y esperar el mensaje de finalización.

   El script crea la base de datos `apsy`, sus tablas y registros de prueba. No es necesario crear la base manualmente.

7. Abrir el sistema desde el navegador:

   `http://localhost/apsy-v2/`

   Si la carpeta tiene otro nombre, reemplazar `apsy-v2` en la dirección por el nombre utilizado.

## Configuración de la base de datos

La conexión se configura en `includes/conexion.php`. Los valores predeterminados de esta versión son:

| Parámetro | Valor |
|---|---|
| Servidor | `localhost` |
| Usuario | `root` |
| Contraseña | vacía |
| Base de datos | `apsy` |

Estos valores corresponden a la configuración predeterminada habitual de XAMPP. Si el entorno utiliza otras credenciales, modificar las constantes `DB_HOST`, `DB_USUARIO`, `DB_CLAVE` y `DB_NOMBRE` en `includes/conexion.php` para que coincidan con la instalación local.

La conexión utiliza MySQLi y establece el juego de caracteres `utf8mb4`.

## Usuario de prueba

La base de datos incluye un usuario de prueba para ingresar al sistema:

| Campo | Dato |
|---|---|
| Usuario | `mariana.lopez` |
| Contraseña | `apsy2026` |

Se recomienda utilizar estas credenciales únicamente en el entorno de demostración o evaluación.

## Funcionalidades principales

- Inicio y cierre de sesión.
- Panel principal con información de los turnos.
- Alta, consulta, modificación y baja lógica de clientes.
- Alta, consulta, modificación y baja lógica de profesionales.
- Gestión de turnos y consulta de disponibilidad horaria.
- Catálogo de servicios cargado mediante el script SQL.

## Estructura general

```text
apsy-v2/
├── assets/
│   ├── css/
│   ├── img/
│   └── js/
├── includes/
│   ├── auth.php
│   ├── conexion.php
│   ├── funciones.php
│   ├── turnos_funciones.php
│   └── componentes compartidos de la interfaz
├── parciales/
│   └── modales y paginación
├── sql/
│   └── apsy.sql
├── clientes.php
├── contacto.php
├── dashboard.php
├── disponibilidad.php
├── index.php
├── login.php
├── logout.php
├── profesionales.php
├── recuperar.php
├── turnos.php
└── README.md
```

## Importante: volver a importar la base de datos

El archivo `sql/apsy.sql` contiene instrucciones `DROP TABLE` para eliminar las tablas existentes y crearlas nuevamente, además de cargar datos de prueba.

**Volver a importar este archivo reemplaza la estructura y los datos actuales de esas tablas.** No hacerlo en una instalación que contenga información que se quiera conservar, salvo que previamente se haya realizado una copia de seguridad.

## Recuperación de acceso

La pantalla de recuperación no permite cambiar la contraseña directamente. Si se olvida la contraseña, se debe solicitar el restablecimiento al responsable del sistema.
