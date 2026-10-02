<?php
/**
 * Plugin Name: Editar Trabajo Académico
 * Description: Permite a los usuarios editar sus trabajos enviados usando su email
 * Version: 1.0
 * Author: mrodriguez
 */

// Evitar acceso directo
if (!defined('ABSPATH')) exit;

class EditarTrabajo {

    private $tabla_nombre = 'trabajos_academicos';

    public function init() {
        // Registrar shortcode
        add_shortcode('editar_trabajo', array($this, 'mostrar_formulario'));

        // Procesar formularios vía AJAX
        add_action('wp_ajax_nopriv_buscar_trabajo', array($this, 'buscar_trabajo'));
        add_action('wp_ajax_buscar_trabajo', array($this, 'buscar_trabajo'));

        add_action('wp_ajax_nopriv_actualizar_trabajo', array($this, 'actualizar_trabajo'));
        add_action('wp_ajax_actualizar_trabajo', array($this, 'actualizar_trabajo'));

        add_action('wp_ajax_nopriv_obtener_nonce_edicion', array($this, 'obtener_nonce_edicion'));
        add_action('wp_ajax_obtener_nonce_edicion', array($this, 'obtener_nonce_edicion'));

        // Estilos
        add_action('wp_enqueue_scripts', array($this, 'cargar_estilos_frontend'));
    }

    public function obtener_nonce_edicion() {
        wp_send_json_success(wp_create_nonce('editar_trabajo_nonce'));
    }

    public function cargar_estilos_frontend() {
        ?>
        <style>
            .editar-trabajo-container {
                max-width: 700px;
                margin: 30px auto;
                padding: 30px;
                background: #ffffff;
                border-radius: 8px;
                box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            }
            .editar-trabajo-container h2 {
                margin-top: 0;
                color: #333;
                border-bottom: 3px solid #0073aa;
                padding-bottom: 15px;
            }
            .et-form-group {
                margin-bottom: 25px;
            }
            .et-form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #333;
                font-size: 14px;
            }
            .et-form-group label .requerido {
                color: #dc3232;
            }
            .et-form-group input[type="text"],
            .et-form-group input[type="email"] {
                width: 100%;
                padding: 12px;
                border: 2px solid #ddd;
                border-radius: 4px;
                font-size: 15px;
                transition: border-color 0.3s;
                box-sizing: border-box;
            }
            .et-form-group input[type="text"]:focus,
            .et-form-group input[type="email"]:focus {
                outline: none;
                border-color: #0073aa;
            }
            .et-checkbox-group {
                display: flex;
                gap: 30px;
                margin-top: 10px;
            }
            .et-checkbox-group label {
                font-weight: normal;
                display: flex;
                align-items: center;
                cursor: pointer;
            }
            .et-checkbox-group input[type="checkbox"] {
                margin-right: 8px;
                width: 18px;
                height: 18px;
                cursor: pointer;
            }
            .et-btn {
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
            .et-btn:hover {
                background: #005177;
            }
            .et-btn:disabled {
                background: #999;
                cursor: not-allowed;
            }
            .et-btn-secondary {
                background: #6c757d;
                margin-top: 10px;
            }
            .et-btn-secondary:hover {
                background: #5a6268;
            }
            .et-mensaje-exito {
                background: #46b450;
                color: white;
                padding: 15px 20px;
                border-radius: 4px;
                margin-bottom: 20px;
                font-weight: 500;
            }
            .et-mensaje-error {
                background: #dc3232;
                color: white;
                padding: 15px 20px;
                border-radius: 4px;
                margin-bottom: 20px;
                font-weight: 500;
            }
            .et-mensaje-info {
                background: #0073aa;
                color: white;
                padding: 15px 20px;
                border-radius: 4px;
                margin-bottom: 20px;
                font-weight: 500;
            }
            .et-hidden {
                display: none;
            }
            .et-datos-actuales {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 4px;
                margin-bottom: 20px;
                border-left: 4px solid #0073aa;
            }
            .et-datos-actuales h3 {
                margin-top: 0;
                color: #0073aa;
                font-size: 16px;
            }
            .et-datos-actuales p {
                margin: 8px 0;
                color: #333;
            }
            .et-datos-actuales strong {
                color: #000;
            }
        </style>
        <?php
    }

