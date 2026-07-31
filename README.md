# Backend - API Control de Gastos

API REST desarrollada en Laravel 11 con Sanctum para el Sistema de Control de Gastos Personales.

## Prerrequisitos

- PHP >= 8.1
- Composer
- MySQL (Laragon)
- Node.js & NPM

## Instalación Local (Windows - Laragon)

1. Abrir Laragon > Iniciar Apache y MySQL
2. Abrir terminal (PowerShell o CMD) en la raíz del proyecto:

```powershell
composer install
copy .env.example .env
php artisan key:generate
```

Configurar base de datos en `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=control_gastos
DB_USERNAME=root
DB_PASSWORD=
```

```powershell
php artisan migrate
php artisan storage:link
php artisan serve
```

La API estará disponible en `http://localhost:8000/api`.

## Endpoints API

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | /api/register | Registro de usuario |
| POST | /api/login | Inicio de sesión |
| POST | /api/login/google | Login con Google |
| POST | /api/logout | Cerrar sesión |
| GET | /api/profile | Obtener perfil |
| PUT | /api/profile | Actualizar perfil |
| GET | /api/transactions | Listar transacciones |
| POST | /api/transactions | Crear transacción |
| GET | /api/categories | Listar categorías |
| POST | /api/categories | Crear categoría |
| GET | /api/savings-goals | Listar metas de ahorro |
| POST | /api/savings-goals | Crear meta de ahorro |
