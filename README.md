<div align="center">

# Incidencias – Migración (Backend + UI)

Aplicación para el registro, seguimiento y exportación de incidencias de colaboración y ofimática (Microsoft 365/Office) con flujo de reporte para colaboradores y tablero operativo para consultores.

</div>

## Visión General

- Stack: Laravel `12.x` (PHP `>=8.2`), Vite `7`, Tailwind `4` (assets en `resources/`), UI simple en `public/ui`, y generación de Excel con `phpoffice/phpspreadsheet`.
- Dominios clave: Incidencias, Adjuntos, Consultores, Consulta de colaboradores (API externa).
- Flujos principales:
  - Colaborador reporta incidencias desde `public/ui/report.php` con validaciones, adjuntos e historial.
  - Consultor autentica (`/api/login`) y gestiona incidencias desde `public/ui/dashboard.php`.
  - Exportación a Excel filtrable (`/api/incidencias/export/excel`).
  - Adjuntos almacenados en `storage/app/attachments/{incident_id}` y servidos vía API.

## Arquitectura

- API REST bajo `routes/api.php` con controladores en `App\Http\Controllers\Api`.
- UI pública (sin Blade) en `public/ui/` con páginas `report.php`, `login.php`, `dashboard.php` y JS/estilos asociados.
- Middleware `ApiTokenMiddleware` expone un guard con alias `api.token` y valida encabezado `X-API-TOKEN`.
- Modelos:
  - `Incident`: entidad principal con datos del colaborador, estado, asignación y seguimiento.
  - `Attachment`: adjuntos (archivos) vinculados a una incidencia.
  - `Consultant`: usuarios operativos con `api_token` para autenticación.
- Servicio: `IncidentExportService` para generar Excel (usa plantilla `.xlsm` si existe en `storage/templates/incidencias_template.xlsm`).
- Rutas web (`routes/web.php`) redireccionan a la UI estática para URLs amigables (`/reportar`, `/consultor/login`, `/consultor/incidencias`).

## Requisitos

- `PHP >= 8.2`
- `Composer`
- `Node.js >= 18` y `npm`
- Base de datos: por defecto `sqlite` (puede alternarse a MySQL/MariaDB/PG/SQLServer).
- Extensiones recomendadas para exportación: `ext-zip`, `ext-gd`.

## Instalación Rápida

1) Instalar dependencias PHP y preparar entorno:

```
composer install
copy .env.example .env
php artisan key:generate
```

2) Configurar base de datos. Por defecto se usa `sqlite`:

```
type NUL > database\database.sqlite
php artisan migrate --force
php artisan db:seed
```

Para MySQL/MariaDB/PG/SQLServer, actualiza variables en `.env` y ejecuta `php artisan migrate --force` + `php artisan db:seed`.

3) Instalar y construir assets (opcional para `resources/`):

```
npm install
npm run build
```

4) Levantar entorno de desarrollo:

```
php artisan serve
```

Opcional (todo-en-uno concurrente):

```
composer run-script dev
```

## Configuración

Variables relevantes en `.env`:

- `APP_ENV`, `APP_DEBUG`, `APP_URL`.
- `DB_CONNECTION` y parámetros de base de datos (por defecto `sqlite`).
- `FILESYSTEM_DISK=local`: almacenamiento de adjuntos en `storage/app`.
- `SESSION_DRIVER=database` y `QUEUE_CONNECTION=database`.
- API externa de colaboradores:
  - `EMPLOYEE_API_BASE_URL=https://empleados.temalitoclean.com/api/employee.php`
  - `EMPLOYEE_API_VERIFY_SSL=false` (poner `true` en producción si el certificado es válido).

## Base de Datos y Modelos

- `incidents`
  - Empleado: `dni_type`, `dni_number`, `full_name`, `area_name`, `corporate_email`.
  - Registro: `category`, `apps` (array JSON), `description`, `urgency` (`Crítico/Alto/Medio/Bajo`).
  - Equipo: `hostname`, `os`, `office_version`, `first_time`, `started_at`.
  - Seguimiento: `status` (por defecto `Pendiente`), `assigned_to_id`, `consultant_notes`, `resolution_date`, `solution_applied`.
  - Índices para filtros (`status, category, urgency, area_name, assigned_to_id, started_at`).

- `attachments`
  - Campos: `incident_id`, `filename`, `path`, `mime`, `size`.
  - Almacenamiento físico en `storage/app/attachments/{incident_id}`.

- `consultants`
  - Campos: `name`, `email` (único), `password` (hash), `api_token` (único, nullable), `area_name`.

Seeders:

```
php artisan db:seed
```

Crea un usuario de ejemplo:

- Email: `consultor@temalitoclean.com`
- Password: `Tema2025@Migration`

IMPORTANTE: Cambia estas credenciales en producción.

## API REST

Base: `GET/POST/PUT /api/*`

Autenticación de consultor:

