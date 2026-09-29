# Sistema Contable

Sistema web de contabilidad basica implementado en **PHP + PDO + PostgreSQL + Bootstrap 5**, disenado para funcionar en XAMPP local.

Cumple con los requisitos de la practica: Libro Diario con partida doble, mayorizacion automatica, catalogo de cuentas jerarquico precargado y los estados financieros basicos.

---

## 1. Requisitos

- XAMPP (Apache + PHP 8.0 o superior)
- PostgreSQL 18 instalado como servicio de Windows (`postgresql-x64-18`)
- Navegador web moderno (Chrome, Edge, Firefox)
- No se requieren librerias externas: Bootstrap y Bootstrap Icons se cargan por CDN

> **MySQL/MariaDB no se utiliza.** No es necesario iniciar el boton *MySQL* del panel de XAMPP.

## 2. Instalacion

El proyecto ya esta instalado y la base de datos ya fue creada. Para usarlo:

1. Inicia **Apache** desde el panel de control de XAMPP.
2. Abre en el navegador:
   `http://localhost/SistemaContable/`

El servicio **PostgreSQL** se inicia automaticamente con Windows, por lo que no hay nada mas
que ejecutar.

### Reinstalar la base de datos (opcional)

Si necesitas volver a crear tablas y catalogo desde cero, entra a:

`http://localhost/SistemaContable/instalar.php`

El instalador es idempotente: se puede ejecutar varias veces sin duplicar el catalogo.

## 3. Configuracion

Archivo: `config/config.php`

| Constante    | Valor por defecto | Descripcion                    |
|--------------|-------------------|--------------------------------|
| `DB_DRIVER`  | `pgsql`           | Driver PDO                     |
| `DB_HOST`    | `127.0.0.1`       | Servidor de base de datos     |
| `DB_PORT`    | `5433`            | Puerto de PostgreSQL 18        |
| `DB_NAME`    | `contabilidad`    | Nombre de la base de datos     |
| `DB_USER`    | `postgres`        | Usuario de la base de datos    |
| `DB_PASS`    | *(vacio)*         | Clave del usuario              |
| `MAX_LINEAS` | `50`              | Maximo de partidas por asiento |

La extension `pdo_pgsql` debe estar habilitada en `C:\xampp\php\php.ini`:

```ini
extension=pdo_pgsql
extension=pgsql
```

Los datos de la empresa (nombre, carrera, asignatura y profesor) se editan desde
**Ajustes** dentro del sistema y se guardan en la tabla `empresa`.

## 4. Estructura del proyecto

```
config/config.php          Datos de conexion y ajustes generales
database/01_esquema.sql    Creacion de tablas
database/02_catalogo.sql   Catalogo de cuentas (401 cuentas)
includes/functions.php     Funciones auxiliares, saldos y validaciones
includes/header.php        Encabezado, menu y mensajes
includes/footer.php        Pie de pagina
assets/css/estilos.css     Estilos propios y formato de reportes
assets/js/asiento.js       Selector jerarquico de cuentas y validacion
api/cuentas.php            Busqueda de cuentas en JSON (para el formulario)
instalar.php               Instalador de la base de datos
index.php                  Pagina de inicio (resumen)
diario/                    Libro Diario: listar, nuevo, editar, ver, guardar, eliminar
mayor/index.php            Libro Mayor
catalogo/index.php         Catalogo de cuentas
ajustes/index.php          Datos de la empresa
reportes/                  Balance General, Estado de Resultados,
                           Balance de Comprobacion, Indice de Cuentas
```

## 5. Base de datos

Motor: **PostgreSQL 18**, base `contabilidad` en el puerto `5433`.

| Tabla              | Contenido                                              |
|--------------------|---------------------------------------------------------|
| `catalogo_cuentas` | Codigo, nombre, nivel, tipo, naturaleza, hoja          |
| `asientos`         | Cabecera: numero, fecha, concepto, documento, totales   |
| `asiento_partidas` | Detalle: cuenta, debe, haber por linea                  |
| `saldos_cuentas`   | Saldos acumulados por cuenta (mayorizacion)             |
| `empresa`          | Datos institutional                                     |

La mayorizacion es **automatico**: cada alta, edicion o eliminacion recalcula los saldos
de las cuentas afectadas. Los reportes se obtienen en tiempo real sumando las partidas,
por lo que el Balance de Comprobacion siempre debe mostrar
*PARTIDA DOBLE CORRECTA*.

El catalogo incluye **401 cuentas**, de las cuales **279 son cuentas de detalle** aptas para
registrar movimientos. El ultimo asiento de cada grupo se actualiza mediante el trigger
`trg_asientos_updated`.

## 6. Uso

1. **Libro Diario -> Registrar asiento**: elige el grupo y luego la cuenta de detalle,
   indica los importes en Debito o Credito. El sistema valida que el total de Debito sea
   igual al total de Credito antes de guardar.
2. Los asientos se pueden consultar, editar y eliminar desde el listado del Libro Diario.
3. **Libro Mayor**: muestra el detalle de movimientos de cada cuenta con saldo acumulado,
   y un resumen de saldos de todas las cuentas con movimiento.
4. **Reportes**: Balance General, Estado de Resultados, Balance de Comprobacion e
   Indice de Cuentas. Todos aceptan un rango de fechas, se pueden imprimir y exportar a CSV.

## 7. Criterios contables implementados

- **Partida doble**: suma de debitos = suma de creditos, validada en el navegador y en el servidor.
- **Cuentas de detalle**: no se permite registrar movimientos en grupos o cuentas de nivel superior.
- **Clasificacion**: `1 Activo = 2 Pasivo + 3 Capital` y `5 Ingresos - 4 Costos y gastos`.
- **Naturaleza de las cuentas**: deudora o acreedora; el saldo se presenta en la columna
  correspondiente.

## 8. Seguridad

- Consultas preparadas (PDO) en todo el acceso a datos.
- Token **CSRF** en todos los formularios que modifican informacion.
- Escape de salida con `htmlspecialchars` para evitar XSS.
- Sesiones de PHP para los mensajes flash.

## 9. Solucion de problemas

| Sintoma | Causa probable | Solucion |
|---------|----------------|----------|
| `could not find driver` | `pdo_pgsql` no habilitada | Activar la extension en `C:\xampp\php\php.ini` y reiniciar Apache |
| `Connection refused` en puerto `5433` | Servicio PostgreSQL detenido | Iniciar `postgresql-x64-18` desde *Servicios* de Windows |
| `password authentication failed` | `pg_hba.conf` exige clave | Configurar `trust` para `127.0.0.1` en `C:\Program Files\PostgreSQL\18\data\pg_hba.conf` |
| Pagina en blanco | PHP con errores ocultos | Revisar `C:\xampp\php\logs\php_error.log` |
