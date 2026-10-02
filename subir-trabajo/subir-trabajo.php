<?php
/**
 * Plugin Name: Subir Trabajo Académico
 * Description: Permite a usuarios registrados subir sus archivos de trabajo académico identificándose por email
 * Version: 1.1
 * Author: mrodriguez
 */

if (!defined('ABSPATH')) exit;

class SubirTrabajo {

    private $tabla_nombre = 'trabajos_academicos';

    public function init() {
        add_shortcode('subir_trabajo', array($this, 'mostrar_formulario'));

        // AJAX para buscar por email
        add_action('wp_ajax_buscar_por_email', array($this, 'buscar_por_email'));
        add_action('wp_ajax_nopriv_buscar_por_email', array($this, 'buscar_por_email'));

        // AJAX para subir archivo
        add_action('wp_ajax_subir_archivo_trabajo', array($this, 'subir_archivo_trabajo'));
        add_action('wp_ajax_nopriv_subir_archivo_trabajo', array($this, 'subir_archivo_trabajo'));

        add_action('wp_enqueue_scripts', array($this, 'cargar_estilos'));
    }

    public function cargar_estilos() {
        ?>
        <style>
            .subir-trabajo-container {
                max-width: 700px;
                margin: 30px auto;
                padding: 30px;
                background: #ffffff;
                border-radius: 8px;
                box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            }
            .subir-trabajo-container h2 {
                margin-top: 0;
                color: #333;
                border-bottom: 3px solid #0073aa;
                padding-bottom: 15px;
            }
            .st-form-group {
                margin-bottom: 25px;
            }
            .st-form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #333;
                font-size: 14px;
            }
            .st-form-group label .requerido {
                color: #dc3232;
            }
            .st-form-group input[type="email"] {
                width: 100%;
                padding: 12px;
                border: 2px solid #ddd;
                border-radius: 4px;
                font-size: 15px;
                transition: border-color 0.3s;
                box-sizing: border-box;
            }
            .st-form-group input[type="email"]:focus {
                outline: none;
                border-color: #0073aa;
            }
            .st-form-group input[type="file"] {
                padding: 8px;
                border: 2px dashed #ddd;
                border-radius: 4px;
                width: 100%;
                box-sizing: border-box;
            }
            .st-btn {
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
            .st-btn:hover {
                background: #005177;
            }
            .st-btn:disabled {
                background: #999;
                cursor: not-allowed;
            }
            .st-mensaje-exito {
                background: #46b450;
                color: white;
                padding: 15px 20px;
                border-radius: 4px;
                margin-bottom: 20px;
                font-weight: 500;
            }
            .st-mensaje-error {
                background: #dc3232;
                color: white;
                padding: 15px 20px;
                border-radius: 4px;
                margin-bottom: 20px;
                font-weight: 500;
            }
            .st-datos-usuario {
                background: #f0f7fc;
                border: 1px solid #c8e1f4;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 25px;
            }
            .st-datos-usuario h3 {
                margin-top: 0;
                color: #0073aa;
                margin-bottom: 15px;
            }
            .st-datos-usuario p {
                margin: 8px 0;
                font-size: 14px;
            }
            .st-datos-usuario strong {
                display: inline-block;
                min-width: 140px;
                color: #333;
            }
            .st-archivo-actual {
                background: #fff8e1;
                border: 1px solid #ffe082;
                border-radius: 4px;
                padding: 15px;
                margin-bottom: 15px;
                font-size: 14px;
            }
            .st-info-archivo {
                font-size: 13px;
                color: #666;
                margin-top: 5px;
            }
            .st-seccion-archivo {
                background: #f9f9f9;
                border: 1px solid #e0e0e0;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
            }
            .st-seccion-archivo h4 {
                margin-top: 0;
                margin-bottom: 15px;
                color: #333;
                font-size: 15px;
            }
            .st-seccion-archivo .st-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 600;
                margin-left: 8px;
                vertical-align: middle;
            }
            .st-badge-es {
                background: #ffc107;
                color: #000;
            }
            .st-badge-pt {
                background: #28a745;
                color: white;
            }
            #st-resultado {
                display: none;
            }
        </style>
        <?php
    }

    public function mostrar_formulario($atts) {
        ob_start();
        ?>
        <div class="subir-trabajo-container">
            <h2>Subir Archivos de Trabajo</h2>

            <div id="st-mensajes"></div>

            <div id="st-buscar-email">
                <div class="st-form-group">
                    <label for="st-email">Ingrese su email registrado <span class="requerido">*</span></label>
                    <input type="email" id="st-email" placeholder="ejemplo@correo.com" required>
                </div>
                <button type="button" id="st-btn-buscar" class="st-btn">Buscar</button>
            </div>

            <div id="st-resultado">
                <div id="st-datos-usuario" class="st-datos-usuario"></div>

                <!-- Archivo Español -->
                <div class="st-seccion-archivo">
                    <h4>Documento en Español <span class="st-badge st-badge-es">ES</span></h4>
                    <div id="st-archivo-es-actual"></div>
                    <form id="st-form-archivo-es" enctype="multipart/form-data">
                        <div class="st-form-group">
                            <label for="st-archivo-es">Seleccione el archivo en español</label>
                            <input type="file" id="st-archivo-es" name="archivo" accept=".pdf,.doc,.docx">
                            <p class="st-info-archivo">Formatos: PDF, DOC, DOCX (máximo 10MB)</p>
                        </div>
                        <button type="submit" class="st-btn">Subir Archivo ES</button>
                    </form>
                </div>

                <!-- Archivo Portugués -->
                <div class="st-seccion-archivo">
                    <h4>Documento en Portugués <span class="st-badge st-badge-pt">PT</span></h4>
                    <div id="st-archivo-pt-actual"></div>
                    <form id="st-form-archivo-pt" enctype="multipart/form-data">
                        <div class="st-form-group">
                            <label for="st-archivo-pt">Seleccione el archivo en portugués</label>
                            <input type="file" id="st-archivo-pt" name="archivo" accept=".pdf,.doc,.docx">
                            <p class="st-info-archivo">Formatos: PDF, DOC, DOCX (máximo 10MB)</p>
                        </div>
                        <button type="submit" class="st-btn">Subir Archivo PT</button>
                    </form>
                </div>
            </div>
        </div>

        <script>
        (function() {
            var ajaxUrl = '<?php echo admin_url("admin-ajax.php"); ?>';
            var nonce = '<?php echo wp_create_nonce("subir_trabajo_nonce"); ?>';
            var registroId = null;

            var btnBuscar = document.getElementById('st-btn-buscar');
            var inputEmail = document.getElementById('st-email');
            var resultado = document.getElementById('st-resultado');
            var datosUsuario = document.getElementById('st-datos-usuario');
            var mensajes = document.getElementById('st-mensajes');
            var buscarEmail = document.getElementById('st-buscar-email');

            var formEs = document.getElementById('st-form-archivo-es');
            var formPt = document.getElementById('st-form-archivo-pt');
            var archivoEsActual = document.getElementById('st-archivo-es-actual');
            var archivoPtActual = document.getElementById('st-archivo-pt-actual');

            function mostrarMensaje(texto, tipo) {
                mensajes.innerHTML = '<div class="' + (tipo === 'error' ? 'st-mensaje-error' : 'st-mensaje-exito') + '">' + texto + '</div>';
                mensajes.scrollIntoView({behavior: 'smooth'});
            }

            function limpiarMensajes() {
                mensajes.innerHTML = '';
            }

            function mostrarArchivoActual(container, nombre) {
                if (nombre) {
                    container.innerHTML = '<div class="st-archivo-actual">📄 Archivo actual: <strong>' + nombre + '</strong> — Puede reemplazarlo subiendo uno nuevo.</div>';
                } else {
                    container.innerHTML = '';
                }
            }

            btnBuscar.addEventListener('click', function() {
                var email = inputEmail.value.trim();
                if (!email) {
                    mostrarMensaje('Por favor ingrese un email.', 'error');
                    return;
                }

                limpiarMensajes();
                btnBuscar.disabled = true;
                btnBuscar.textContent = 'Buscando...';

                var formData = new FormData();
                formData.append('action', 'buscar_por_email');
                formData.append('nonce', nonce);
                formData.append('email', email);

                fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    btnBuscar.disabled = false;
                    btnBuscar.textContent = 'Buscar';

                    if (data.success) {
                        var reg = data.data;
                        registroId = reg.id;

                        datosUsuario.innerHTML = '<h3>Datos del Registro</h3>' +
                            '<p><strong>Nombre:</strong> ' + reg.nombre + ' ' + reg.apellido + '</p>' +
                            '<p><strong>Email:</strong> ' + reg.email + '</p>' +
                            '<p><strong>Título del Trabajo:</strong> ' + reg.titulo_trabajo + '</p>' +
                            '<p><strong>Idioma:</strong> ' + reg.idioma_presentacion + '</p>' +
                            '<p><strong>Fecha de Registro:</strong> ' + reg.fecha_registro + '</p>';

                        mostrarArchivoActual(archivoEsActual, reg.archivo_es_nombre);
                        mostrarArchivoActual(archivoPtActual, reg.archivo_pt_nombre);

                        buscarEmail.style.display = 'none';
                        resultado.style.display = 'block';
                    } else {
                        mostrarMensaje(data.data, 'error');
                        resultado.style.display = 'none';
                    }
                })
                .catch(function() {
                    btnBuscar.disabled = false;
                    btnBuscar.textContent = 'Buscar';
                    mostrarMensaje('Error de conexión. Intente nuevamente.', 'error');
                });
            });

            inputEmail.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    btnBuscar.click();
                }
            });

            function subirArchivo(form, idioma, containerActual) {
                var archivoInput = form.querySelector('input[type="file"]');
                if (!archivoInput.files.length) {
                    mostrarMensaje('Por favor seleccione un archivo.', 'error');
                    return;
                }

                limpiarMensajes();
                var btnSubmit = form.querySelector('.st-btn');
                btnSubmit.disabled = true;
                btnSubmit.textContent = 'Subiendo...';

                var formData = new FormData();
                formData.append('action', 'subir_archivo_trabajo');
                formData.append('nonce', nonce);
                formData.append('registro_id', registroId);
                formData.append('idioma', idioma);
                formData.append('archivo', archivoInput.files[0]);

                fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = idioma === 'es' ? 'Subir Archivo ES' : 'Subir Archivo PT';

                    if (data.success) {
                        mostrarMensaje('✓ ¡Archivo subido exitosamente!', 'exito');
                        mostrarArchivoActual(containerActual, data.data.archivo_nombre);
                        archivoInput.value = '';
                    } else {
                        mostrarMensaje(data.data, 'error');
                    }
                })
                .catch(function() {
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = idioma === 'es' ? 'Subir Archivo ES' : 'Subir Archivo PT';
                    mostrarMensaje('Error de conexión. Intente nuevamente.', 'error');
                });
            }

            formEs.addEventListener('submit', function(e) {
                e.preventDefault();
                subirArchivo(formEs, 'es', archivoEsActual);
            });

            formPt.addEventListener('submit', function(e) {
                e.preventDefault();
                subirArchivo(formPt, 'pt', archivoPtActual);
            });
        })();
        </script>

        <?php
        return ob_get_clean();
    }

    public function buscar_por_email() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'subir_trabajo_nonce')) {
            wp_send_json_error('Seguridad inválida.');
        }

        $email = sanitize_email($_POST['email']);
        if (empty($email)) {
            wp_send_json_error('Por favor ingrese un email válido.');
        }

        global $wpdb;
        $tabla = $wpdb->prefix . $this->tabla_nombre;

        $registro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE email = %s",
            $email
        ));

        if (!$registro) {
            wp_send_json_error('No se encontró un registro con ese email.');
        }

        wp_send_json_success(array(
            'id' => $registro->id,
            'nombre' => esc_html($registro->nombre),
            'apellido' => esc_html($registro->apellido),
            'email' => esc_html($registro->email),
            'titulo_trabajo' => esc_html($registro->titulo_trabajo),
            'idioma_presentacion' => esc_html($registro->idioma_presentacion),
            'archivo_es_nombre' => !empty($registro->archivo_es_nombre) ? esc_html($registro->archivo_es_nombre) : '',
            'archivo_pt_nombre' => !empty($registro->archivo_pt_nombre) ? esc_html($registro->archivo_pt_nombre) : '',
            'fecha_registro' => date('d/m/Y H:i', strtotime($registro->fecha_registro))
        ));
    }

    public function subir_archivo_trabajo() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'subir_trabajo_nonce')) {
            wp_send_json_error('Seguridad inválida.');
        }

        if (empty($_FILES['archivo']['name'])) {
            wp_send_json_error('Debe seleccionar un archivo.');
        }

        $registro_id = intval($_POST['registro_id']);
        if (!$registro_id) {
            wp_send_json_error('Registro inválido.');
        }

        $idioma = sanitize_text_field($_POST['idioma']);
        if (!in_array($idioma, array('es', 'pt'))) {
            wp_send_json_error('Idioma inválido.');
        }

        global $wpdb;
        $tabla = $wpdb->prefix . $this->tabla_nombre;

        // Verificar que el registro existe
        $existe = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tabla WHERE id = %d", $registro_id));
        if (!$existe) {
            wp_send_json_error('El registro no existe.');
        }

        $archivo = $_FILES['archivo'];

        // Validar error de subida
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('Error al subir el archivo.');
        }

        // Validar tamaño (10MB máx)
        if ($archivo['size'] > 10485760) {
            wp_send_json_error('El archivo excede el tamaño máximo de 10MB.');
        }

        // Validar tipo de archivo
        $allowed_types = array('pdf', 'doc', 'docx');
        $file_extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_types)) {
            wp_send_json_error('Solo se permiten archivos PDF, DOC o DOCX.');
        }

        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/trabajos-academicos/';

        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }

        $archivo_nombre = sanitize_file_name($archivo['name']);
        $archivo_nombre_unico = time() . '_' . $idioma . '_' . $archivo_nombre;
        $target_file = $target_dir . $archivo_nombre_unico;

        if (!move_uploaded_file($archivo['tmp_name'], $target_file)) {
            wp_send_json_error('No se pudo mover el archivo al servidor.');
        }

        $archivo_url = $upload_dir['baseurl'] . '/trabajos-academicos/' . $archivo_nombre_unico;

        // Actualizar registro en la base de datos según el idioma
        $col_url = 'archivo_' . $idioma . '_url';
        $col_nombre = 'archivo_' . $idioma . '_nombre';

        $resultado = $wpdb->update(
            $tabla,
            array(
                $col_url => $archivo_url,
                $col_nombre => $archivo_nombre
            ),
            array('id' => $registro_id),
            array('%s', '%s'),
            array('%d')
        );

        if ($resultado !== false) {
            wp_send_json_success(array(
                'archivo_nombre' => $archivo_nombre
            ));
        } else {
            wp_send_json_error('No se pudo actualizar el registro en la base de datos.');
        }
    }
}

$subir_trabajo = new SubirTrabajo();
$subir_trabajo->init();
