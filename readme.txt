=== Under Construction Page ===
Contributors: slider66
Donate link: https://github.com/sponsors/slider66
Tags: maintenance, coming soon, under construction, maintenance mode, landing page
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin ligero para mostrar una pantalla de mantenimiento personalizada con HTML/CSS propio mientras trabajas en tu sitio web.

== Description ==

**WP Under Construction** es un plugin ultraligero y profesional que permite mostrar una página de mantenimiento totalmente personalizable mientras realizas cambios en tu sitio web WordPress.

= ✨ Características principales =

* **HTML/CSS 100% personalizable** - Diseña tu propia página de mantenimiento con total libertad
* **Seguro** - Los administradores siempre tienen acceso completo al sitio
* **Ultraligero** - Sin dependencias externas, sin librerías adicionales, carga instantánea
* **Responsive** - Plantilla por defecto adaptada a todos los dispositivos
* **SEO Friendly** - Envía código HTTP 503 con cabecera Retry-After para indicar estado temporal
* **Vista previa** - Previsualiza tus cambios antes de activar el modo mantenimiento
* **Internacionalizado** - Preparado para traducción con text-domain incluido
* **Indicador visual** - Barra de admin indica cuando el modo está activo

= 🎯 Ideal para =

* Sitios web en desarrollo inicial
* Mantenimientos programados
* Actualizaciones importantes
* Rediseños de sitio web
* Migraciones de servidor

= 🔧 Funcionamiento =

Cuando activas el modo mantenimiento:

* **Los visitantes** ven tu página de mantenimiento personalizada
* **Los administradores** ven el sitio normal y pueden trabajar
* **Las peticiones AJAX/REST** funcionan normalmente
* **La página de login** siempre está accesible
* **El cron de WordPress** funciona sin interrupciones

= 💡 Uso del marcador {{CSS}} =

En tu código HTML, usa el marcador `{{CSS}}` donde quieras que se inyecten los estilos CSS. Normalmente dentro del `<head>`:

`<head>
    <meta charset="UTF-8">
    <title>Mantenimiento</title>
    {{CSS}}
</head>`

== Installation ==

= Instalación automática =

1. Ve a tu panel de WordPress → Plugins → Añadir nuevo
2. Busca "WP Under Construction"
3. Haz clic en "Instalar ahora" y luego en "Activar"

= Instalación manual =

1. Descarga el archivo del plugin desde WordPress.org
2. Descomprime el archivo ZIP
3. Sube la carpeta `wp-underconstruction` a `/wp-content/plugins/`
4. Activa el plugin desde el menú Plugins en WordPress

= Después de la instalación =

1. Ve a Ajustes → Under Construction
2. Personaliza tu HTML y CSS (o usa la plantilla por defecto)
3. Activa el modo mantenimiento cuando estés listo
4. ¡Listo! Los visitantes verán tu página de mantenimiento

== Frequently Asked Questions ==

= ¿Por qué sigo viendo el sitio normal cuando activo el modo mantenimiento? =

Estás logueado como administrador. Los usuarios con capacidad `manage_options` siempre ven el sitio normal para poder trabajar. Abre una ventana de incógnito o cierra sesión para ver la página de mantenimiento.

= ¿Afecta al SEO de mi sitio? =

No de forma negativa. El plugin envía un código HTTP 503 (Servicio no disponible) con una cabecera `Retry-After: 3600`, que indica a los motores de búsqueda que el estado es temporal y que deben volver a intentarlo más tarde.

= ¿Puedo usar JavaScript en mi página de mantenimiento? =

¡Sí! Puedes incluir cualquier código JavaScript en tu HTML personalizado. Esto te permite crear páginas con cuentas regresivas, animaciones, formularios de suscripción, etc.

= ¿Es compatible con plugins de caché? =

Sí, pero recuerda limpiar la caché después de activar o desactivar el modo mantenimiento para que los cambios se reflejen inmediatamente.

= ¿Puedo excluir ciertas páginas del modo mantenimiento? =

En la versión actual, el modo mantenimiento aplica a todo el sitio. La exclusión de páginas específicas está planificada para futuras versiones.

= ¿Qué pasa si desactivo el plugin mientras el modo mantenimiento está activo? =

El modo mantenimiento se desactiva automáticamente cuando el plugin se desactiva, garantizando que tu sitio vuelva a ser accesible.

= ¿Se pierden mis configuraciones si desinstalo el plugin? =

Sí, al desinstalar completamente el plugin (no solo desactivar), se eliminan todas las opciones guardadas de la base de datos.

== Screenshots ==

1. Panel de configuración del plugin con editores de código
2. Vista previa de la página de mantenimiento por defecto
3. Indicador en la barra de administración cuando el modo está activo

== Changelog ==

= 1.0.0 =
* Versión inicial
* Editor de HTML personalizado
* Editor de CSS personalizado
* Plantilla por defecto responsive con diseño glassmorphism
* Vista previa de la página de mantenimiento
* Indicador en barra de admin
* Cabeceras HTTP 503 y Retry-After para SEO
* Bypass automático para administradores
* Soporte para internacionalización

== Upgrade Notice ==

= 1.0.0 =
Versión inicial del plugin. ¡Instala y empieza a personalizar tu página de mantenimiento!

== Privacy Policy ==

Este plugin:

* **No recopila** ningún dato personal de los visitantes
* **No envía** información a servidores externos
* **No utiliza** cookies propias
* **No rastrea** a los usuarios de ninguna manera

Todos los datos de configuración se almacenan localmente en la base de datos de WordPress usando la API de opciones estándar.
