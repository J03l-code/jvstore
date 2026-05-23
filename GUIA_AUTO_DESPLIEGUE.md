# Guía de Despliegue Automático en Hostinger

## Problema: Error "could not read Username"
Si Hostinger te da un error al intentar conectar el repositorio, es porque **es privado** y necesita permiso.
La solución es poner tu **Usuario** y un **Token** directamente en la URL.

---

## Paso 1: Generar un Token en GitHub
1.  Ve a tu cuenta de GitHub -> **Settings** (arriba a la derecha).
2.  Baja hasta el final del menú izquierdo -> **Developer settings**.
3.  Clic en **Personal access tokens** -> **Tokens (classic)**.
4.  **Generate new token (classic)**.
5.  Ponle un nombre (ej. "Hostinger").
6.  **Importante**: Marca la casilla **`repo`** (Full control of private repositories).
7.  Dale a **Generate token**.
8.  **Copia el token** (se ve como `ghp_...`). ¡No lo pierdas!

## Paso 2: Conectar en Hostinger con la URL Especial
1.  En Hostinger -> **Git**.
2.  En el campo **Repositorio**, NO pegues la URL normal. Pega esta estructura:

```text
https://TU_USUARIO:TU_TOKEN@github.com/J03l-code/impordispacec.git
```

*   Reemplaza `TU_USUARIO` con tu usuario de GitHub.
*   Reemplaza `TU_TOKEN` con el código `ghp_...` que acabas de copiar.

**Ejemplo real:**
`https://J03l-code:ghp_AbCdEf123456@github.com/J03l-code/impordispacec.git`

3.  **Rama**: `main`
4.  **Directorio**: `public_html` (Asegúrate de que esté vacía).
5.  Clic en **Crear**.

---

## Paso 3: Activar Webhook (Actualización Automática)
1.  Una vez creado en Hostinger, busca la **Webhook URL** y copiala.
2.  Ve a tu repo en GitHub -> **Settings** -> **Webhooks** -> **Add webhook**.
3.  Pega la URL en **Payload URL**.
4.  Content type: `application/json`.
5.  Clic en **Add webhook**.

---

## Paso 4: Configuración (Verificación)
Como solicitaste, **hemos subido los archivos de configuración** al repositorio.
El código está programado para detectar si está en Hostinger y usar tus credenciales de producción.

1.  Asegúrate de que en el archivo `includes/config.php` y `conexion.php` (que ahora están en GitHub) las credenciales de la sección `else { ... PRODUCCIÓN ... }` sean las correctas de tu base de datos en Hostinger.
2.  Si cambias la contraseña de la base de datos en Hostinger, recuerda actualizarla en tu código local y hacer un `git push`.
