<?php
/**
 * Plugin Name: Formulario de Trabajos Académicos
 * Description: Formulario para envío de trabajos con panel de administración
 * Version: 1.0
 * Author: mrodriguez
 */

// Evitar acceso directo
if (!defined('ABSPATH')) exit;

class FormularioTrabajos {
    
    private $tabla_nombre = 'trabajos_academicos';
    
    public function init() {
        // Crear tabla en activación
        register_activation_hook(__FILE__, array($this, 'crear_tabla'));
        
        // Registrar shortcode
        add_shortcode('formulario_trabajos', array($this, 'mostrar_formulario'));
        
        // Procesar formulario
        add_action('admin_post_nopriv_procesar_formulario', array($this, 'procesar_formulario'));
        add_action('admin_post_procesar_formulario', array($this, 'procesar_formulario'));
        
        // Exportar datos (solo admin)
        add_action('admin_post_exportar_datos', array($this, 'exportar_datos'));
        
        // Menú en admin
        add_action('admin_menu', array($this, 'agregar_menu_admin'));
        
        // Estilos
        add_action('wp_enqueue_scripts', array($this, 'cargar_estilos_frontend'));
        add_action('admin_enqueue_scripts', array($this, 'cargar_estilos_admin'));
    }
    
    public function crear_tabla() {
        global $wpdb;
        $tabla = $wpdb->prefix . $this->tabla_nombre;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $tabla (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            nombre varchar(100) NOT NULL,
            apellido varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            titulo_trabajo varchar(255) NOT NULL,
            idioma_presentacion varchar(20) NOT NULL,
            archivo_url varchar(255) NOT NULL,
            archivo_nombre varchar(255) NOT NULL,
            fecha_registro datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function agregar_menu_admin() {
        add_menu_page(
            'Trabajos Enviados',
            'Trabajos',
            'manage_options',
            'trabajos-academicos',
            array($this, 'pagina_admin'),
            'dashicons-media-document',
            30
        );
    }
    
    public function cargar_estilos_frontend() {
        ?>
        <style>
            .formulario-trabajos {
                max-width: 700px;
                margin: 30px auto;
                padding: 30px;
                background: #ffffff;
                border-radius: 8px;
                box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            }
            .formulario-trabajos h2 {
                margin-top: 0;
                color: #333;
                border-bottom: 3px solid #0073aa;
                padding-bottom: 15px;
            }
            .form-group {
                margin-bottom: 25px;
            }
            .form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #333;
                font-size: 14px;
            }
            .form-group label .requerido {
                color: #dc3232;
            }
            .form-group input[type="text"],
            .form-group input[type="email"] {
                width: 100%;
                padding: 12px;
                border: 2px solid #ddd;
                border-radius: 4px;
                font-size: 15px;
                transition: border-color 0.3s;
                box-sizing: border-box;
            }
            .form-group input[type="text"]:focus,
            .form-group input[type="email"]:focus {
                outline: none;
                border-color: #0073aa;
            }
            .form-group input[type="file"] {
                padding: 8px;
                border: 2px dashed #ddd;
                border-radius: 4px;
                width: 100%;
                box-sizing: border-box;
            }
            .radio-group {
                display: flex;
                gap: 30px;
                margin-top: 10px;
            }
            .radio-group label {
                font-weight: normal;
                display: flex;
                align-items: center;
                cursor: pointer;
            }
            .radio-group input[type="radio"] {
                margin-right: 8px;
                width: 18px;
                height: 18px;
                cursor: pointer;
            }
            .btn-submit {
                background: #0073aa;
                color: white;
                padding: 14px 40px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
                font-weight: 600;
                transition: background 0.3s;
                width: 100%;
            }
            .btn-submit:hover {
                background: #005177;
            }
            .mensaje-exito {
                background: #46b450;
                color: white;
                padding: 15px 20px;
                border-radius: 4px;
                margin-bottom: 20px;
                font-weight: 500;
            }
            .mensaje-error {
                background: #dc3232;
                color: white;
                padding: 15px 20px;
                border-radius: 4px;
                margin-bottom: 20px;
                font-weight: 500;
            }
            .info-archivo {
                font-size: 13px;
                color: #666;
                margin-top: 5px;
            }
        </style>
        <?php
    }
    
    public function cargar_estilos_admin($hook) {
        if ($hook != 'toplevel_page_trabajos-academicos') {
            return;
        }
        ?>
        <style>
            .wrap-trabajos {
                background: white;
                padding: 20px;
                margin-top: 20px;
                border-radius: 8px;
            }
            .botones-exportar {
                margin: 20px 0;
                display: flex;
                gap: 10px;
            }
            .btn-exportar {
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                font-weight: 600;
            }
            .btn-excel {
                background: #217346;
                color: white;
            }
            .btn-excel:hover {
                background: #185c37;
                color: white;
            }
            .btn-csv {
                background: #0073aa;
                color: white;
            }
            .btn-csv:hover {
                background: #005177;
                color: white;
            }
            .tabla-trabajos {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            .tabla-trabajos th,
            .tabla-trabajos td {
                padding: 12px;
                text-align: left;
                border: 1px solid #ddd;
            }
            .tabla-trabajos th {
                background: #0073aa;
                color: white;
                font-weight: 600;
            }
            .tabla-trabajos tr:nth-child(even) {
                background: #f9f9f9;
            }
            .tabla-trabajos tr:hover {
                background: #f0f0f0;
            }
            .link-archivo {
                color: #0073aa;
                text-decoration: none;
                font-weight: 500;
            }
            .link-archivo:hover {
                text-decoration: underline;
            }
            .badge-idioma {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 600;
            }
            .badge-espanol {
                background: #ffc107;
                color: #000;
            }
            .badge-portugues {
                background: #28a745;
                color: white;
            }
            .total-registros {
                background: #f0f0f0;
                padding: 15px;
                border-radius: 4px;
                margin-bottom: 20px;
                font-weight: 600;
            }
        </style>
        <?php
    }
    
    public function mostrar_formulario($atts) {
        ob_start();
        
        // Mostrar mensajes
        if (isset($_GET['mensaje'])) {
            if ($_GET['mensaje'] == 'exito') {
                echo '<div class="mensaje-exito">✓ ¡Trabajo enviado exitosamente!</div>';
            } elseif ($_GET['mensaje'] == 'error') {
                $error = isset($_GET['error']) ? $_GET['error'] : 'desconocido';
                echo '<div class="mensaje-error">✗ Error: ' . esc_html($error) . '</div>';
            }
        }
        ?>
        
        <div class="formulario-trabajos">
            <h2>Envío de Trabajo</h2>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="procesar_formulario">
                <?php wp_nonce_field('formulario_trabajos_nonce', 'formulario_nonce'); ?>
                
                <div class="form-group">
                    <label for="nombre">Nombre <span class="requerido">*</span></label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                
                <div class="form-group">
                    <label for="apellido">Apellido <span class="requerido">*</span></label>
                    <input type="text" id="apellido" name="apellido" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email <span class="requerido">*</span></label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="titulo_trabajo">Título del Trabajo <span class="requerido">*</span></label>
                    <input type="text" id="titulo_trabajo" name="titulo_trabajo" required>
                </div>
                
                <div class="form-group">
                    <label>Idioma de Presentación <span class="requerido">*</span></label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="idioma_presentacion" value="Español" required>
                            Español
                        </label>
                        <label>
                            <input type="radio" name="idioma_presentacion" value="Portugués" required>
                            Portugués
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="archivo">Archivo del Trabajo <span class="requerido">*</span></label>
                    <input type="file" id="archivo" name="archivo" accept=".pdf,.doc,.docx" required>
                    <p class="info-archivo">Formatos aceptados: PDF, DOC, DOCX (máximo 10MB)</p>
                </div>
                
                <button type="submit" class="btn-submit">Enviar Trabajo</button>
            </form>
        </div>
        
        <?php
        return ob_get_clean();
    }
    
    public function procesar_formulario() {
        global $wpdb;
        
        // Verificar nonce
        if (!isset($_POST['formulario_nonce']) || !wp_verify_nonce($_POST['formulario_nonce'], 'formulario_trabajos_nonce')) {
            wp_redirect(add_query_arg(array('mensaje' => 'error', 'error' => 'Seguridad inválida'), wp_get_referer()));
            exit;
        }
        
        // Validar archivo
        if (empty($_FILES['archivo']['name'])) {
            wp_redirect(add_query_arg(array('mensaje' => 'error', 'error' => 'Debe subir un archivo'), wp_get_referer()));
            exit;
        }
        
        // Procesar archivo
        $resultado_archivo = $this->subir_archivo($_FILES['archivo']);
        
        if (is_wp_error($resultado_archivo)) {
            wp_redirect(add_query_arg(array('mensaje' => 'error', 'error' => $resultado_archivo->get_error_message()), wp_get_referer()));
            exit;
        }
        
        $tabla = $wpdb->prefix . $this->tabla_nombre;
        
        // Insertar datos
        $resultado = $wpdb->insert(
            $tabla,
            array(
                'nombre' => sanitize_text_field($_POST['nombre']),
                'apellido' => sanitize_text_field($_POST['apellido']),
                'email' => sanitize_email($_POST['email']),
                'titulo_trabajo' => sanitize_text_field($_POST['titulo_trabajo']),
                'idioma_presentacion' => sanitize_text_field($_POST['idioma_presentacion']),
                'archivo_url' => $resultado_archivo['url'],
                'archivo_nombre' => $resultado_archivo['nombre']
            )
        );
        
        if ($resultado) {
            wp_redirect(add_query_arg('mensaje', 'exito', wp_get_referer()));
        } else {
            wp_redirect(add_query_arg(array('mensaje' => 'error', 'error' => 'No se pudo guardar en la base de datos'), wp_get_referer()));
        }
        exit;
    }
    
    private function subir_archivo($archivo) {
        // Validar que el archivo existe
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', 'Error al subir el archivo');
        }
        
        // Validar tamaño (10MB máx)
        if ($archivo['size'] > 10485760) {
            return new WP_Error('file_size', 'El archivo excede el tamaño máximo de 10MB');
        }
        
        // Validar tipo de archivo
        $allowed_types = array('pdf', 'doc', 'docx');
        $file_extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            return new WP_Error('file_type', 'Solo se permiten archivos PDF, DOC o DOCX');
        }
        
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/trabajos-academicos/';
        
        // Crear directorio si no existe
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }
        