    public function mostrar_formulario($atts) {
        ob_start();
        ?>

        <div class="editar-trabajo-container">
            <h2>Editar Mi Trabajo</h2>

            <div id="et-mensajes"></div>

            <!-- Formulario de búsqueda por email -->
            <div id="et-buscar-form">
                <p>Ingrese su email para buscar y editar su trabajo enviado:</p>
                <form id="et-form-buscar">
                    <div class="et-form-group">
                        <label for="et-email-buscar">Email <span class="requerido">*</span></label>
                        <input type="email" id="et-email-buscar" name="email" required>
                    </div>
                    <button type="submit" class="et-btn" id="et-btn-buscar">Buscar Mi Trabajo</button>
                </form>
            </div>

            <!-- Formulario de edición (oculto inicialmente) -->
            <div id="et-editar-form" class="et-hidden">
                <div class="et-datos-actuales" id="et-datos-actuales"></div>

                <h3>Modificar Datos</h3>
                <form id="et-form-actualizar">
                    <input type="hidden" id="et-trabajo-id" name="trabajo_id">

                    <div class="et-form-group">
                        <label for="et-nombre">Nombre <span class="requerido">*</span></label>
                        <input type="text" id="et-nombre" name="nombre" required>
                    </div>

                    <div class="et-form-group">
                        <label for="et-apellido">Apellido <span class="requerido">*</span></label>
                        <input type="text" id="et-apellido" name="apellido" required>
                    </div>

                    <div class="et-form-group">
                        <label for="et-email">Email <span class="requerido">*</span></label>
                        <input type="email" id="et-email" name="email" required>
                    </div>

                    <div class="et-form-group">
                        <label for="et-titulo">Título del Trabajo <span class="requerido">*</span></label>
                        <input type="text" id="et-titulo" name="titulo_trabajo" required>
                    </div>

                    <div class="et-form-group">
                        <label>Idioma de Presentación <span class="requerido">*</span></label>
                        <div class="et-checkbox-group">
                            <label>
                                <input type="checkbox" name="idioma_presentacion[]" value="Español" id="et-idioma-es">
                                Español
                            </label>
                            <label>
                                <input type="checkbox" name="idioma_presentacion[]" value="Portugués" id="et-idioma-pt">
                                Portugués
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="et-btn" id="et-btn-actualizar">Guardar Cambios</button>
                    <button type="button" class="et-btn et-btn-secondary" id="et-btn-cancelar">Cancelar</button>
                </form>
            </div>
        </div>

        <script>
        (function() {
            var ajaxUrl = '<?php echo admin_url("admin-ajax.php"); ?>';
            var mensajes = document.getElementById('et-mensajes');
            var buscarForm = document.getElementById('et-buscar-form');
            var editarForm = document.getElementById('et-editar-form');
            var formBuscar = document.getElementById('et-form-buscar');
            var formActualizar = document.getElementById('et-form-actualizar');
            var btnBuscar = document.getElementById('et-btn-buscar');
            var btnActualizar = document.getElementById('et-btn-actualizar');
            var btnCancelar = document.getElementById('et-btn-cancelar');

            function mostrarMensaje(texto, tipo) {
                var clase = 'et-mensaje-error';
                if (tipo === 'exito') clase = 'et-mensaje-exito';
                if (tipo === 'info') clase = 'et-mensaje-info';

                mensajes.innerHTML = '<div class="' + clase + '">' + texto + '</div>';
                mensajes.scrollIntoView({behavior: 'smooth'});
            }

            function obtenerNonceFresco() {
                return fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=obtener_nonce_edicion'
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        return data.data;
                    }
                    throw new Error('No se pudo obtener el token de seguridad');
                });
            }

