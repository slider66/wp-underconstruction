<?php
/**
 * Plugin Name: Custom Under Construction
 * Plugin URI: https://github.com/slider66/wp-underconstruction
 * Description: Plugin ligero para mostrar una pantalla de mantenimiento personalizada con HTML/CSS propio.
 * Version: 1.0.0
 * Author: Alex Merle
 * Author URI: https://alexmerle.es
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-under-construction
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * =============================================================================
 * CONSTANTES DEL PLUGIN
 * =============================================================================
 */
define('CUC_PLUGIN_VERSION', '1.0.0');
define('CUC_OPTION_GROUP', 'cuc_settings_group');
define('CUC_OPTION_NAME', 'cuc_settings');

/**
 * =============================================================================
 * CLASE PRINCIPAL DEL PLUGIN
 * =============================================================================
 */
class Custom_Under_Construction
{

    /**
     * Instancia única del plugin (Singleton)
     */
    private static $instance = null;

    /**
     * Opciones del plugin
     */
    private $options;

    /**
     * Obtener instancia única
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado para Singleton
     */
    private function __construct()
    {
        // Cargar opciones
        $this->options = get_option(CUC_OPTION_NAME, $this->get_default_options());

        // Hooks de administración
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));

        // Hook de intercepción del frontend
        add_action('template_redirect', array($this, 'maybe_show_maintenance_page'));

        // Añadir indicador en la barra de admin cuando el modo está activo
        add_action('admin_bar_menu', array($this, 'add_admin_bar_indicator'), 100);

        // Hooks para import/export
        add_action('admin_init', array($this, 'handle_export'));
        add_action('admin_init', array($this, 'handle_import'));
    }

    /**
     * Opciones por defecto
     */
    private function get_default_options()
    {
        return array(
            'enabled' => false,
            'html_code' => $this->get_default_html(),
            'css_code' => $this->get_default_css(),
        );
    }

    /**
     * HTML por defecto
     */
    private function get_default_html()
    {
        return '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sitio en Mantenimiento</title>
    {{CSS}}
</head>
<body>
    <div class="maintenance-container">
        <h1>🚧 Estamos trabajando en mejoras</h1>
        <p>Nuestro sitio web está temporalmente en mantenimiento.</p>
        <p>Volveremos pronto con novedades.</p>
    </div>
</body>
</html>';
    }

    /**
     * CSS por defecto
     */
    private function get_default_css()
    {
        return '* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, sans-serif;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
}

.maintenance-container {
    text-align: center;
    padding: 40px;
    max-width: 600px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
}

h1 {
    font-size: 2.5rem;
    margin-bottom: 20px;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}

p {
    font-size: 1.2rem;
    line-height: 1.6;
    opacity: 0.9;
    margin-bottom: 10px;
}

