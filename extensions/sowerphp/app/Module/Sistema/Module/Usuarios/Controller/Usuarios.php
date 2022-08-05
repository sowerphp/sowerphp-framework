<?php

/**
 * SowerPHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

// namespace del controlador
namespace sowerphp\app\Sistema\Usuarios;

/**
 * Clase para el controlador asociado a la tabla usuario de la base de
 * datos
 * Comentario de la tabla: Usuarios de la aplicación
 * Esta clase permite controlar las acciones entre el modelo y vista para la
 * tabla usuario
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2016-02-22
 */
class Controller_Usuarios extends \sowerphp\app\Controller_Maintainer
{

    protected $namespace = __NAMESPACE__; ///< Namespace del controlador y modelos asociados
    protected $columnsView = [
        'listar'=>['id', 'nombre', 'usuario', 'email', 'activo', 'ultimo_ingreso_fecha_hora']
    ]; ///< Columnas que se deben mostrar en las vistas
    protected $deleteRecord = false; ///< Indica si se permite o no borrar registros
    protected $changeUsername = true; ///< Indica si se permite que se cambie el nombre de usuario

    /**
     * Permitir ciertas acciones y luego ejecutar verificar permisos con
     * parent::beforeFilter()
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2020-06-01
     */
    public function beforeFilter()
    {
        $this->Auth->allow('ingresar', 'salir', 'contrasenia_recuperar', 'registrar', 'preauth', '_api_perfil_GET');
        $this->Auth->allowWithLogin('perfil', 'telegram_parear');
        parent::beforeFilter();
    }

