# MotorFix · Sistema de gestión integral para taller mecánico

Práctica intermodular de **Acceso a Datos (AD)** y **Desarrollo de Interfaces (DI)**, 2º DAM.

Aplicación web para el taller "MotorFix": gestión de usuarios y roles, clientes y vehículos, citas y reparaciones, y facturación. Arquitectura MVC con frontend en HTML/CSS/JS que se comunica con el backend PHP solo mediante `fetch()` (sin recargas de página) y respuestas JSON.

## Tecnologías

- **Backend:** PHP 8 orientado a objetos, PDO (patrón Singleton, sentencias preparadas), MySQL/MariaDB.
- **Frontend:** HTML5, CSS3, JavaScript (fetch + async/await).
- **Gestión:** Git/GitHub y tablero Kanban en Jira.

## Estructura del proyecto

```
motorfix/
├── backend/
│   └── model/
│       └── conectionBD.php  # Conexión PDO con patrón Singleton
└── database/
    └── motorfix.sql         # Script para crear la BD en MySQL (se importa en phpMyAdmin)
```

## Puesta en marcha

1. Clonar el repositorio dentro de la carpeta del servidor web (por ejemplo `htdocs` de XAMPP).
2. Importar la base de datos:
   ```bash
   mysql -u root -p < database/motorfix.sql
   ```
   (o desde phpMyAdmin → Importar → `database/motorfix.sql`).
3. Si tu usuario o contraseña de MySQL no son `root` / `1234`, cámbialos en `backend/model/conectionBD.php`.

## Ramas

- `main`: versión estable, lo que se entrega.
- `develop`: integración del trabajo de cada semana.
- Una rama por tarea, por ejemplo `base-datos` o `conexion-bd`.

## Planificación

| Semana | Fechas | Objetivo |
|---|---|---|
| 1 | 19–25 oct | Repositorio Git, tablero Jira con backlog, modelo E-R y conexión Singleton |
| 2 | 26 oct–1 nov | Modelos `Usuario`, `Cliente`, `Vehiculo` con CRUD, BCrypt, maquetación de Login/Registro/Cliente |
| 3 | 2–8 nov | Modelos `Cita`, `Factura`, `auth_sw.php` y control de acceso, login/registro con `fetch()` |
| 4 | 9–15 nov | Panel de cliente: vehículos y cita previa en línea |
| 5 | 16–22 nov | Paneles de Oficina y Mecánico, facturación automática (IVA 21 %) |
| 6 | 23–30 nov | Pruebas, refactorización, memoria y entrega final |

## Autor

Hilario Sánchez Pérez · 2º DAM