- `POST /api/login`
  - Body JSON: `{ "email": "consultor@temalitoclean.com", "password": "..." }`
  - Respuesta: `{ token: string, consultant: { id, name, email, area_name } }`
  - Para endpoints protegidos usa encabezado `X-API-TOKEN: <token>`.

- `POST /api/logout` (protegido) invalida el token actual.

Incidencias:

- `GET /api/incidencias`
  - Paginado (20 por página). Incluye `assignedTo` y `attachments`.
  - Filtros soportados: `status`, `category`, `urgency`, `area_name`, `assigned_to_id`, `app` (busca en el JSON `apps`), `dni_type`, `dni_number`, búsqueda parcial `full_name_like`, `dni_number_like`, `date_from`, `date_to`, y `page`.

- `GET /api/incidencias/{id}` devuelve el detalle.

- `POST /api/incidencias` (registro/edición desde UI de reporte)
  - `multipart/form-data`.
  - Validación: `dni_type` requerido, `dni_number` string, `full_name` requerido, `corporate_email` email, `category` requerido, `description` requerido, `urgency` en `{Crítico,Alto,Medio,Bajo}`, `attachments.*` max `10MB`.
  - Mapeo de `dni_type`: `1`→`DNI`, `2`→`CE`.
  - Edición puntual: incluir `edit_existing_id=<id>` para actualizar una incidencia específica del mismo colaborador.
  - Reglas de adjuntos al editar: si se suben nuevas imágenes, se reemplazan las imágenes previas del incidente.

- `PUT /api/incidencias/{id}` (protegido por token)
  - Body JSON opcional: `status` en `{Pendiente,En revisión,Resuelto,Cerrado}`, `assigned_to_id` (id de `consultants`), `consultant_notes`, `resolution_date`, `solution_applied`.
  - Si no envías `assigned_to_id`, se autoasigna al consultor autenticado.

Adjuntos:

- `GET /api/attachments/{id}`
  - Stream del archivo con `Content-Type` original. Útil para ver imágenes inline.
  - Nota: Actualmente no está protegido por token (ver sección Seguridad).

Exportación:

- `GET /api/incidencias/export/excel` (protegido)
  - Respeta los mismos filtros que `GET /api/incidencias`.
  - Si existe `storage/templates/incidencias_template.xlsm`, se usa como plantilla; de lo contrario, se genera un Excel por defecto.

## UI Pública

- `public/ui/report.php`
  - Flujo: buscar colaborador (`/api/employees/lookup`), prellenar datos, listar incidencias del colaborador, bloquear campos de equipo si ya existen, registrar/editar incidencia con adjuntos.
  - Validación de `hostname`: formatos admitidos `TL-LAP-123` o `PCR-LAP12345`.
  - Visualización de imagen: doble clic en el ícono de ojo abre un modal con el adjunto.

- `public/ui/login.php`
  - Autenticación de consultores; guarda token y datos en `localStorage`.

- `public/ui/dashboard.php`
  - Lista con filtros, paginación, detalle en modal. Usa el token en `X-API-TOKEN`.

## Almacenamiento de Adjuntos

- Guardados mediante `Storage::put` en `storage/app/attachments/{incident_id}`.
- Servidos vía `GET /api/attachments/{id}` con el `mime` original.
- No requieren `php artisan storage:link` (se usa streaming desde storage privado).

## Seguridad

- Autenticación por token para acciones de consultor (`PUT /incidencias`, `GET /incidencias/export/excel`, `POST /logout`).
- `GET /api/attachments/{id}` actualmente es público. Recomendaciones:
  - Moverlo al grupo protegido por token si los adjuntos contienen información sensible.
  - Alternativamente, emitir URLs firmadas de corta duración.
- Cambia las credenciales del seeder en producción y exige HTTPS.

## Desarrollo y Pruebas

- Ejecutar servidor: `php artisan serve`.
- Vite dev (assets de `resources/`): `npm run dev`.
- Pruebas: `php artisan test`.
- Logs: configurados vía `LOG_CHANNEL=stack` y `LOG_LEVEL` en `.env`.

## Despliegue

- Configura `.env` acorde al entorno (DB, `EMPLOYEE_API_*`, `APP_URL`).
- Ejecuta migraciones y semillas: `php artisan migrate --force && php artisan db:seed`.
- Construye assets si usas `resources/`: `npm run build`.
- Configura un proceso para colas si se habilitan (actualmente no hay jobs críticos).

## Resolución de Problemas

- Error exportando Excel: asegúrate de tener `ext-zip` y permisos de escritura en `storage/`.
- Adjuntos no visualizan: verifica que el `id` exista y que el archivo esté presente en `storage/app`.
- API externa de empleados: si falla por certificado en desarrollo, usa `EMPLOYEE_API_VERIFY_SSL=false`.

## Roadmap (Sugerencias)

- Protección de `GET /api/attachments/{id}` o URLs firmadas.
- Filtros avanzados por rango de fechas en UI y asignación masiva.
- Auditoría de cambios (quién cambió estado/nota/solución).
- KPI de tiempos (SLA) y reporte agregados.

## Licencia

Este proyecto usa licencia `MIT`.