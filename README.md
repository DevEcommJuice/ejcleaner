# EcommJuice - Cache & DB Cleaner (ejcleaner)

Módulo profesional para el mantenimiento automático de PrestaShop. Este módulo permite limpiar periódicamente los directorios de caché (Symfony/Smarty) y vaciar las tablas de estadísticas nativas que suelen saturar la base de datos.

## 🚀 Características

- **Limpieza de Caché Inteligente**: Detecta la versión de PrestaShop y limpia los directorios correspondientes:
  - **PS 1.7 & 8.x**: `/var/cache/prod` y `/var/cache/dev`.
  - **PS 1.6**: `/cache/smarty/compile` y `/cache/smarty/cache`.
- **Mantenimiento de Base de Datos**: Ejecuta un `TRUNCATE` (operación de alto rendimiento) en las tablas:
  - `ps_guest`
  - `ps_connections`
  - `ps_connections_source`
- **Seguridad**: Endpoint protegido mediante un token alfanumérico único generado durante la instalación.
- **Optimizado para Warehouse**: Compatible con la regeneración de caché dinámica de temas avanzados.

## 🛠 Instalación

1. Descarga el archivo `ejcleaner.zip`.
2. Súbelo a tu tienda a través del gestor de módulos en el Back Office.
3. Haz clic en **Instalar**.
4. Accede a la configuración para obtener tu **URL de Cron personalizada**.

## 📅 Automatización en Plesk

Para automatizar la limpieza diaria, sigue estos pasos en tu panel Plesk:

1. Ve a **Sitios web y dominios** > **Tareas programadas**.
2. Haz clic en **Añadir tarea**.
3. Selecciona **Obtener una URL**.
4. En el campo **URL de la tarea**, pega la URL que copiaste del módulo.
5. Configura la programación (Ejemplo para ejecutar a las 04:00 AM diariamente):
   - Estilo cron: `0 4 * * *`
6. Guarda los cambios.

## ⚠️ Notas Técnicas

- **Rendimiento**: Se recomienda ejecutar la tarea en horas de bajo tráfico, ya que tras vaciar la caché, la primera visita al frontend tendrá que regenerar los archivos compilados, lo que puede aumentar ligeramente el tiempo de respuesta inicial.
- **Integridad de datos**: El vaciado de la tabla `ps_guest` puede desconectar carritos de usuarios "invitados" que estén navegando en ese preciso instante.

## 👨‍💻 Autor

- **Empresa**: EcommJuice
- **Web**: [www.ecommjuice.com](https://www.ecommjuice.com/)
- **Especialidad**: Arquitectura y Desarrollo Senior en PrestaShop.

---