            // Buscar trabajo por email
            formBuscar.addEventListener('submit', function(e) {
                e.preventDefault();
                mensajes.innerHTML = '';

                var email = document.getElementById('et-email-buscar').value.trim();
                if (!email) {
                    mostrarMensaje('Por favor ingrese su email.', 'error');
                    return;
                }

                btnBuscar.disabled = true;
                btnBuscar.textContent = 'Buscando...';

                obtenerNonceFresco()
                .then(function(nonce) {
                    var formData = new FormData();
                    formData.append('action', 'buscar_trabajo');
                    formData.append('nonce', nonce);
                    formData.append('email', email);

                    return fetch(ajaxUrl, {
                        method: 'POST',
                        body: formData
                    });
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    btnBuscar.disabled = false;
                    btnBuscar.textContent = 'Buscar Mi Trabajo';

                    if (data.success) {
                        var trabajo = data.data;
                        cargarDatosTrabajo(trabajo);
                        buscarForm.classList.add('et-hidden');
                        editarForm.classList.remove('et-hidden');
                        mostrarMensaje('✓ Trabajo encontrado. Puede modificar los datos a continuación.', 'info');
                    } else {
                        mostrarMensaje('✗ ' + data.data, 'error');
                    }
                })
                .catch(function(error) {
                    btnBuscar.disabled = false;
                    btnBuscar.textContent = 'Buscar Mi Trabajo';
                    mostrarMensaje('Error: ' + (error.message || 'Error de conexión.'), 'error');
                });
            });

            function cargarDatosTrabajo(trabajo) {
                // Mostrar datos actuales
                var datosHTML = '<h3>Datos Actuales</h3>';
                datosHTML += '<p><strong>Nombre:</strong> ' + trabajo.nombre + '</p>';
                datosHTML += '<p><strong>Apellido:</strong> ' + trabajo.apellido + '</p>';
                datosHTML += '<p><strong>Email:</strong> ' + trabajo.email + '</p>';
                datosHTML += '<p><strong>Título:</strong> ' + trabajo.titulo_trabajo + '</p>';
                datosHTML += '<p><strong>Idioma:</strong> ' + trabajo.idioma_presentacion + '</p>';
                datosHTML += '<p><strong>Fecha de registro:</strong> ' + trabajo.fecha_registro + '</p>';
                document.getElementById('et-datos-actuales').innerHTML = datosHTML;

                // Cargar datos en el formulario
                document.getElementById('et-trabajo-id').value = trabajo.id;
                document.getElementById('et-nombre').value = trabajo.nombre;
                document.getElementById('et-apellido').value = trabajo.apellido;
                document.getElementById('et-email').value = trabajo.email;
                document.getElementById('et-titulo').value = trabajo.titulo_trabajo;

                // Cargar idiomas seleccionados
                var idiomas = trabajo.idioma_presentacion.split(',').map(function(i) { return i.trim(); });
                document.getElementById('et-idioma-es').checked = idiomas.includes('Español');
                document.getElementById('et-idioma-pt').checked = idiomas.includes('Portugués');
            }