        $archivo_nombre = sanitize_file_name($archivo['name']);
        $archivo_nombre_unico = time() . '_' . $archivo_nombre;
        $target_file = $target_dir . $archivo_nombre_unico;
        
        if (move_uploaded_file($archivo['tmp_name'], $target_file)) {
            return array(
                'url' => $upload_dir['baseurl'] . '/trabajos-academicos/' . $archivo_nombre_unico,
                'nombre' => $archivo_nombre
            );
        }
        
        return new WP_Error('move_error', 'No se pudo mover el archivo al servidor');
    }
    
    public function pagina_admin() {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para acceder a esta página.');
        }
        
        global $wpdb;
        $tabla = $wpdb->prefix . $this->tabla_nombre;
        
        ?>
        <div class="wrap">
            <h1>📚 Trabajos Académicos Enviados</h1>
            
            <div class="wrap-trabajos">
                <?php
                $total = $wpdb->get_var("SELECT COUNT(*) FROM $tabla");
                echo '<div class="total-registros">Total de trabajos recibidos: ' . $total . '</div>';
                ?>
                
                <div class="botones-exportar">
                    <a href="<?php echo admin_url('admin-post.php?action=exportar_datos&formato=excel'); ?>" class="btn-exportar btn-excel">📥 Descargar Excel</a>
                    <a href="<?php echo admin_url('admin-post.php?action=exportar_datos&formato=csv'); ?>" class="btn-exportar btn-csv">📥 Descargar CSV</a>
                </div>
                
                <?php
                $registros = $wpdb->get_results("SELECT * FROM $tabla ORDER BY fecha_registro DESC");
                
                if ($registros) {
                    echo '<table class="tabla-trabajos">';
                    echo '<thead><tr>';
                    echo '<th>ID</th><th>Nombre</th><th>Apellido</th><th>Email</th><th>Título del Trabajo</th><th>Idioma</th><th>Archivo</th><th>Fecha Envío</th>';
                    echo '</tr></thead><tbody>';
                    
                    foreach ($registros as $registro) {
                        echo '<tr>';
                        echo '<td>' . esc_html($registro->id) . '</td>';
                        echo '<td>' . esc_html($registro->nombre) . '</td>';
                        echo '<td>' . esc_html($registro->apellido) . '</td>';
                        echo '<td>' . esc_html($registro->email) . '</td>';
                        echo '<td><strong>' . esc_html($registro->titulo_trabajo) . '</strong></td>';
                        
                        $badge_class = ($registro->idioma_presentacion == 'Español') ? 'badge-espanol' : 'badge-portugues';
                        echo '<td><span class="badge-idioma ' . $badge_class . '">' . esc_html($registro->idioma_presentacion) . '</span></td>';
                        
                        echo '<td><a href="' . esc_url($registro->archivo_url) . '" target="_blank" class="link-archivo">📄 ' . esc_html($registro->archivo_nombre) . '</a></td>';
                        echo '<td>' . esc_html(date('d/m/Y H:i', strtotime($registro->fecha_registro))) . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody></table>';
                } else {
                    echo '<p>No hay trabajos enviados todavía.</p>';
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    public function exportar_datos() {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para exportar datos.');
        }
        
        global $wpdb;
        $tabla = $wpdb->prefix . $this->tabla_nombre;
        $formato = isset($_GET['formato']) ? $_GET['formato'] : 'csv';
        
        $registros = $wpdb->get_results("SELECT * FROM $tabla ORDER BY fecha_registro DESC", ARRAY_A);
        
        if ($formato == 'excel') {
            $this->exportar_excel($registros);
        } else {
            $this->exportar_csv($registros);
        }
        
        exit;
    }
    
    private function exportar_csv($registros) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=trabajos_academicos_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados
        fputcsv($output, array('ID', 'Nombre', 'Apellido', 'Email', 'Título del Trabajo', 'Idioma', 'Enlace Archivo', 'Nombre Archivo', 'Fecha Registro'));
        
        // Datos
        foreach ($registros as $registro) {
            fputcsv($output, array(
                $registro['id'],
                $registro['nombre'],
                $registro['apellido'],
                $registro['email'],
                $registro['titulo_trabajo'],
                $registro['idioma_presentacion'],
                $registro['archivo_url'],
                $registro['archivo_nombre'],
                $registro['fecha_registro']
            ));
        }
        
        fclose($output);
    }
    
    private function exportar_excel($registros) {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename=trabajos_academicos_' . date('Y-m-d') . '.xls');
        
        echo "\xEF\xBB\xBF"; // BOM para UTF-8
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta charset="UTF-8"></head>';
        echo '<body><table border="1">';
        
        // Encabezados
        echo '<tr>';
        echo '<th>ID</th><th>Nombre</th><th>Apellido</th><th>Email</th><th>Título del Trabajo</th><th>Idioma</th><th>Enlace Archivo</th><th>Nombre Archivo</th><th>Fecha Registro</th>';
        echo '</tr>';
        
        // Datos
        foreach ($registros as $registro) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($registro['id']) . '</td>';
            echo '<td>' . htmlspecialchars($registro['nombre']) . '</td>';
            echo '<td>' . htmlspecialchars($registro['apellido']) . '</td>';
            echo '<td>' . htmlspecialchars($registro['email']) . '</td>';
            echo '<td>' . htmlspecialchars($registro['titulo_trabajo']) . '</td>';
            echo '<td>' . htmlspecialchars($registro['idioma_presentacion']) . '</td>';
            echo '<td><a href="' . htmlspecialchars($registro['archivo_url']) . '">' . htmlspecialchars($registro['archivo_url']) . '</a></td>';
            echo '<td>' . htmlspecialchars($registro['archivo_nombre']) . '</td>';
            echo '<td>' . htmlspecialchars($registro['fecha_registro']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table></body></html>';
    }
}

// Inicializar plugin
$formulario_trabajos = new FormularioTrabajos();
$formulario_trabajos->init();