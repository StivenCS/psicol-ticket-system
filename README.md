# PsiCol Tickets app — Sistema de Gestión de Incidentes

Plataforma fullstack para la gestión de solicitudes e incidentes. Permite registrar, asignar y hacer seguimiento a tickets.
---

## Arquitectura

```
psicol-ticket-system/
├── backend/          # Laravel 13 + JWT + Spatie Permissions
├── frontend/         # Quasar Framework (Vue 3 + TypeScript)
├── docker/
│   ├── nginx/        # Configuración del servidor web
│   └── php/          # Dockerfile PHP-FPM 8.3
└── docker-compose.yml
```

### Stack

| Capa       | Tecnología                          |
|------------|-------------------------------------|
| Backend    | PHP 8.3 / Laravel 13                |
| Auth       | JWT (`php-open-source-saver/jwt-auth`) |
| Permisos   | Spatie Laravel Permission v7        |
| Frontend   | Quasar 2 / Vue 3 / TypeScript       |
| Estado     | Pinia                               |
| HTTP       | Axios (interceptores centralizados) |
| Base datos | MySQL 8                             |
| Servidor   | Nginx + PHP-FPM                     |
| Entorno    | Docker Compose                      |

---

## Levantar el entorno

### Requisitos

- Docker Desktop instalado y corriendo

### Inicio

```bash
# Clonar y levantar
docker compose up --build

# En otra terminal — primera vez
docker compose exec app php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="permission-migrations"
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

### URLs

| Servicio       | URL                          |
|----------------|------------------------------|
| Frontend       | http://localhost:5173        |
| API Backend    | http://localhost:8000/api    |
| MySQL (host)   | localhost:3307               |

---

## Roles y credenciales de prueba

Todos los usuarios de prueba tienen contraseña: **`password`**

| Email                   | Rol     | Permisos                                      |
|-------------------------|---------|-----------------------------------------------|
| admin@test.com          | admin   | CRUD completo + eliminar tickets              |
| ana.admin@test.com      | admin   | CRUD completo + eliminar tickets              |
| carlos@test.com         | agente  | Crear, ver todos, editar — no puede eliminar  |
| laura@test.com          | agente  | Crear, ver todos, editar — no puede eliminar  |
| juan@test.com           | cliente | Solo ve y gestiona sus propios tickets        |
| maria@test.com          | cliente | Solo ve y gestiona sus propios tickets        |

---

## API — Endpoints disponibles

### Autenticación

| Método | Ruta               | Descripción              | Auth |
|--------|--------------------|--------------------------|------|
| POST   | /api/auth/login    | Iniciar sesión           | No   |
| GET    | /api/auth/me       | Usuario autenticado      | Sí   |
| POST   | /api/auth/logout   | Cerrar sesión            | Sí   |
| POST   | /api/auth/refresh  | Refrescar token JWT      | Sí   |

### Tickets

| Método | Ruta                  | Descripción              | Roles permitidos    |
|--------|-----------------------|--------------------------|---------------------|
| GET    | /api/tickets          | Listar tickets           | Todos               |
| POST   | /api/tickets          | Crear ticket             | Todos               |
| GET    | /api/tickets/{id}     | Ver ticket               | Todos*              |
| PUT    | /api/tickets/{id}     | Actualizar ticket        | admin, agente       |
| DELETE | /api/tickets/{id}     | Eliminar ticket          | admin               |

> *`cliente` solo puede ver sus propios tickets.

### Dashboard

| Método | Ruta                    | Descripción              | Auth |
|--------|-------------------------|--------------------------|------|
| GET    | /api/dashboard/stats    | Estadísticas generales   | Sí   |

### Usuarios

| Método | Ruta        | Descripción              | Auth |
|--------|-------------|--------------------------|------|
| GET    | /api/users  | Listado de usuarios      | Sí   |

---

## Ejecutar tests

```bash
docker compose exec app php artisan test
```

Los tests cubren autenticación, CRUD de tickets con control de roles y el dashboard.

---

## Datos de prueba

El seeder crea:
- **20 usuarios** (2 admin, 6 agentes, 12 clientes)
- **550 tickets** con distribución realista de estados y prioridades

```bash
docker compose exec app php artisan migrate:fresh --seed
```

---

## Variables de entorno relevantes

```env
DB_HOST=mysql            # Hostname del contenedor MySQL
JWT_SECRET=...           # Clave para firmar tokens JWT
JWT_TTL=60               # Duración del token en minutos
FRONTEND_URL=http://localhost:5173   # Origen permitido en CORS
CACHE_STORE=file         # Cache en disco (no DB) para dev
```