@media (max-width: 768px) {
    .maintenance-container {
        margin: 20px;
        padding: 30px 20px;
    }
    
    h1 {
        font-size: 1.8rem;
    }
    
    p {
        font-size: 1rem;
    }
}';
    }

    /**
     * ==========================================================================
     * ADMINISTRACIÓN - Menú y Página de Ajustes
     * ==========================================================================
     */

    /**
     * Añadir página de ajustes al menú de administración
     */
    public function add_admin_menu()
    {
        add_options_page(
            __('Custom Under Construction', 'custom-under-construction'), // Título de la página
            __('Under Construction', 'custom-under-construction'),        // Título del menú
            'manage_options',                                                // Capacidad requerida
            'custom-under-construction',                                     // Slug del menú
            array($this, 'render_settings_page')                          // Callback de renderizado
        );
    }

    /**
     * Registrar configuraciones usando la Settings API
     */
    public function register_settings()
    {
        register_setting(
            CUC_OPTION_GROUP,
            CUC_OPTION_NAME,
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => $this->get_default_options(),
            )
        );

        // Sección principal
        add_settings_section(
            'cuc_main_section',
            __('Configuración del Modo Mantenimiento', 'custom-under-construction'),
            array($this, 'render_section_description'),
            'custom-under-construction'
        );

        // Campo: Activar/Desactivar
        add_settings_field(
            'cuc_enabled',
            __('Activar Modo Mantenimiento', 'custom-under-construction'),
            array($this, 'render_enabled_field'),
            'custom-under-construction',
            'cuc_main_section'
        );

        // Campo: HTML personalizado
        add_settings_field(
            'cuc_html_code',
            __('Código HTML', 'custom-under-construction'),
            array($this, 'render_html_field'),
            'custom-under-construction',
            'cuc_main_section'
        );

        // Campo: CSS personalizado
        add_settings_field(
            'cuc_css_code',
            __('Código CSS', 'custom-under-construction'),
            array($this, 'render_css_field'),
            'custom-under-construction',
            'cuc_main_section'
        );
    }

    /**
     * Sanitizar y validar las opciones antes de guardar
     * 
     * @param array $input Datos de entrada del formulario
     * @return array Datos sanitizados
     */
    public function sanitize_settings($input)
    {
        $sanitized = array();

        // Sanitizar checkbox (enabled)
        $sanitized['enabled'] = isset($input['enabled']) && $input['enabled'] ? true : false;

        // Sanitizar HTML - Permitimos HTML sin escapar pero validamos estructura básica
        // IMPORTANTE: El admin es responsable del código que introduce
        $sanitized['html_code'] = isset($input['html_code'])
            ? wp_kses_post($input['html_code'])
            : $this->get_default_html();

        // Sanitizar CSS - Removemos cualquier cosa que no sea CSS válido
        $sanitized['css_code'] = isset($input['css_code'])
            ? $this->sanitize_css($input['css_code'])
            : $this->get_default_css();

        return $sanitized;
    }

    /**
     * Sanitizar código CSS
     * 
     * @param string $css Código CSS a sanitizar
     * @return string CSS sanitizado
     */
    private function sanitize_css($css)
    {
        // Eliminar cualquier etiqueta HTML/script que pueda estar incrustada
        $css = wp_strip_all_tags($css);

        // Eliminar expresiones JavaScript potencialmente peligrosas
        $css = preg_replace('/expression\s*\(/i', '', $css);
        $css = preg_replace('/javascript\s*:/i', '', $css);
        $css = preg_replace('/behavior\s*:/i', '', $css);
        $css = preg_replace('/-moz-binding\s*:/i', '', $css);

        return $css;
    }

    /**
     * Descripción de la sección
     */
    public function render_section_description()
    {
        echo '<p>' . esc_html__(
            'Configure la pantalla de mantenimiento que verán los visitantes cuando el modo esté activo. Los administradores seguirán viendo el sitio normalmente.',
            'custom-under-construction'
        ) . '</p>';

        // Mostrar advertencia si el modo está activo
        $options = get_option(CUC_OPTION_NAME, $this->get_default_options());
        if (!empty($options['enabled'])) {
            echo '<div class="notice notice-warning inline"><p><strong>';
            echo esc_html__('⚠️ El modo mantenimiento está ACTIVO. Los visitantes no pueden acceder al sitio.', 'custom-under-construction');
            echo '</strong></p></div>';
        }
    }

    /**
     * Renderizar campo de activación
     */
    public function render_enabled_field()
    {
        $enabled = isset($this->options['enabled']) ? $this->options['enabled'] : false;
        ?>
        <label class="cuc-toggle">
            <input type="checkbox" name="<?php echo esc_attr(CUC_OPTION_NAME); ?>[enabled]" value="1" <?php checked($enabled, true); ?>>
            <span class="cuc-toggle-slider"></span>
            <span class="cuc-toggle-label">
                <?php echo $enabled
                    ? esc_html__('Activo - Los visitantes ven la página de mantenimiento', 'custom-under-construction')
                    : esc_html__('Inactivo - El sitio funciona normalmente', 'custom-under-construction');
                ?>
            </span>
        </label>
        <?php
    }

    /**
     * Renderizar campo de HTML
     */
    public function render_html_field()
    {
        $html_code = isset($this->options['html_code']) ? $this->options['html_code'] : $this->get_default_html();
        ?>
        <textarea name="<?php echo esc_attr(CUC_OPTION_NAME); ?>[html_code]" id="cuc_html_code"
            class="large-text code cuc-code-editor" rows="15"
            placeholder="<!DOCTYPE html>..."><?php echo esc_textarea($html_code); ?></textarea>
        <p class="description">
            <?php echo esc_html__('Introduce el código HTML completo de tu página de mantenimiento.', 'custom-under-construction'); ?>
            <br>
            <strong><?php echo esc_html__('Usa {{CSS}} donde quieras inyectar los estilos CSS.', 'custom-under-construction'); ?></strong>
        </p>
        <?php
    }

    /**
     * Renderizar campo de CSS
     */
    public function render_css_field()
    {
        $css_code = isset($this->options['css_code']) ? $this->options['css_code'] : $this->get_default_css();
        ?>
        <textarea name="<?php echo esc_attr(CUC_OPTION_NAME); ?>[css_code]" id="cuc_css_code"
            class="large-text code cuc-code-editor" rows="15"
            placeholder="body { ... }"><?php echo esc_textarea($css_code); ?></textarea>
        <p class="description">
            <?php echo esc_html__('Introduce el código CSS para estilizar tu página de mantenimiento. Se inyectará automáticamente en {{CSS}}.', 'custom-under-construction'); ?>
        </p>
        <?php
    }

    /**
     * Estilos del panel de administración
     */
    public function enqueue_admin_styles($hook)
    {
        // Solo cargar en nuestra página de ajustes
        if ('settings_page_custom-under-construction' !== $hook) {
            return;
        }

        wp_add_inline_style('wp-admin', '
            .cuc-code-editor {
                font-family: "Monaco", "Menlo", "Ubuntu Mono", "Consolas", monospace;
                font-size: 13px;
                line-height: 1.5;
                background: #1e1e1e;
                color: #d4d4d4;
                border: 1px solid #3c3c3c;
                border-radius: 4px;
                padding: 12px;
                resize: vertical;
            }
            
            .cuc-code-editor:focus {
                border-color: #007cba;
                box-shadow: 0 0 0 1px #007cba;
                outline: none;
            }
            
            .cuc-toggle {
                display: flex;
                align-items: center;
                gap: 12px;
                cursor: pointer;
            }
            
            .cuc-toggle input[type="checkbox"] {
                display: none;
            }
            
            .cuc-toggle-slider {
                position: relative;
                width: 50px;
                height: 26px;
                background: #ccc;
                border-radius: 26px;
                transition: background 0.3s;
            }
            
            .cuc-toggle-slider::after {
                content: "";
                position: absolute;
                top: 3px;
                left: 3px;
                width: 20px;
                height: 20px;
                background: white;
                border-radius: 50%;
                transition: transform 0.3s;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }
            
            .cuc-toggle input:checked + .cuc-toggle-slider {
                background: #dc3545;
            }
            
            .cuc-toggle input:checked + .cuc-toggle-slider::after {
                transform: translateX(24px);
            }
            
            .cuc-toggle-label {
                font-weight: 500;
            }
            
            .cuc-preview-btn {
                margin-top: 10px !important;
            }
            
            .cuc-sponsor-section {
                margin-top: 30px;
                padding: 20px;
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                border-radius: 8px;
                border: 1px solid #dee2e6;
            }
            
            .cuc-sponsor-btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                background: linear-gradient(135deg, #ea4aaa 0%, #bf3989 100%);
                color: #fff !important;
                padding: 10px 20px;
                border-radius: 6px;
                text-decoration: none !important;
                font-weight: 600;
                transition: all 0.3s ease;
                box-shadow: 0 2px 8px rgba(234, 74, 170, 0.3);
            }
            
            .cuc-sponsor-btn:hover {
                background: linear-gradient(135deg, #bf3989 0%, #9a2d6e 100%);
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(234, 74, 170, 0.4);
                color: #fff !important;
            }
            
            .cuc-sponsor-btn svg {
                fill: currentColor;
            }
            
            .cuc-backup-section {
                margin-top: 30px;
                padding: 20px;
                background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
                border-radius: 8px;
                border: 1px solid #90caf9;
            }
            
            .cuc-backup-section h2 {
                margin-top: 0;
                color: #1565c0;
            }
            
            .cuc-backup-actions {
                display: flex;
                gap: 20px;
                flex-wrap: wrap;
                margin-top: 15px;
            }
            
            .cuc-backup-box {
                flex: 1;
                min-width: 250px;
                padding: 15px;
                background: #fff;
                border-radius: 6px;
                border: 1px solid #e0e0e0;
            }
            
            .cuc-backup-box h3 {
                margin-top: 0;
                font-size: 14px;
                color: #333;
            }
            
            .cuc-backup-box p {
                font-size: 13px;
                color: #666;
                margin-bottom: 10px;
            }
            
            .cuc-export-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: #1976d2;
                color: #fff !important;
                padding: 8px 16px;
                border-radius: 4px;
                text-decoration: none !important;
                font-weight: 500;
                transition: all 0.2s ease;
            }
            
            .cuc-export-btn:hover {
                background: #1565c0;
                color: #fff !important;
            }
            
            .cuc-import-input {
                margin-bottom: 10px;
            }
        ');
    }

    /**
     * Renderizar la página de ajustes completa
     */
    public function render_settings_page()
    {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para acceder a esta página.', 'custom-under-construction'));
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form method="post" action="options.php">
                <?php
                // Campos de seguridad (nonce)
                settings_fields(CUC_OPTION_GROUP);

                // Renderizar secciones y campos
                do_settings_sections('custom-under-construction');

                // Botón de guardar
                submit_button(__('Guardar Cambios', 'custom-under-construction'));
                ?>
            </form>

            <hr>

            <h2><?php esc_html_e('Vista Previa', 'custom-under-construction'); ?></h2>
            <p><?php esc_html_e('Previsualiza cómo se verá la página de mantenimiento:', 'custom-under-construction'); ?></p>
            <a href="<?php echo esc_url(add_query_arg('cuc_preview', '1', home_url())); ?>" target="_blank"
                class="button button-secondary cuc-preview-btn">
                <?php esc_html_e('👁️ Ver Vista Previa', 'custom-under-construction'); ?>
            </a>

            <div class="cuc-sponsor-section">
                <h2><?php esc_html_e('💖 Apoya este Plugin', 'custom-under-construction'); ?></h2>
                <p><?php esc_html_e('¡Hola! Soy el desarrollador de wp-underconstruction. Dedico gran parte de mi tiempo libre a mantener este plugin gratuito, seguro y actualizado para que tu sitio web en WordPress funcione sin problemas.', 'custom-under-construction'); ?>
                </p>
                <p><?php esc_html_e('Al apoyarme a través de GitHub Sponsors, me ayudas a cubrir los costes de desarrollo y a dedicar más horas a implementar nuevas funcionalidades. Tu soporte garantiza que este proyecto siga siendo gratuito y de código abierto para todos.', 'custom-under-construction'); ?>
                </p>
                <a href="https://github.com/sponsors/slider66" target="_blank" rel="noopener noreferrer"
                    class="cuc-sponsor-btn">
                    <svg height="16" width="16" viewBox="0 0 16 16" version="1.1" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M4.25 2.5c-1.336 0-2.75 1.164-2.75 3 0 2.15 1.58 4.144 3.365 5.682A20.565 20.565 0 008 13.393a20.561 20.561 0 003.135-2.211C12.92 9.644 14.5 7.65 14.5 5.5c0-1.836-1.414-3-2.75-3-1.373 0-2.609.986-3.029 2.456a.75.75 0 01-1.442 0C6.859 3.486 5.623 2.5 4.25 2.5zM8 14.25l-.345.666-.002-.001-.006-.003-.018-.01a7.643 7.643 0 01-.31-.17 22.075 22.075 0 01-3.434-2.414C2.045 10.731 0 8.35 0 5.5 0 2.836 2.086 1 4.25 1 5.797 1 7.153 1.802 8 3.02 8.847 1.802 10.203 1 11.75 1 13.914 1 16 2.836 16 5.5c0 2.85-2.045 5.231-3.885 6.818a22.08 22.08 0 01-3.744 2.584l-.018.01-.006.003h-.002L8 14.25zm0 0l.345.666a.752.752 0 01-.69 0L8 14.25z">
                        </path>
                    </svg>
                    <?php esc_html_e('Sponsor en GitHub', 'custom-under-construction'); ?>
                </a>
            </div>
            <div class="cuc-backup-section">
                <h2><?php esc_html_e('💾 Backup / Restaurar Configuración', 'custom-under-construction'); ?></h2>
                <p><?php esc_html_e('Exporta tu configuración para guardarla como backup o impórtala desde un archivo JSON.', 'custom-under-construction'); ?>
                </p>

                <?php settings_errors('cuc_import'); ?>

                <div class="cuc-backup-actions">
                    <div class="cuc-backup-box">
                        <h3><?php esc_html_e('📤 Exportar Configuración', 'custom-under-construction'); ?></h3>
                        <p><?php esc_html_e('Descarga un archivo JSON con toda tu configuración actual.', 'custom-under-construction'); ?>
                        </p>
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('options-general.php?page=custom-under-construction&cuc_export=1'), 'cuc_export_nonce')); ?>"
                            class="cuc-export-btn">
                            <?php esc_html_e('⬇️ Descargar Backup', 'custom-under-construction'); ?>
                        </a>
                    </div>

                    <div class="cuc-backup-box">
                        <h3><?php esc_html_e('📥 Importar Configuración', 'custom-under-construction'); ?></h3>
                        <p><?php esc_html_e('Restaura tu configuración desde un archivo de backup.', 'custom-under-construction'); ?>
                        </p>
                        <form method="post" enctype="multipart/form-data">
                            <?php wp_nonce_field('cuc_import_nonce'); ?>
                            <input type="hidden" name="cuc_import" value="1">
                            <input type="file" name="cuc_import_file" accept=".json" class="cuc-import-input" required>
                            <button type="submit" class="button button-secondary">
                                <?php esc_html_e('⬆️ Importar Backup', 'custom-under-construction'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * ==========================================================================
     * FRONTEND - Lógica de Intercepción
     * ==========================================================================
     */

    /**
     * Mostrar página de mantenimiento si corresponde
     */
    public function maybe_show_maintenance_page()
    {
        // Obtener opciones actualizadas
        $options = get_option(CUC_OPTION_NAME, $this->get_default_options());

        // Verificar si existe el parámetro de preview (solo para admins)
        $is_preview = isset($_GET['cuc_preview']) && current_user_can('manage_options');

        // Condiciones para NO mostrar la página de mantenimiento:
        // 1. El modo no está activo (y no es preview)
        if (empty($options['enabled']) && !$is_preview) {
            return;
        }

        // 2. El usuario tiene permisos de administrador (puede ver el sitio normalmente)
        if (current_user_can('manage_options') && !$is_preview) {
            return;
        }

        // 3. Es la página de login de WordPress
        if ($this->is_login_page()) {
            return;
        }

        // 4. Es una petición AJAX o REST API (para no bloquear funcionalidad backend)
        if (wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        // 5. Es el área de administración
        if (is_admin()) {
            return;
        }

        // 6. Es la página de cron
        if (defined('DOING_CRON') && DOING_CRON) {
            return;
        }

        // Todas las condiciones cumplidas: mostrar página de mantenimiento
        $this->render_maintenance_page($options);
    }

    /**
     * Verificar si estamos en la página de login
     * 
     * @return bool
     */
    private function is_login_page()
    {
        // Verificar página de login estándar
        if (in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'), true)) {
            return true;
        }

        // Verificar URLs de login personalizadas
        $login_url = wp_login_url();
        $current_url = home_url($_SERVER['REQUEST_URI']);

        if (strpos($current_url, $login_url) !== false) {
            return true;
        }

        return false;
    }

    /**
     * Renderizar la página de mantenimiento
     * 
     * @param array $options Opciones del plugin
     */
    private function render_maintenance_page($options)
    {
        // Establecer código de estado HTTP 503 (Servicio no disponible)
        status_header(503);

        // Cabecera Retry-After para SEO (volver a intentar en 1 hora)
        header('Retry-After: 3600');

        // Cabecera para evitar cacheo
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Obtener HTML y CSS
        $html = isset($options['html_code']) ? $options['html_code'] : $this->get_default_html();
        $css = isset($options['css_code']) ? $options['css_code'] : $this->get_default_css();

        // Construir etiqueta <style>
        $style_tag = '<style type="text/css">' . "\n" . $css . "\n" . '</style>';

        // Inyectar CSS en el marcador {{CSS}} o antes de </head>
        if (strpos($html, '{{CSS}}') !== false) {
            $html = str_replace('{{CSS}}', $style_tag, $html);
        } elseif (strpos($html, '</head>') !== false) {
            $html = str_replace('</head>', $style_tag . "\n</head>", $html);
        } else {
            // Si no hay ni {{CSS}} ni </head>, añadir al principio
            $html = $style_tag . "\n" . $html;
        }

        // Enviar la respuesta
        echo $html;

        // IMPORTANTE: Detener la ejecución para evitar que se cargue el tema
        exit;
    }

    /**
     * Añadir indicador en la barra de administración
     */
    public function add_admin_bar_indicator($wp_admin_bar)
    {
        $options = get_option(CUC_OPTION_NAME, $this->get_default_options());

        // Solo mostrar si el modo está activo y el usuario es admin
        if (empty($options['enabled']) || !current_user_can('manage_options')) {
            return;
        }

        $wp_admin_bar->add_node(array(
            'id' => 'cuc-maintenance-mode',
            'title' => '<span style="background:#dc3545;color:#fff;padding:2px 8px;border-radius:3px;font-weight:bold;">🚧 ' .
                esc_html__('Modo Mantenimiento ACTIVO', 'custom-under-construction') .
                '</span>',
            'href' => admin_url('options-general.php?page=custom-under-construction'),
            'meta' => array(
                'title' => __('Haga clic para gestionar el modo mantenimiento', 'custom-under-construction'),
            ),
        ));
    }

    /**
     * ==========================================================================
     * IMPORT/EXPORT - Backup de Configuración
     * ==========================================================================
     */

    /**
     * Manejar la exportación de configuración
     */
    public function handle_export()
    {
        // Verificar si es una petición de exportación
        if (!isset($_GET['cuc_export']) || $_GET['cuc_export'] !== '1') {
            return;
        }

        // Verificar nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'cuc_export_nonce')) {
            wp_die(esc_html__('Error de seguridad. Por favor, recarga la página e inténtalo de nuevo.', 'custom-under-construction'));
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para realizar esta acción.', 'custom-under-construction'));
        }

        // Obtener opciones
        $options = get_option(CUC_OPTION_NAME, $this->get_default_options());

        // Preparar datos para exportar
        $export_data = array(
            'plugin' => 'wp-underconstruction',
            'version' => CUC_PLUGIN_VERSION,
            'exported_at' => current_time('mysql'),
            'settings' => $options,
        );

        // Generar nombre de archivo
        $filename = 'wp-underconstruction-backup-' . date('Y-m-d-His') . '.json';

        // Enviar cabeceras
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Enviar contenido
        echo wp_json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Manejar la importación de configuración
     */
    public function handle_import()
    {
        // Verificar si es una petición de importación
        if (!isset($_POST['cuc_import']) || $_POST['cuc_import'] !== '1') {
            return;
        }

        // Verificar nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'cuc_import_nonce')) {
            add_settings_error(
                'cuc_import',
                'cuc_import_error',
                __('Error de seguridad. Por favor, recarga la página e inténtalo de nuevo.', 'custom-under-construction'),
                'error'
            );
            return;
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            add_settings_error(
                'cuc_import',
                'cuc_import_error',
                __('No tienes permisos para realizar esta acción.', 'custom-under-construction'),
                'error'
            );
            return;
        }

        // Verificar archivo subido
        if (!isset($_FILES['cuc_import_file']) || $_FILES['cuc_import_file']['error'] !== UPLOAD_ERR_OK) {
            add_settings_error(
                'cuc_import',
                'cuc_import_error',
                __('Error al subir el archivo. Por favor, selecciona un archivo válido.', 'custom-under-construction'),
                'error'
            );
            return;
        }

        // Verificar extensión
        $file_info = pathinfo($_FILES['cuc_import_file']['name']);
        if (strtolower($file_info['extension']) !== 'json') {
            add_settings_error(
                'cuc_import',
                'cuc_import_error',
                __('El archivo debe ser un archivo JSON válido.', 'custom-under-construction'),
                'error'
            );
            return;
        }

        // Leer contenido del archivo
        $file_content = file_get_contents($_FILES['cuc_import_file']['tmp_name']);
        $import_data = json_decode($file_content, true);

        // Verificar JSON válido
        if (json_last_error() !== JSON_ERROR_NONE) {
            add_settings_error(
                'cuc_import',
                'cuc_import_error',
                __('El archivo JSON no es válido o está corrupto.', 'custom-under-construction'),
                'error'
            );
            return;
        }

        // Verificar que sea un backup de este plugin
        if (!isset($import_data['plugin']) || $import_data['plugin'] !== 'wp-underconstruction') {
            add_settings_error(
                'cuc_import',
                'cuc_import_error',
                __('El archivo no es un backup válido de WP Under Construction.', 'custom-under-construction'),
                'error'
            );
            return;
        }

        // Verificar que tenga settings
        if (!isset($import_data['settings']) || !is_array($import_data['settings'])) {
            add_settings_error(
                'cuc_import',
                'cuc_import_error',
                __('El archivo de backup no contiene configuración válida.', 'custom-under-construction'),
                'error'
            );
            return;
        }

        // Sanitizar e importar configuración
        $sanitized_settings = $this->sanitize_settings($import_data['settings']);
        update_option(CUC_OPTION_NAME, $sanitized_settings);

        // Actualizar opciones en memoria
        $this->options = $sanitized_settings;

        // Mensaje de éxito
        add_settings_error(
            'cuc_import',
            'cuc_import_success',
            sprintf(
                __('✅ Configuración importada correctamente. Backup creado el: %s', 'custom-under-construction'),
                isset($import_data['exported_at']) ? $import_data['exported_at'] : __('Fecha desconocida', 'custom-under-construction')
            ),
            'success'
        );
    }
}