            // Actualizar trabajo
            formActualizar.addEventListener('submit', function(e) {
                e.preventDefault();
                mensajes.innerHTML = '';

                var idiomasSeleccionados = formActualizar.querySelectorAll('input[name="idioma_presentacion[]"]:checked');
                if (idiomasSeleccionados.length === 0) {
                    mostrarMensaje('Por favor seleccione al menos un idioma de presentación.', 'error');
                    return;
                }

                var idiomas = Array.from(idiomasSeleccionados).map(function(cb) { return cb.value; });

                btnActualizar.disabled = true;
                btnActualizar.textContent = 'Guardando...';

                obtenerNonceFresco()
                .then(function(nonce) {
                    var formData = new FormData();
                    formData.append('action', 'actualizar_trabajo');
                    formData.append('nonce', nonce);
                    formData.append('trabajo_id', document.getElementById('et-trabajo-id').value);
                    formData.append('nombre', document.getElementById('et-nombre').value.trim());
                    formData.append('apellido', document.getElementById('et-apellido').value.trim());
                    formData.append('email', document.getElementById('et-email').value.trim());
                    formData.append('titulo_trabajo', document.getElementById('et-titulo').value.trim());
                    formData.append('idioma_presentacion', idiomas.join(', '));

                    return fetch(ajaxUrl, {
                        method: 'POST',
                        body: formData
                    });
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    btnActualizar.disabled = false;
                    btnActualizar.textContent = 'Guardar Cambios';

                    if (data.success) {
                        mostrarMensaje('✓ ¡Datos actualizados exitosamente!', 'exito');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        mostrarMensaje('✗ Error: ' + data.data, 'error');
                    }
                })
                .catch(function(error) {
                    btnActualizar.disabled = false;
                    btnActualizar.textContent = 'Guardar Cambios';
                    mostrarMensaje('Error: ' + (error.message || 'Error de conexión.'), 'error');
                });
            });

            // Cancelar edición
            btnCancelar.addEventListener('click', function() {
                editarForm.classList.add('et-hidden');
                buscarForm.classList.remove('et-hidden');
                mensajes.innerHTML = '';
                formBuscar.reset();
            });
        })();
        </script>

        <?php
        return ob_get_clean();
    }

    public function buscar_trabajo() {
        global $wpdb;

        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'editar_trabajo_nonce')) {
            wp_send_json_error('Seguridad inválida.');
        }

        $email = sanitize_email($_POST['email']);

        if (empty($email)) {
            wp_send_json_error('Debe ingresar un email válido.');
        }

        $tabla = $wpdb->prefix . $this->tabla_nombre;
        $trabajo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE email = %s",
            $email
        ), ARRAY_A);

        if (!$trabajo) {
            wp_send_json_error('No se encontró ningún trabajo registrado con este email.');
        }

        // Formatear fecha para mostrar
        $trabajo['fecha_registro'] = date('d/m/Y H:i', strtotime($trabajo['fecha_registro']));

        wp_send_json_success($trabajo);
    }

    public function actualizar_trabajo() {
        global $wpdb;

        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'editar_trabajo_nonce')) {
            wp_send_json_error('Seguridad inválida.');
        }

        $trabajo_id = intval($_POST['trabajo_id']);
        $email = sanitize_email($_POST['email']);

        if (empty($email) || $trabajo_id <= 0) {
            wp_send_json_error('Datos inválidos.');
        }

        $tabla = $wpdb->prefix . $this->tabla_nombre;

        // Verificar que el trabajo existe
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE id = %d",
            $trabajo_id
        ));

        if ($existe == 0) {
            wp_send_json_error('El trabajo no existe.');
        }

        // Verificar si el nuevo email ya está siendo usado por otro registro
        $email_duplicado = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE email = %s AND id != %d",
            $email,
            $trabajo_id
        ));

        if ($email_duplicado > 0) {
            wp_send_json_error('El email ya está siendo utilizado por otro registro.');
        }

        // Actualizar datos
        $resultado = $wpdb->update(
            $tabla,
            array(
                'nombre' => sanitize_text_field($_POST['nombre']),
                'apellido' => sanitize_text_field($_POST['apellido']),
                'email' => $email,
                'titulo_trabajo' => sanitize_text_field($_POST['titulo_trabajo']),
                'idioma_presentacion' => sanitize_text_field($_POST['idioma_presentacion'])
            ),
            array('id' => $trabajo_id)
        );

        if ($resultado !== false) {
            wp_send_json_success('Datos actualizados correctamente.');
        } else {
            wp_send_json_error('No se pudo actualizar la base de datos.');
        }
    }
}

// Inicializar plugin
$editar_trabajo = new EditarTrabajo();
$editar_trabajo->init();