    /**
     * Acción para que un usuario ingrese al sistema (inicie sesión)
     * @param redirect Ruta (en base64) de hacia donde hay que redireccionar una vez se autentica el usuario
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2018-10-16
     */
    public function ingresar($redirect = null)
    {
        // si ya está logueado se redirecciona
        if ($this->Auth->logged()) {
            \sowerphp\core\Model_Datasource_Session::message(sprintf(
                'Usuario <em>%s</em> tiene su sesión iniciada. Para ingresar con un nuevo usuaro primero debe cerrar esta sesión.',
                $this->Auth->User->usuario
            ), 'info');
            $this->redirect(
                $this->Auth->settings['redirect']['login']
            );
        }
        // asignar variables para la vista
        $this->layout .= '.min';
        $this->set([
            'redirect' => $redirect ? base64_decode ($redirect) : null,
            'self_register' => (boolean)\sowerphp\core\Configure::read('app.self_register'),
            'language' => \sowerphp\core\Configure::read('language'),
            'auth2_token_enabled' => \sowerphp\app\Model_Datasource_Auth2::tokenEnabled(),
        ]);
        // procesar inicio de sesión
        if (isset($_POST['usuario'])) {
            // si el usuario o contraseña es vacio mensaje de error
            if (empty($_POST['usuario']) || empty($_POST['contrasenia'])) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Debe especificar usuario y clave', 'warning'
                );
            }
            // realizar proceso de validación de datos
            else {
                $public_key = \sowerphp\core\Configure::read('recaptcha.public_key');
                $auth2_token = !empty($_POST['auth2_token']) ? $_POST['auth2_token'] : null;
                $this->Auth->login($_POST['usuario'], $_POST['contrasenia'], $auth2_token);
                if ($this->Auth->User->contrasenia_intentos and $this->Auth->User->contrasenia_intentos<$this->Auth->settings['maxLoginAttempts']) {
                    $this->set('public_key', $public_key);
                }
            }
        }
    }

    /**
     * Acción para que un usuario cierra la sesión
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-23
     */
    public function salir()
    {
        if ($this->Auth->logged()) {
            $this->Auth->logout();
        } else {
            \sowerphp\core\Model_Datasource_Session::message(
                'No existe sesión de usuario abierta',
                'warning'
            );
            $this->redirect('/');
        }
    }

     /**
     * Acción que fuerza el cierre de sesión de un usuario eliminando su hash
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-08-11
     */
    public function salir_forzar($id)
    {
        $class = $this->Auth->settings['model'];
        $Usuario = new $class($id);
        if(!$Usuario->exists()) {
            \sowerphp\core\Model_Datasource_Session::message(
                'Usuario no existe, no se puede forzar el cierre de la sesión',
                'error'
            );
            $this->redirect('/sistema/usuarios/usuarios/listar');
        }
        $Usuario->ultimo_ingreso_hash = null;
        try {
            $Usuario->save();
            (new \sowerphp\core\Cache())->delete($this->Auth->settings['session']['key'].$id);
            \sowerphp\core\Model_Datasource_Session::message(
                'Sesión del usuario '.$Usuario->usuario.' cerrada',
                'ok'
            );
        } catch (\Exception $e) {
            \sowerphp\core\Model_Datasource_Session::message(
                'No fue posible forzar el cierre de la sesión: '.$e->getMessage(),
                'error'
            );
        }
        $this->redirect('/sistema/usuarios/usuarios/editar/'.$id);
    }

    /**
     * Acción para recuperar la contraseña
     * @param usuario Usuario al que se desea recuperar su contraseña
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2022-08-05
     */
    public function contrasenia_recuperar($usuario = null, $codigo = null)
    {
        $this->layout .= '.min';
        $this->autoRender = false;
        $class = $this->Auth->settings['model'];
        // pedir correo
        if ($usuario == null) {
            if (!isset($_POST['id'])) {
                $this->render('Usuarios/contrasenia_recuperar_step1');
            } else {
                try {
                    $Usuario = new $class($_POST['id']);
                } catch (\sowerphp\core\Exception_Model_Datasource_Database $e) {
                    $Usuario = new $class();
                }
                if (!$Usuario->exists()) {
                    \sowerphp\core\Model_Datasource_Session::message (
                        'Usuario no válido', 'error'
                    );
                    $this->render('Usuarios/contrasenia_recuperar_step1');
                }
                else if (!$Usuario->activo) {
                    \sowerphp\core\Model_Datasource_Session::message (
                        'Usuario no activo', 'error'
                    );
                    $this->render('Usuarios/contrasenia_recuperar_step1');
                }
                else {
                    $this->contrasenia_recuperar_email (
                        $Usuario->email,
                        $Usuario->nombre,
                        $Usuario->usuario,
                        md5(hash('sha256', $Usuario->contrasenia))
                    );
                    \sowerphp\core\Model_Datasource_Session::message (
                        'Se ha enviado un email con las instrucciones para recuperar su contraseña',
                        'ok'
                    );
                    $this->redirect('/usuarios/ingresar');
                }
            }
        }
        // cambiar contraseña
        else {
            $Usuario = new $class($usuario);
            if (!$Usuario->exists()) {
                \sowerphp\core\Model_Datasource_Session::message (
                    'Usuario inválido', 'error'
                );
                $this->redirect ('/usuarios/contrasenia/recuperar');
            }
            if (!isset($_POST['contrasenia1'])) {
                $this->set([
                    'usuario' => $usuario,
                    'codigo' => $codigo,
                ]);
                $this->render('Usuarios/contrasenia_recuperar_step2');
            } else {
                if ($_POST['codigo']!=md5(hash('sha256', $Usuario->contrasenia))) {
                    \sowerphp\core\Model_Datasource_Session::message (
                        'El enlace para recuperar su contraseña no es válido, solicite uno nuevo por favor', 'error'
                    );
                    $this->redirect('/usuarios/contrasenia/recuperar');
                }
                else if (empty ($_POST['contrasenia1']) || empty ($_POST['contrasenia2']) || $_POST['contrasenia1']!=$_POST['contrasenia2']) {
                    \sowerphp\core\Model_Datasource_Session::message (
                        'Contraseña nueva inválida (en blanco o no coinciden)', 'warning'
                    );
                    $this->set('usuario', $usuario);
                    $this->render ('Usuarios/contrasenia_recuperar_step2');
                }
                else {
                    $Usuario->savePassword($_POST['contrasenia1']);
                    $Usuario->savePasswordRetry($this->Auth->settings['maxLoginAttempts']);
                    \sowerphp\core\Model_Datasource_Session::message (
                        'La contraseña para el usuario '.$usuario.' ha sido cambiada con éxito',
                        'ok'
                    );
                    $this->redirect('/usuarios/ingresar');
                }
            }
        }
    }

    /**
     * Método que envía el correo con los datos para poder recuperar la contraseña
     * @param correo Donde enviar el email
     * @param nombre Nombre "real" del usuario
     * @param usuario Nombre de usuario
     * @param hash Hash para identificar que el usuario es quien dice ser y cambiar su contraseña
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2020-02-09
     */
    private function contrasenia_recuperar_email($correo, $nombre, $usuario, $hash)
    {
        $this->layout = null;
        $this->set (array(
            'nombre'=>$nombre,
            'usuario'=>$usuario,
            'hash'=>$hash,
            'ip'=>$this->Auth->ip(),
        ));
        $msg = $this->render('Usuarios/contrasenia_recuperar_email')->body();
        $email = new \sowerphp\core\Network_Email();
        $email->to($correo);
        $email->subject('Recuperación de contraseña');
        $status = $email->send($msg);
        if ($status !== true and $status['type']=='error') {
            \sowerphp\core\Model_Datasource_Session::message($status['message'], 'error');
            $this->redirect('/usuarios/contrasenia/recuperar');
        }
    }

    /**
     * Acción para crear un nuevo usuario
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-09-07
     */
    public function crear()
    {
        if (!empty($_GET['listar'])) {
            $filterListarUrl = '?listar='.$_GET['listar'];
            $filterListar = base64_decode($_GET['listar']);
        } else {
            $filterListarUrl = '';
            $filterListar = '';
        }
        $class = $this->Auth->settings['model'];
        // si se envió el formulario se procesa
        if (isset($_POST['submit'])) {
            $Usuario = new $class();
            $Usuario->set($_POST);
            $Usuario->email = strtolower($Usuario->email);
            $ok = true;
            if ($Usuario->checkIfUserAlreadyExists()) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Nombre de usuario '.$_POST['usuario'].' ya está en uso',
                    'warning'
                );
                $ok = false;
            }
            if ($ok and $Usuario->checkIfHashAlreadyExists ()) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Hash seleccionado ya está en uso', 'warning'
                );
                $ok = false;
            }
            if ($ok and $Usuario->checkIfEmailAlreadyExists ()) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Email seleccionado ya está en uso', 'warning'
                );
                $ok = false;
            }
            if ($ok) {
                if (empty($Usuario->contrasenia)) {
                    $Usuario->contrasenia = \sowerphp\core\Utility_String::random(8);
                }
                $contrasenia = $Usuario->contrasenia;
                $Usuario->contrasenia = $Usuario->hashPassword($Usuario->contrasenia);
                if (empty($Usuario->hash)) {
                    do {
                        $Usuario->hash = \sowerphp\core\Utility_String::random(32);
                    } while ($Usuario->checkIfHashAlreadyExists ());
                }
                if ($Usuario->save()) {
                    $Usuario->saveGroups($_POST['grupos']);
                    if (empty($_POST['contrasenia'])) {
                        if ($Usuario->getEmailAccount())
                            $contrasenia = 'actual contraseña de correo '.$Usuario->getEmailAccount()->getEmail();
                        else if ($Usuario->getLdapPerson())
                            $contrasenia = 'actual contraseña de cuenta '.$Usuario->getLdapPerson()->uid.' en LDAP';
                    } else {
                        $Usuario->savePassword($contrasenia);
                    }
                    // enviar correo
                    $emailConfig = \sowerphp\core\Configure::read('email.default');
                    if (!empty($emailConfig['type']) && !empty($emailConfig['from'])) {
                        $layout = $this->layout;
                        $this->layout = null;
                        $this->set(array(
                            'nombre'=>$Usuario->nombre,
                            'usuario'=>$Usuario->usuario,
                            'contrasenia'=>$contrasenia,
                        ));
                        $msg = $this->render('Usuarios/crear_email')->body();
                        $this->layout = $layout;
                        $email = new \sowerphp\core\Network_Email();
                        $email->to($Usuario->email);
                        $email->subject('Cuenta de usuario creada');
                        $email->send($msg);
                        \sowerphp\core\Model_Datasource_Session::message(
                            'Registro creado (se envió email a '.$Usuario->email.' con los datos de acceso)',
                            'ok'
                        );
                    } else {
                        \sowerphp\core\Model_Datasource_Session::message(
                            'Registro creado (no se envió correo)', 'warning'
                        );
                    }
                } else {
                    \sowerphp\core\Model_Datasource_Session::message(
                        'Registro no creado (hubo algún error)', 'error'
                    );
                }
                $this->redirect('/sistema/usuarios/usuarios/listar'.$filterListar);
            }
        }
        // setear variables
        $class::$columnsInfo['contrasenia']['null'] = true;
        $class::$columnsInfo['hash']['null'] = true;
        $this->set(array(
            'accion' => 'Crear',
            'columns' => $class::$columnsInfo,
            'grupos_asignados' => (isset($_POST['grupos'])?$_POST['grupos']:[]),
            'listarUrl'=>'/sistema/usuarios/usuarios/listar'.$filterListar,
            'ldap' => \sowerphp\core\Configure::read('ldap.default'),
        ));
        $this->setGruposAsignables();
        $this->autoRender = false;
        $this->render ('Usuarios/crear_editar');
    }

    /**
     * Acción para editar un nuevo usuario
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-09-07
     */
    public function editar($id)
    {
        if (!empty($_GET['listar'])) {
            $filterListarUrl = '?listar='.$_GET['listar'];
            $filterListar = base64_decode($_GET['listar']);
        } else {
            $filterListarUrl = '';
            $filterListar = '';
        }
        if (strpos($filterListar, 'http')===0) {
            $redirect = $filterListar;
        } else {
            $redirect = '/sistema/usuarios/usuarios/listar'.$filterListar;
        }
        $class = $this->Auth->settings['model'];
        $Usuario = new $class($id);
        // si el registro que se quiere editar no existe error
        if(!$Usuario->exists()) {
            \sowerphp\core\Model_Datasource_Session::message(
                'Registro ('.implode(', ', func_get_args()).') no existe, no se puede editar',
                'error'
            );
            $this->redirect($redirect);
        }
        // si no se ha enviado el formulario se mostrará
        if(!isset($_POST['submit'])) {
            $class::$columnsInfo['contrasenia']['null'] = true;
            $grupos_asignados = $Usuario->groups();
            $this->setGruposAsignables();
            $this->set(array(
                'accion' => 'Editar',
                'Obj' => $Usuario,
                'columns' => $class::$columnsInfo,
                'grupos_asignados' => array_keys($grupos_asignados),
                'listarUrl'=>$redirect,
                'ldap' => \sowerphp\core\Configure::read('ldap.default'),
            ));
            $this->autoRender = false;
            $this->render ('Usuarios/crear_editar');
        }
        // si se envió el formulario se procesa
        else {
            if (isset($_POST['usuario']) and !$this->changeUsername and $Usuario->usuario!=$_POST['usuario']) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Nombre de usuario no puede ser cambiado',
                    'warning'
                );
                $this->redirect('/sistema/usuarios/usuarios/editar/'.$id.$filterListarUrl);
            }
            $activo = $Usuario->activo;
            $Usuario->set($_POST);
            $Usuario->email = strtolower($Usuario->email);
            if ($Usuario->checkIfUserAlreadyExists ()) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Nombre de usuario '.$_POST['usuario'].' ya está en uso',
                    'warning'
                );
                $this->redirect('/sistema/usuarios/usuarios/editar/'.$id.$filterListarUrl);
            }
            if ($Usuario->checkIfHashAlreadyExists ()) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Hash seleccionado ya está en uso', 'warning'
                );
                $this->redirect('/sistema/usuarios/usuarios/editar/'.$id.$filterListarUrl);
            }
            if ($Usuario->checkIfEmailAlreadyExists ()) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Email seleccionado ya está en uso', 'warning'
                );
                $this->redirect('/sistema/usuarios/usuarios/editar/'.$id.$filterListarUrl);
            }
            $Usuario->save();
            // enviar correo solo si el usuario estaba inactivo y ahora está activo
            if (!$activo and $Usuario->activo) {
                $emailConfig = \sowerphp\core\Configure::read('email.default');
                    if (!empty($emailConfig['type']) && !empty($emailConfig['user']) && !empty($emailConfig['pass'])) {
                    $layout = $this->layout;
                    $this->layout = null;
                    $this->set([
                        'nombre'=>$Usuario->nombre,
                        'usuario'=>$Usuario->usuario,
                    ]);
                    $msg = $this->render('Usuarios/activo_email')->body();
                    $this->layout = $layout;
                    $email = new \sowerphp\core\Network_Email();
                    $email->to($Usuario->email);
                    $email->subject('Cuenta de usuario habilitada');
                    $email->send($msg);
                }
            }
            if (!empty($_POST['contrasenia'])) {
                $Usuario->savePassword($_POST['contrasenia']);
                $Usuario->savePasswordRetry($this->Auth->settings['maxLoginAttempts']);
            }
            $Usuario->saveGroups($_POST['grupos']);
            \sowerphp\core\Model_Datasource_Session::message(
                'Registro Usuario('.implode(', ', func_get_args()).') editado',
                'ok'
            );
            $this->redirect($redirect);
        }
    }

    /**
     * Método que asigna los grupos que el usuario logueado puede asignar al
     * crear o editar un usuario
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-08-02
     */
    private function setGruposAsignables ()
    {
        $grupos = (new Model_Grupos())->getList();
        // si el usuario no pertenece al grupo sysadmin quitar los grupos
        // sysadmin y appadmin del listado para evitar que los asignen
        if (!$this->Auth->User->inGroup('sysadmin')) {
            $aux = $grupos;
            $grupos = [];
            foreach ($aux as $key => &$grupo) {
                if (!in_array($grupo['glosa'], ['sysadmin', 'appadmin'])) {
                    $grupos[] = $grupo;
                }
            }
            unset ($aux);
        }
        $this->set('grupos', $grupos);
    }

    /**
     * Acción para mostrar y editar el perfil del usuario que esta autenticado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2018-10-31
     */
    public function perfil()
    {
        // si hay cualquier campo que empiece por 'config_' se quita ya que son
        // configuraciones reservadas para los administradores de la APP y no pueden
        // ser asignadas por los usuarios (esto evita que envién "a la mala" una
        // configuración). Si se desea que el usuario pueda configurar alguna
        // configuración personalizada en el perfil del usuario, se deberá enviar a una
        // acción diferente en un Controlador de usuarios personalizado (que herede este)
        foreach ($_POST as $var => $val) {
            if (strpos($var, 'config_')===0) {
                unset($_POST[$var]);
            }
        }
        // procesar datos personales
        if (isset($_POST['datosUsuario'])) {
            // actualizar datos generales
            if (isset($_POST['usuario']) and !$this->changeUsername and $this->Auth->User->usuario!=$_POST['usuario']) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Nombre de usuario no puede ser cambiado',
                    'error'
                );
                $this->redirect('/usuarios/perfil');
            }
            $this->Auth->User->nombre = $_POST['nombre'];
            if ($this->changeUsername and !empty($_POST['usuario'])) {
                $this->Auth->User->usuario = $_POST['usuario'];
            }
            $this->Auth->User->email = strtolower($_POST['email']);
            if (isset($_POST['hash'])) {
                $this->Auth->User->hash = $_POST['hash'];
                if (!empty($this->Auth->User->hash) and strlen($this->Auth->User->hash)!=32) {
                    \sowerphp\core\Model_Datasource_Session::message(
                        'Hash del usuario debe ser de largo 32',
                        'error'
                    );
                    $this->redirect('/usuarios/perfil');
                }
            }
            if ($this->Auth->User->checkIfUserAlreadyExists ()) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Nombre de usuario '.$_POST['usuario'].' ya está en uso',
                    'error'
                );
                $this->redirect('/usuarios/perfil');
            }
            if ($this->Auth->User->checkIfHashAlreadyExists ()) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Hash seleccionado ya está en uso', 'error'
                );
                $this->redirect('/usuarios/perfil');
            }
            if ($this->Auth->User->checkIfEmailAlreadyExists ()) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Email seleccionado ya está en uso', 'error'
                );
                $this->redirect('/usuarios/perfil');
            }
            if (empty($this->Auth->User->hash)) {
                do {
                    $this->Auth->User->hash = \sowerphp\core\Utility_String::random(32);
                } while ($this->Auth->User->checkIfHashAlreadyExists ());
            }
            $this->Auth->User->save();
            $this->Auth->saveCache();
            // mensaje de ok y redireccionar
            \sowerphp\core\Model_Datasource_Session::message(
                'Perfil actualizado', 'ok'
            );
            $this->redirect('/usuarios/perfil');
        }
        // procesar cambio de contraseña
        else if (isset($_POST['cambiarContrasenia'])) {
            // verificar que las contraseñas no sean vacías
            if (empty($_POST['contrasenia']) or empty(trim($_POST['contrasenia1'])) or empty($_POST['contrasenia2'])) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Debe especificar su contraseña actual y escribir dos veces su nueva contraseña', 'error'
                );
                $this->redirect('/usuarios/perfil');
            }
            // verificar que la contraseña actual sea correcta
            if (!$this->Auth->User->checkPassword($_POST['contrasenia'])) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Contraseña actual es incorrecta', 'error'
                );
                $this->redirect('/usuarios/perfil');
            }
            // verificar que la contraseña nueva se haya escrito 2 veces de forma correcta
            if ($_POST['contrasenia1']!=$_POST['contrasenia2']) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Contraseñas no coinciden', 'error'
                );
                $this->redirect('/usuarios/perfil');
            }
            // actualizar contraseña
            if ($this->Auth->User->savePassword($_POST['contrasenia1'], $_POST['contrasenia'])) {
                $this->Auth->saveCache();
                \sowerphp\core\Model_Datasource_Session::message(
                    'Contraseña actualizada', 'ok'
                );
            } else {
                \sowerphp\core\Model_Datasource_Session::message(
                    'No fue posible cambiar la contraseña', 'error'
                );
            }
            $this->redirect('/usuarios/perfil');
        }
        // procesar creación de la autenticación secundaria
        else if (isset($_POST['crearAuth2'])) {
            unset($_POST['crearAuth2']);
            try {
                $this->Auth->User->createAuth2($_POST);
                $this->Auth->saveCache();
                \sowerphp\core\Model_Datasource_Session::message(
                    'Desde ahora la cuenta está protegida con '.$_POST['auth2'],
                    'ok'
                );
            } catch (\Exception $e) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'No fue posible proteger la cuenta con '.$_POST['auth2'].': '.$e->getMessage(), 'error'
                );
            }
            $this->redirect('/usuarios/perfil#auth');
        }
        // procesar destrucción de la autenticación secundaria
        else if (isset($_POST['destruirAuth2'])) {
            unset($_POST['destruirAuth2']);
            try {
                $this->Auth->User->destroyAuth2($_POST);
                $this->Auth->saveCache();
                \sowerphp\core\Model_Datasource_Session::message(
                    'Su cuenta ya no está protegida con '.$_POST['auth2'], 'ok'
                );
            } catch (\Exception $e) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'No fue posible eliminar la protección con '.$_POST['auth2'].': '.$e->getMessage(), 'error'
                );
            }
            $this->redirect('/usuarios/perfil#auth');
        }
        // mostrar formulario para edición
        else {
            $this->set([
                'changeUsername' => $this->changeUsername,
                'qrcode' => base64_encode($this->request->url.';'.$this->Auth->User->hash),
                'auths2' => \sowerphp\app\Model_Datasource_Auth2::getAll(),
                'layouts' => (array)\sowerphp\core\Configure::read('page.layouts'),
                'layout' => $this->Auth->User->config_page_layout ? $this->Auth->User->config_page_layout : $this->layout,
            ]);
        }
    }

    /**
     * Acción que permite registrar un nuevo usuario en la aplicación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2018-10-16
     */
    public function registrar()
    {
        // si ya está logueado se redirecciona
        if ($this->Auth->logged()) {
            \sowerphp\core\Model_Datasource_Session::message(sprintf(
                'Usuario <em>%s</em> tiene su sesión iniciada. Para registrar un nuevo usuaro primero debe cerrar esta sesión.',
                $this->Auth->User->usuario
            ));
            $this->redirect(
                $this->Auth->settings['redirect']['login']
            );
        }
        // si no se permite el registro se redirecciona
        $config = \sowerphp\core\Configure::read('app.self_register');
        if (!$config) {
            \sowerphp\core\Model_Datasource_Session::message(
                'Registro de usuarios deshabilitado', 'error'
            );
            $this->redirect(
                $this->Auth->settings['redirect']['login']
            );
        }
        // colocar variable para captcha (si está configurado)
        $public_key = \sowerphp\core\Configure::read('recaptcha.public_key');
        if ($public_key) {
            $this->set([
                'public_key' => $public_key,
                'language' => \sowerphp\core\Configure::read('language'),
            ]);
        }
        // colocar variable para terminos si está configurado
        if (!empty($config['terms'])) {
            $this->set('terms', $config['terms']);
        }
        $this->layout .= '.min';
        // si se envió formulario se procesa
        if (isset($_POST['usuario'])) {
            // verificar que campos no sean vacios
            if (empty($_POST['nombre']) or empty($_POST['usuario']) or empty($_POST['email'])) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Debe completar todos los campos del formulario', 'warning'
                );
                return;
            }
            // si existe la configuración para recaptcha se debe validar
            $private_key = \sowerphp\core\Configure::read('recaptcha.private_key');
            if ($private_key) {
                if (empty($_POST['g-recaptcha-response'])) {
                    \sowerphp\core\Model_Datasource_Session::message(
                        'Se requiere Captcha para poder registrar un nuevo usuario',
                        'warning'
                    );
                    return;
                }
                $recaptcha = new \ReCaptcha\ReCaptcha($private_key);
                $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
                if (!$resp->isSuccess()) {
                    \sowerphp\core\Model_Datasource_Session::message(
                        'Captcha incorrecto', 'error'
                    );
                    return;
                }
            }
            // si existen términos y no se aceptaron se redirecciona
            if (!empty($config['terms']) and empty($_POST['terms_ok'])) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Debe aceptar los términos y condiciones', 'warning'
                );
                return;
            }
            // validar que el usuario y/o correo no exista previamente
            $class = $this->Auth->settings['model'];
            $Usuario = new $class();
            $Usuario->nombre = $_POST['nombre'];
            $Usuario->usuario = $_POST['usuario'];
            $Usuario->email = strtolower($_POST['email']);
            if ($Usuario->checkIfUserAlreadyExists()) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Nombre de usuario '.$_POST['usuario'].' ya está en uso',
                    'warning'
                );
                return;
            }
            if ($Usuario->checkIfEmailAlreadyExists()) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Email seleccionado ya está en uso', 'warning'
                );
                return;
            }
            // asignar contraseña al usuario
            $contrasenia = \sowerphp\core\Utility_String::random(8);
            $Usuario->contrasenia = $Usuario->hashPassword($contrasenia);
            // asignar hash al usuario
            do {
                $Usuario->hash = \sowerphp\core\Utility_String::random(32);
            } while ($Usuario->checkIfHashAlreadyExists ());
            if ($Usuario->save()) {
                // asignar grupos por defecto al usuario
                if (is_array($config) and !empty($config['groups']))
                    $Usuario->saveGroups($config['groups']);
                // enviar correo
                $emailConfig = \sowerphp\core\Configure::read('email.default');
                if (!empty($emailConfig['type']) && !empty($emailConfig['from'])) {
                    $layout = $this->layout;
                    $this->layout = null;
                    $this->set([
                        'nombre'=>$Usuario->nombre,
                        'usuario'=>$Usuario->usuario,
                        'contrasenia'=>$contrasenia,
                    ]);
                    $msg = $this->render('Usuarios/crear_email')->body();
                    $this->layout = $layout;
                    $email = new \sowerphp\core\Network_Email();
                    $email->to($Usuario->email);
                    $email->subject('Cuenta de usuario creada');
                    $email->send($msg);
                    \sowerphp\core\Model_Datasource_Session::message(
                        'Registro creado, se envió contraseña a '.$Usuario->email,
                        'ok'
                    );
                } else {
                    \sowerphp\core\Model_Datasource_Session::message(
                        'Registro creado, su contraseña es <em>'.$contrasenia.'</em>', 'warning'
                    );
                }
            } else {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Registro de usuario falló por algún motivo', 'error'
                );
            }
            $this->redirect('/usuarios/ingresar');
        }
    }

    /**
     * Acción que permite ingresar a la aplicación con un usuario ya autenticado
     * a través de un token provisto
     * @param token Token de pre autenticación para validar la sesión
     * @param usuario Usuario con el que se desea ingresar
     * @param url URL a la cual redireccionar el usuario una vez ha iniciado sesión
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-12-23
     */
    public function preauth($token = null, $usuario = null, $url = null)
    {
        // si se pasaron datos por POST tienen preferencia
        if (!empty($_POST['token'])) {
            $token = $_POST['token'];
            $usuario = !empty($_POST['usuario']) ? $_POST['usuario'] : null;
            $url = !empty($_POST['url']) ? $_POST['url'] : null;
        }
        // buscar clave de preauth, si no existe se indica que la
        // preautenticación no está disponible
        $enabled = \sowerphp\core\Configure::read('preauth.enabled');
        if (!$enabled) {
            \sowerphp\core\Model_Datasource_Session::message(
                'La preautenticación no está disponible', 'error'
            );
            $this->redirect('/usuarios/ingresar');
        }
        if ($usuario) {
            $key = \sowerphp\core\Configure::read('preauth.key');
            if (!$key) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'No hay clave global para preautenticación', 'error'
                );
                $this->redirect('/usuarios/ingresar');
            }
        }
        // definir url
        $url = $url ? base64_decode($url) : $this->Auth->settings['redirect']['login'];
        // si ya está logueado se redirecciona de forma silenciosa
        if ($this->Auth->logged()) {
            $this->redirect($url);
        }
        // procesar inicio de sesión con preauth, si no se puede autenticar se
        // genera un error
        $auth2_token = !empty($_GET['auth2_token']) ? $_GET['auth2_token'] : null;
        if (!$this->Auth->preauth($token, $usuario, $auth2_token)) {
            \sowerphp\core\Model_Datasource_Session::message(
                'La preautenticación del usuario falló', 'error'
            );
            $this->redirect('/usuarios/ingresar');
        }
        // todo ok -> redirigir
        $this->redirect($url);
    }

    /**
     * Acción que verifica el token ingresado y hace el pareo con telegram
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-10-18
     */
    public function telegram_parear()
    {
        if (!empty($_POST['telegram_token'])) {
            $token = $_POST['telegram_token'];
            $telegram_user = $this->Cache->get('telegram.pairing.'.$token);
            // si no se encontró el usuario el token no es válido o expiró
            if (!$telegram_user) {
                \sowerphp\core\Model_Datasource_Session::message('Token no válido o expiró, por favor, solicite uno nuevo al Bot con <strong><em>/token</em></strong>', 'error');
            }
            // se encontró el usuario, entonces guardar los datos del usuario de Telegram en el usuario de la aplicación web
            else {
                // verificar que no exista ya el usuario
                $Usuario = (new \sowerphp\app\Sistema\Usuarios\Model_Usuarios())->getUserByTelegramID(
                    $telegram_user['id'], $this->Auth->settings['model']
                );
                // cuenta de telegram ya está pareada
                if ($Usuario) {
                    \sowerphp\core\Model_Datasource_Session::message('La cuenta de Telegram ya está pareada al usuario '.$Usuario->usuario.' del sistema, primero debe cerrar la sesión de dicha cuenta', 'error');
                }
                // cuenta de telegram no está pareada, guardar
                else {
                    $this->Auth->User->set([
                        'config_telegram_id' => $telegram_user['id'],
                        'config_telegram_username' => $telegram_user['username'],
                    ]);
                    try {
                        $this->Auth->User->save();
                        $this->Auth->saveCache();
                        $this->Cache->delete('telegram.pairing.'.$token);
                        \sowerphp\core\Model_Datasource_Session::message('Usuario @'.$telegram_user['username'].' pareado con éxito', 'ok');
                    } catch (\Exception $e) {
                        \sowerphp\core\Model_Datasource_Session::message('Ocurrió un error al parear con Telegram: '.$e->getMessage(), 'error');
                    }
                }
            }
        }
        $this->redirect('/usuarios/perfil#apps');
    }

    /**
     * Acción que desparea al usuario de Telegram
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-10-16
     */
    public function telegram_desparear()
    {
        $this->Auth->User->set([
            'config_telegram_id' => null,
            'config_telegram_username' => null,
        ]);
        try {
            $this->Auth->User->save();
            $this->Auth->saveCache();
            \sowerphp\core\Model_Datasource_Session::message('Su cuenta ya no está asociada a Telegram', 'ok');
        } catch (\Exception $e) {
            \sowerphp\core\Model_Datasource_Session::message('Ocurrió un error al eliminar su cuenta de Telegram: '.$e->getMessage(), 'error');
        }
        $this->redirect('/usuarios/perfil#apps');
    }

    /**
     * Método que permite cambiar el layout por defecto del usuario
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2018-10-31
     */
    public function layout($layout = null)
    {
        // verificar se haya indicado un layout
        $layout = !empty($_POST['layout']) ? $_POST['layout'] : $layout;
        if (!$layout) {
            \sowerphp\core\Model_Datasource_Session::message('Debe indicar el nuevo diseño que desea utilizar en la aplicación', 'error');
            $this->redirect('/usuarios/perfil');
        }
        // cambiar layout
        $this->Auth->User->set([
            'config_page_layout' => $layout,
        ]);
        $this->Auth->User->save();
        $this->Auth->saveCache();
        \sowerphp\core\Model_Datasource_Session::message('Se modificó el diseño por defecto de su cuenta', 'ok');
        $this->redirect('/session/config/page.layout/'.$layout.'/'.base64_encode('/usuarios/perfil'));
    }

    /**
     * Función de la API que permite obtener el perfil del usuario autenticado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2021-08-30
     */
    public function _api_perfil_GET()
    {
        $User = $this->Api->getAuthUser(false);
        if (is_string($User)) {
            $this->Api->send($User, 401);
        }
        extract($this->getQuery([
            'login' => false,
        ]));
        if ($login) {
            $User->updateLastLogin(
                $this->Auth->ip(true),
                $this->Auth->settings['multipleLogins']
            );
        }
        return [
            'id' => $User->id,
            'nombre' => $User->nombre,
            'usuario' => $User->usuario,
            'email' => $User->email,
            'hash' =>
                $User->getAuth2()
                ? (
                    (!empty($_GET['auth2_token']) and $User->checkAuth2($_GET['auth2_token']))
                    ? $User->hash
                    : null
                )
                : $User->hash,
            'ultimo_ingreso' => $User->lastLogin(),
            'grupos' => array_values($User->groups()),
        ];
    }

}