/**
 * =============================================================================
 * INICIALIZACIÓN DEL PLUGIN
 * =============================================================================
 */

/**
 * Iniciar el plugin
 */
function cuc_init()
{
    Custom_Under_Construction::get_instance();
}
add_action('plugins_loaded', 'cuc_init');

/**
 * Activación del plugin
 */
function cuc_activate()
{
    // Crear opciones por defecto si no existen
    if (!get_option(CUC_OPTION_NAME)) {
        // Usamos valores por defecto directos ya que los métodos de la clase son privados
        add_option(CUC_OPTION_NAME, array(
            'enabled' => false,
            'html_code' => '',
            'css_code' => '',
        ));
    }
}
register_activation_hook(__FILE__, 'cuc_activate');

/**
 * Desactivación del plugin
 */
function cuc_deactivate()
{
    // Desactivar el modo mantenimiento al desactivar el plugin
    $options = get_option(CUC_OPTION_NAME);
    if ($options && isset($options['enabled']) && $options['enabled']) {
        $options['enabled'] = false;
        update_option(CUC_OPTION_NAME, $options);
    }
}
register_deactivation_hook(__FILE__, 'cuc_deactivate');

/**
 * Desinstalación del plugin (eliminar datos)
 * NOTA: Este código se ejecuta cuando se elimina el plugin desde el panel de WordPress
 */
function cuc_uninstall()
{
    delete_option(CUC_OPTION_NAME);
}
register_uninstall_hook(__FILE__, 'cuc_uninstall');
