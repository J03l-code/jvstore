# 🔑 Guía: Activar Login con Google en JVStore

## ¿Por qué necesitas esto?
El login con Google permite que los clientes accedan a su cuenta (y vean sus pedidos) con un clic, sin recordar contraseñas.

---

## Paso 1 — Crear credenciales en Google Cloud

1. Ve a [console.cloud.google.com](https://console.cloud.google.com/)
2. Crea un proyecto nuevo (ej: **JVStore**)
3. En el menú lateral: **APIs y servicios → Credenciales**
4. Clic en **+ Crear credenciales → ID de cliente de OAuth 2.0**
5. Tipo de aplicación: **Aplicación web**
6. Nombre: `JVStore Login`
7. En **URIs de redireccionamiento autorizados** agrega:
   ```
   https://tudominio.com/login.php?action=google_callback
   ```
   > ⚠️ Reemplaza `tudominio.com` con tu dominio real de Hostinger
8. Clic en **Crear** → Copia el **Client ID** y **Client Secret**

---

## Paso 2 — Configurar en JVStore

Abre el archivo `includes/config.php` y reemplaza la sección de Google (línea 60 aprox):

```php
define('GOOGLE_CLIENT_ID',     getenv('GOOGLE_CLIENT_ID')     ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
```

Por tus propias claves:

```php
define('GOOGLE_CLIENT_ID',     'AQUI_TU_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'AQUI_TU_CLIENT_SECRET');
```

> ✅ Una vez configurado y guardado, el botón "Continuar con Google" aparecerá automáticamente en tu página de login en vivo.

---

## Paso 3 — Migrar la base de datos (¡Muy importante!)

Como creamos nuevas funciones (el carrito guardado y el login de Google), necesitamos añadir columnas a tu base de datos de producción.

Ejecuta este script **UNA SOLA VEZ** visitándolo desde tu navegador:

```
https://tudominio.com/setup_db.php?confirm=jvstore2026
```
*(Cambia tudominio.com por tu web real)*

Esto añadirá las columnas `carrito`, `google_id`, etc. a tu BD automáticamente.
> ⚠️ **Elimina `setup_db.php` de tus archivos después de ejecutarlo por seguridad.**

---

## Paso 4 — Probar

1. Visita tu tienda en vivo.
2. Clic en el botón para Iniciar Sesión.
3. Deberías ver el diseño renovado con el botón azul de Google.
4. Inicia sesión. Si todo salió bien, te llevará a **Mi Cuenta**, tu nuevo Dashboard Premium de cliente donde podrás ver tus pedidos.
