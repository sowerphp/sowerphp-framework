<?php

/**
 * SowerPHP: Simple and Open Web Ecosystem Reimagined for PHP.
 * Copyright (C) SowerPHP <https://www.sowerphp.org>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero
 * de GNU publicada por la Fundación para el Software Libre, ya sea la
 * versión 3 de la Licencia, o (a su elección) cualquier versión
 * posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU
 * para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General
 * Affero de GNU junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace sowerphp\app\Sistema\Usuarios;

use \sowerphp\core\Network_Request as Request;
use \sowerphp\core\Facade_Session_Message as SessionMessage;

/**
 * Clase para el controlador asociado a la tabla usuario de la base de
 * datos.
 * Comentario de la tabla: Usuarios de la aplicación
 * Esta clase permite controlar las acciones entre el modelo y vista para la
 * tabla usuario.
 */
class Controller_Usuarios extends \sowerphp\autoload\Controller_Model
{

    protected $columnsView = [
        'listar' => [
            'id',
            'nombre',
            'usuario',
            'email',
            'activo',
            'ultimo_ingreso_fecha_hora',
        ],
    ]; ///< Columnas que se deben mostrar en las vistas
    protected $deleteRecord = false; ///< Indica si se permite o no borrar registros
    protected $changeUsername = true; ///< Indica si se permite que se cambie el nombre de usuario

    /**
     * Permitir ciertas acciones y luego ejecutar verificar permisos con
     * parent::boot()
     */
    public function boot(): void
    {
        app('auth')->allowActionsWithoutLogin(
            'ingresar',
            'salir',
            'contrasenia_recuperar',
            'registrar',
            'preauth',
        );
        app('auth')->allowActionsWithLogin(
            'perfil',
            'telegram_parear',
        );
        parent::boot();
    }

    /**
     * Acción para que un usuario ingrese al sistema (inicie sesión).
     *
     * @param string $redirect Ruta (en base64) de hacia donde hay que
     * redireccionar una vez se autentica el usuario.
     */
    public function ingresar(?string $redirect = null)
    {
        // Si ya está logueado se redirecciona.
        if (auth()->check()) {
            return redirect('/');
        }
        // Asignar variables para la vista.
        $this->set([
            'redirect' => $redirect ? base64_decode ($redirect) : null,
            'self_register' => (bool)config('auth.self_register.enabled'),
            'language' => config('app.locale'),
            'auth2_token_enabled' => \sowerphp\app\Model_Datasource_Auth2::tokenEnabled(),
        ]);
        // Procesar inicio de sesión.
        if (isset($_POST['usuario'])) {
            // Si el usuario o contraseña es vacio mensaje de error.
            if (empty($_POST['usuario']) || empty($_POST['contrasenia'])) {
                SessionMessage::warning(__(
                    'Debe especificar usuario y clave.'
                ));
            }
            // Realizar proceso de validación de datos.
            else {
                try {
                    $status = auth()->attempt([
                        'username' => $_POST['usuario'],
                        'password' => $_POST['contrasenia'],
                        '2fa_token' => $_POST['auth2_token'] ?: null,
                    ]);
                    if ($status) {
                        $user = user();
                        return redirect('/')->withSuccess(__(
                            'Usuario %s ha iniciado su sesión. Último ingreso fue el %s desde %s.',
                            $user->usuario,
                            \sowerphp\general\Utility_Date::format(
                                $user->ultimo_ingreso_fecha_hora,
                                'd/m/Y H:i'
                            ),
                            $user->ultimo_ingreso_desde
                        ));
                    } else {
                        return redirect('/')->withError(__(
                            'Credenciales de ingreso incorrectas.'
                        ));
                    }
                } catch (\Exception $e) {
                    return redirect('/')->withError($e->getMessage());
                }
            }
        }
    }

    /**
     * Acción para que un usuario cierra la sesión.
     */
    public function salir()
    {
        if (auth()->check()) {
            $user = user();
            auth()->logout();
            return redirect('/')->withSuccess(__(
                'Usuario %s ha cerrado su sesión.',
                $user->usuario
            ));
        } else {
            return redirect('/')->withWarning(__(
                'No existe sesión de usuario abierta.'
            ));
        }
    }

    /**
     * Acción que fuerza el cierre de sesión de un usuario eliminando su hash
     */
    public function salir_forzar($id)
    {
        // Buscar usuario al que se desea cerrar la sesión.
        $Usuario = model()->getUser($id);
        if (!$Usuario->exists()) {
            return redirect('/sistema/usuarios/usuarios/listar')->withError(__(
                'Usuario no existe, no se puede forzar el cierre de la sesión.'
            ));
        }
        // Cerrar sesión del usuario.
        try {
            $Usuario->ultimo_ingreso_hash = null;
            $Usuario->save();
            $cacheKey = 'session.auth' . '.' . $Usuario->id;
            cache()->forget($cacheKey);
            return redirect('/sistema/usuarios/usuarios/editar/'.$id)
                ->withSuccess(
                    __('Sesión del usuario %(user)s cerrada.',
                        [
                            'user' => $Usuario->usuario
                        ]
                    )
                );
        } catch (\Exception $e) {
            return redirect('/sistema/usuarios/usuarios/editar/'.$id)
                ->withError(
                    __('No fue posible forzar el cierre de la sesión: %(error_message)s',
                        [
                            'error_message' => $e->getMessage()
                        ]
                    )
                );
        }
    }

    /**
     * Acción para recuperar la contraseña
     * @param usuario Usuario al que se desea recuperar su contraseña
     */
    public function contrasenia_recuperar(
        Request $request,
        $usuario = null,
        $codigo = null
    )
    {
        // Pedir correo.
        if ($usuario == null) {
            if (!isset($_POST['id'])) {
                return $this->render('Usuarios/contrasenia_recuperar_step1');
            } else {
                // Validar captcha.
                try {
                    \sowerphp\general\Utility_Google_Recaptcha::check();
                } catch (\Exception $e) {
                    return redirect($request->getRequestUriDecoded())
                        ->withError(__(
                            'Falló validación captcha: %s',
                            $e->getMessage()
                        )
                    );
                }
                // Buscar usuario y solicitar correo de recuperación.
                try {
                    $Usuario = model()->getUser($_POST['id']);
                } catch (\Exception $e) {
                    $Usuario = model()->getUser();
                }
                if (!$Usuario->exists()) {
                    return $this->render('Usuarios/contrasenia_recuperar_step1')
                        ->withError(
                            'Usuario no válido. Recuerda que puedes buscar por tu nombre de usuario o correo.'
                        )
                    ;
                }
                else if (!$Usuario->activo) {
                    return $this->render('Usuarios/contrasenia_recuperar_step1')
                        ->withError(
                            'Usuario no activo. Primero deberás realizar la activación del usuario, luego podrás cambiar la contraseña.'
                        )
                    ;
                }
                else {
                    // Renderizar mensaje de correo electrónico.
                    $msg = view()->render('Usuarios/contrasenia_recuperar_email', [
                        'nombre' => $Usuario->nombre,
                        'usuario' => $Usuario->usuario,
                        'hash' => md5(hash('sha256', $Usuario->contrasenia)),
                        'ip' => $request->fromIp(),
                    ]);
                    // Enviar correo electrónico.
                    $email = new \sowerphp\core\Network_Email();
                    $email->to($Usuario->email);
                    $email->subject('Recuperación de contraseña.');
                    $status = $email->send($msg);
                    // Redireccionar con error.
                    if ($status !== true && $status['type'] == 'error') {
                        return redirect('/usuarios/contrasenia/recuperar')->withError(
                            $status['message']
                        );
                    }
                    // Redireccionar ok.
                    return redirect('/usuarios/ingresar')->withSuccess(
                        'Se ha enviado un email con las instrucciones para recuperar tu contraseña.'
                    );
                }
            }
        }
        // cambiar contraseña
        else {
            // buscar usuario al que se desea cambiar la contraseña
            $Usuario = model()->getUser(urldecode($usuario));
            if (!$Usuario->exists()) {
                return redirect('/usuarios/contrasenia/recuperar')->withError(__(
                    'Usuario inválido.'
                ));
            }
            // formulario de cambio de contraseña
            if (!isset($_POST['contrasenia1'])) {
                return $this->render('Usuarios/contrasenia_recuperar_step2', [
                    'usuario' => $Usuario->usuario,
                    'codigo' => $codigo,
                ]);
            }
            // procesar cambio de contraseña
            else {
                // validar captcha
                try {
                    \sowerphp\general\Utility_Google_Recaptcha::check();
                } catch (\Exception $e) {
                    return redirect($request->getRequestUriDecoded())
                        ->withError(__(
                            'Falló validación captcha: %s',
                            $e->getMessage()
                        ))
                    ;
                }
                // cambiar la contraseña al usuario
                if ($_POST['codigo'] != md5(hash('sha256', $Usuario->contrasenia))) {
                    return redirect('/usuarios/contrasenia/recuperar')
                        ->withError(__(
                            'El enlace para recuperar su contraseña no es válido, solicite uno nuevo por favor.'
                        ));
                }
                else if (
                    empty ($_POST['contrasenia1'])
                    || empty ($_POST['contrasenia2'])
                    || $_POST['contrasenia1'] != $_POST['contrasenia2']
                ) {
                    return $this->render('Usuarios/contrasenia_recuperar_step2', [
                        'usuario' => $usuario,
                    ])->withWarning(
                        'Contraseña nueva inválida (en blanco o no coinciden).'
                    );
                }
                else {
                    $Usuario->savePassword($_POST['contrasenia1']);
                    $Usuario->savePasswordRetry(config('auth.max_login_attempts'));
                    return redirect('/usuarios/ingresar')->withSuccess(__(
                        'La contraseña para el usuario %s ha sido cambiada con éxito.',
                        $usuario
                    ));
                }
            }
        }
    }

    /**
     * Acción para crear un nuevo usuario
     */
    public function crear(Request $request)
    {
        if (!empty($_GET['listar'])) {
            $filterListarUrl = '?listar='.$_GET['listar'];
            $filterListar = base64_decode($_GET['listar']);
        } else {
            $filterListarUrl = '';
            $filterListar = '';
        }
        // si se envió el formulario se procesa
        if (!empty($_POST)) {
            $Usuario = model()->getUser();
            $Usuario->fill($_POST);
            $Usuario->usuario = \sowerphp\core\Utility_String::normalize(
                $Usuario->usuario
            );
            $Usuario->email = mb_strtolower($Usuario->email);
            $ok = true;
            if ($Usuario->checkIfUserAlreadyExists()) {
                SessionMessage::warning(__(
                    'Nombre de usuario %s ya está en uso.',
                    $Usuario->usuario
                ));
                $ok = false;
            }
            if ($ok && $Usuario->checkIfHashAlreadyExists()) {
                SessionMessage::warning(__(
                    'Hash seleccionado ya está en uso.'
                ));
                $ok = false;
            }
            if ($ok && $Usuario->checkIfEmailAlreadyExists()) {
                SessionMessage::warning(__(
                    'Correo electrónico %s ya está en uso.',
                    $Usuario->email
                ));
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
                    if (!empty($_POST['contrasenia'])) {
                        $Usuario->savePassword($contrasenia);
                    }
                    // enviar correo
                    $emailConfig = config('email.default');
                    if (!empty($emailConfig['type']) && !empty($emailConfig['from'])) {
                        // Renderizar correo electrónico.
                        $msg = view()->render('Usuarios/crear_email', [
                            'nombre' => $Usuario->nombre,
                            'usuario' => $Usuario->usuario,
                            'contrasenia' => $contrasenia,
                        ]);
                        // Enviar correo electrónico.
                        $email = new \sowerphp\core\Network_Email();
                        $email->to($Usuario->email);
                        $email->subject('Cuenta de usuario creada.');
                        $email->send($msg);
                        return redirect('/sistema/usuarios/usuarios/listar' . $filterListar)
                            ->withSuccess(
                                __('Registro creado. Se envió email a %(user_email)s con los datos de acceso.',
                                    [
                                        'user_email' => $Usuario->email
                                    ]
                                )
                            );
                    } else {
                        return redirect('/sistema/usuarios/usuarios/listar' . $filterListar)
                            ->withWarning(
                                __('Registro creado. No se pudo enviar el correo.')
                            );
                    }
                } else {
                    return redirect('/sistema/usuarios/usuarios/listar' . $filterListar)
                        ->withError(
                            __('Registro no creado (hubo algún error).')
                        );
                }
            }
        }
        // Asignar variables para la vista y renderizar.
        $this->getModelClass()::$columnsInfo['contrasenia']['null'] = true;
        $this->getModelClass()::$columnsInfo['hash']['null'] = true;
        $this->setGruposAsignables();
        return $this->render('Usuarios/crear_editar', [
            'accion' => 'Crear',
            'columns' => $this->getModelClass()::$columnsInfo,
            'grupos_asignados' => (isset($_POST['grupos']) ? $_POST['grupos'] : []),
            'listarUrl' => '/sistema/usuarios/usuarios/listar' . $filterListar,
        ]);
    }

    /**
     * Acción para editar un nuevo usuario.
     */
    public function editar(Request $request, ...$pk)
    {
        list($id) = $pk;
        if (!empty($_GET['listar'])) {
            $filterListarUrl = '?listar=' . $_GET['listar'];
            $filterListar = base64_decode($_GET['listar']);
        } else {
            $filterListarUrl = '';
            $filterListar = '';
        }
        if (strpos($filterListar, 'http') === 0) {
            $redirect = $filterListar;
        } else {
            $redirect = '/sistema/usuarios/usuarios/listar' . $filterListar;
        }
        $Usuario = model()->getUser($id);
        // si el registro que se quiere editar no existe error
        if(!$Usuario->exists()) {
            return redirect($redirect)
                ->withError(
                    __('Registro (%(args)s) no existe, no se puede editar.',
                        [
                            'args' => implode(', ', func_get_args())
                        ]
                    )
                );
        }
        // si no se ha enviado el formulario se mostrará
        if (empty($_POST)) {
            $this->getModelClass()::$columnsInfo['contrasenia']['null'] = true;
            $grupos_asignados = $Usuario->groups();
            $this->setGruposAsignables();
            return $this->render('Usuarios/crear_editar', [
                'accion' => 'Editar',
                'Obj' => $Usuario,
                'columns' => $this->getModelClass()::$columnsInfo,
                'grupos_asignados' => array_keys($grupos_asignados),
                'listarUrl' => $redirect,
            ]);
        }
        // si se envió el formulario se procesa
        else {
            if (isset($_POST['usuario']) && !$this->changeUsername && $Usuario->usuario != $_POST['usuario']) {
                return redirect('/sistema/usuarios/usuarios/editar/' . $id . $filterListarUrl)
                    ->withWarning(
                        __('Nombre de usuario no puede ser cambiado.')
                    );
            }
            $activo = $Usuario->activo;
            $Usuario->set($_POST);
            $Usuario->usuario = \sowerphp\core\Utility_String::normalize($Usuario->usuario);
            $Usuario->email = mb_strtolower($Usuario->email);
            if ($Usuario->checkIfUserAlreadyExists()) {
                return redirect('/sistema/usuarios/usuarios/editar/'.$id.$filterListarUrl)
                    ->withWarning(
                        __('Nombre de usuario %(user)s ya está en uso.',
                            [
                                'user' => $Usuario->usuario
                            ]
                        )
                    );
            }
            if ($Usuario->checkIfHashAlreadyExists ()) {
                return redirect('/sistema/usuarios/usuarios/editar/'.$id.$filterListarUrl)
                    ->withWarning(
                        __('Hash seleccionado ya está en uso.')
                    );
            }
            if ($Usuario->checkIfEmailAlreadyExists ()) {
                return redirect('/sistema/usuarios/usuarios/editar/'.$id.$filterListarUrl)
                    ->withWarning(
                        __('Email seleccionado ya está en uso.')
                    );
            }
            $Usuario->save();
            // enviar correo solo si el usuario estaba inactivo y ahora está activo
            if (!$activo && $Usuario->activo) {
                $emailConfig = config('email.default');
                if (
                    !empty($emailConfig['type'])
                    && !empty($emailConfig['user'])
                    && !empty($emailConfig['pass'])
                ) {
                    // Renderizar correo.
                    $msg = view()->render('Usuarios/activo_email', [
                        'nombre' => $Usuario->nombre,
                        'usuario' => $Usuario->usuario,
                    ]);
                    // Enviar correo.
                    $email = new \sowerphp\core\Network_Email();
                    $email->to($Usuario->email);
                    $email->subject('Cuenta de usuario habilitada.');
                    $email->send($msg);
                }
            }
            if (!empty($_POST['contrasenia'])) {
                $Usuario->savePassword($_POST['contrasenia']);
                $Usuario->savePasswordRetry(config('auth.max_login_attempts'));
            }
            $Usuario->saveGroups($_POST['grupos']);
            return redirect($redirect)->withSuccess(
                'Registro Usuario('.implode(', ', func_get_args()).') editado.'
            );
        }
    }

    /**
     * Método que asigna los grupos que el usuario logueado puede asignar al
     * crear o editar un usuario.
     */
    private function setGruposAsignables()
    {
        $user = request()->user();
        $grupos = (new Model_Grupos())->getList();
        // si el usuario no pertenece al grupo sysadmin quitar los grupos
        // sysadmin y appadmin del listado para evitar que los asignen
        if (!$user->inGroup('sysadmin')) {
            $prohibidos = ['sysadmin', 'appadmin', 'passwd', 'soporte', 'mantenedores', 'webservices'];
            $aux = $grupos;
            $grupos = [];
            foreach ($aux as $key => &$grupo) {
                if (!in_array($grupo['glosa'], $prohibidos)) {
                    $grupos[] = $grupo;
                }
            }
            unset ($aux);
        }
        $this->set(['grupos' => $grupos]);
    }

    /**
     * Acción para mostrar y editar el perfil del usuario que esta autenticado.
     */
    public function perfil(Request $request)
    {
        $user = $request->user();
        // si hay cualquier campo que empiece por 'config_' se quita ya que son
        // configuraciones reservadas para los administradores de la APP y no
        // pueden ser asignadas por los usuarios (esto evita que envién
        // "a la mala" una configuración). Si se desea que el usuario pueda
        // configurar alguna configuración personalizada en el perfil del usuario, se deberá enviar a una
        // acción diferente en un Controlador de usuarios personalizado (que herede este)
        foreach ($_POST as $var => $val) {
            if (strpos($var, 'config_') === 0) {
                unset($_POST[$var]);
            }
        }
        // procesar datos personales
        if (isset($_POST['datosUsuario'])) {
            // actualizar datos generales
            if (
                isset($_POST['usuario'])
                && !$this->changeUsername
                && $user->usuario != $_POST['usuario']
            ) {
                return redirect('/usuarios/perfil')
                    ->withError(
                        __('Nombre de usuario no puede ser cambiado.')
                    );
            }
            $user->nombre = $_POST['nombre'];
            if ($this->changeUsername && !empty($_POST['usuario'])) {
                $user->usuario = \sowerphp\core\Utility_String::normalize($_POST['usuario']);
            }
            $user->email = mb_strtolower($_POST['email']);
            if (isset($_POST['hash'])) {
                $user->hash = $_POST['hash'];
                if (
                    !empty($user->hash)
                    && strlen($user->hash) != 32
                ) {
                    return redirect('/usuarios/perfil')
                        ->withError(
                            __('Hash del usuario debe ser de largo 32.')
                        );
                }
            }
            if ($user->checkIfUserAlreadyExists()) {
                return redirect('/usuarios/perfil')
                    ->withError(
                        __('Nombre de usuario %(user)s ya está en uso.',
                            [
                                'user' => $user->usuario
                            ]
                        )
                    );
            }
            if ($user->checkIfHashAlreadyExists()) {
                return redirect('/usuarios/perfil')
                    ->withError(
                        __('Hash seleccionado ya está en uso.')
                    );
            }
            if ($user->checkIfEmailAlreadyExists()) {
                return redirect('/usuarios/perfil')
                    ->withError(
                        __('Correo electrónico %(user_email)s ya está en uso.',
                            [
                                'user_email' => $user->email
                            ]
                        )
                    );
            }
            if (empty($user->hash)) {
                do {
                    $user->hash = \sowerphp\core\Utility_String::random(32);
                } while ($user->checkIfHashAlreadyExists());
            }
            $user->save();
            auth()->save();
            // mensaje de ok y redireccionar
            return redirect('/usuarios/perfil')
                ->withSuccess(
                    __('Perfil de usuario actualizado.')
                );
        }
        // procesar cambio de contraseña
        else if (isset($_POST['cambiarContrasenia'])) {
            // verificar que las contraseñas no sean vacías
            if (empty($_POST['contrasenia']) || empty(trim($_POST['contrasenia1'])) || empty($_POST['contrasenia2'])) {
                return redirect('/usuarios/perfil')
                    ->withError(
                        __('Debe especificar su contraseña actual y escribir dos veces su nueva contraseña.')
                    );
            }
            // verificar que la contraseña actual sea correcta
            if (!$user->checkPassword($_POST['contrasenia'])) {
                return redirect('/usuarios/perfil')
                    ->withError(
                        __('La contraseña actual ingresada es incorrecta.')
                    );
            }
            // verificar que la contraseña nueva se haya escrito 2 veces de forma correcta
            if ($_POST['contrasenia1'] != $_POST['contrasenia2']) {
                return redirect('/usuarios/perfil')
                    ->withError(
                        __('La contraseña nueva no coincide con la confirmación de contraseña.')
                    );
            }
            // actualizar contraseña
            if ($user->savePassword($_POST['contrasenia1'], $_POST['contrasenia'])) {
                auth()->save();
                return redirect('/usuarios/perfil')
                    ->withSuccess(
                        __('La contraseña del usuario ha sido actualizada.')
                    );
            } else {
                return redirect('/usuarios/perfil')
                    ->withError(
                        __('No fue posible cambiar la contraseña.')
                    );
            }
        }
        // procesar creación de la autenticación secundaria
        else if (isset($_POST['crearAuth2'])) {
            unset($_POST['crearAuth2']);
            try {
                $user->createAuth2($_POST);
                auth()->save();
                return redirect('/usuarios/perfil#auth')
                    ->withSuccess(
                        __('Desde ahora la cuenta está protegida con %(auth2)s.',
                            [
                                'auth2' =>$_POST['auth2']
                            ]
                        )
                    );
            } catch (\Exception $e) {
                return redirect('/usuarios/perfil#auth')
                    ->withError(
                        __('No fue posible proteger la cuenta con %(auth2)s: %(error_message)s',
                            [
                                'auth2' => $_POST['auth2'],
                                'error_message' => $e->getMessage()
                            ]
                        )
                    );
            }
        }
        // procesar destrucción de la autenticación secundaria
        else if (isset($_POST['destruirAuth2'])) {
            unset($_POST['destruirAuth2']);
            try {
                $user->destroyAuth2($_POST);
                auth()->save();
                return redirect('/usuarios/perfil#auth')
                    ->withSuccess(
                        __('Su cuenta ya no está protegida con %(auth2)s.',
                            [
                                'auth2' => $_POST['auth2']
                            ]
                        )
                    );
            } catch (\Exception $e) {
                return redirect('/usuarios/perfil#auth')
                    ->withError(
                        __('No fue posible eliminar la protección con %(auth2)s: %(error_message)s',
                            [
                                'auth2' => $_POST['auth2'],
                                'error_message' => $e->getMessage()
                            ]
                        )
                    );
            }
        }
        // mostrar formulario para edición
        else {
            $this->set([
                'changeUsername' => $this->changeUsername,
                'qrcode' => base64_encode(url() . ';' . $user->hash),
                'auths2' => \sowerphp\app\Model_Datasource_Auth2::getAll(),
                'layouts' => (array)config('app.ui.layouts'),
                'layout' => $user->config_app_ui_layout ?? view()->getLayout(),
            ]);
        }
    }

    /**
     * Acción que permite registrar un nuevo usuario en la aplicación.
     */
    public function registrar(Request $request)
    {
        $user = $request->user();
        // Si ya está autenticado se redirecciona.
        if ($user) {
            return redirect('/')->withInfo(__(
                'Usuario <em>%s</em> tiene su sesión iniciada. Para registrar un nuevo usuaro primero debe cerrar esta sesión.',
                $user->usuario
            ));
        }
        // Si no se permite el registro se redirecciona.
        $config = config('auth.self_register');
        if (!$config['enabled']) {
            return redirect('/')->withError(__(
                'El registro de usuarios está deshabilitado.'
            ));
        }
        // Colocar variable para terminos y condicines si está configurada.
        if (!empty($config['terms'])) {
            $this->set(['terms' => $config['terms']]);
        }
        // Si se envió formulario se procesa.
        if (isset($_POST['usuario'])) {
            // verificar que campos no sean vacios
            if (empty($_POST['nombre']) || empty($_POST['usuario']) || empty($_POST['email'])) {
                SessionMessage::warning(__(
                    'Debe completar todos los campos del formulario.'
                ));
                return;
            }
            // validar captcha
            try {
                \sowerphp\general\Utility_Google_Recaptcha::check();
            } catch (\Exception $e) {
                SessionMessage::error(__(
                    'Falló validación captcha: %s',
                    $e->getMessage()
                ));
                return;
            }
            // si existen términos y no se aceptaron se redirecciona
            if (!empty($config['terms']) && empty($_POST['terms_ok'])) {
                SessionMessage::warning(__(
                    'Debe aceptar los términos y condiciones de uso.'
                ));
                return;
            }
            // validar que el usuario y/o correo no exista previamente
            $Usuario = model()->getUser();
            $Usuario->nombre = $_POST['nombre'];
            $Usuario->usuario = \sowerphp\core\Utility_String::normalize(
                $_POST['usuario']
            );
            $Usuario->email = mb_strtolower($_POST['email']);
            if ($Usuario->checkIfUserAlreadyExists()) {
                SessionMessage::warning(__(
                    'Nombre de usuario %s ya está en uso, elegir otro por favor.',
                    $Usuario->usuario
                ));
                return;
            }
            if ($Usuario->checkIfEmailAlreadyExists()) {
                SessionMessage::warning(__(
                    'Correo electrónico %s ya está en uso, elegir otro por favor.',
                    $Usuario->email
                ));
                return;
            }
            // asignar contraseña al usuario
            $contrasenia = \sowerphp\core\Utility_String::random(8);
            $Usuario->contrasenia = $Usuario->hashPassword($contrasenia);
            // asignar hash al usuario
            do {
                $Usuario->hash = \sowerphp\core\Utility_String::random(32);
            } while ($Usuario->checkIfHashAlreadyExists());
            if ($Usuario->save()) {
                // asignar grupos por defecto al usuario
                if (is_array($config) && !empty($config['groups'])) {
                    $Usuario->saveGroups($config['groups']);
                }
                // enviar correo
                $emailConfig = config('email.default');
                if (!empty($emailConfig['type']) && !empty($emailConfig['from'])) {
                    $msg = view()->render('Usuarios/crear_email', [
                        'nombre' => $Usuario->nombre,
                        'usuario' => $Usuario->usuario,
                        'contrasenia' => $contrasenia,
                    ]);
                    // Enviar correo electrónico.
                    $email = new \sowerphp\core\Network_Email();
                    $email->to($Usuario->email);
                    $email->subject('Cuenta de usuario creada.');
                    $email->send($msg);
                    return redirect('/usuarios/ingresar')
                        ->withSuccess(
                            __('Registro de usuario realizado, se envió su contraseña al correo %s',
                                [
                                    'user_email' => $Usuario->email
                                ]
                            )
                        );
                } else {
                    return redirect('/usuarios/ingresar')
                        ->withSuccess(
                            __('Registro de usuario realizado, su contraseña es <em>%(contrasenia)s</em>',
                                [
                                    'contrasenia' => $contrasenia
                                ]
                            )
                        );
                }
            } else {
                return redirect('/usuarios/ingresar')
                    ->withError(
                        __('El registro de su usuario falló por algún motivo desconocido.')
                    );
            }
        }
    }

    /**
     * Acción que permite ingresar a la aplicación con un usuario ya
     * autenticado a través de un token provisto.
     *
     * @param string $token Token de pre autenticación para validar la sesión.
     * @param string $usuario Usuario con el que se desea ingresar.
     * @param string $url URL a la cual redireccionar el usuario una vez ha
     * iniciado sesión.
     */
    public function preauth(
        ?string $token = null,
        ?string $usuario = null,
        ?string $url = null
    )
    {
        // Si se pasaron datos por POST tienen preferencia.
        if (!empty($_POST['token'])) {
            $token = $_POST['token'];
            $usuario = !empty($_POST['usuario']) ? $_POST['usuario'] : null;
            $url = !empty($_POST['url']) ? $_POST['url'] : null;
        }
        // Buscar clave de preauth, si no existe se indica que la
        // preautenticación no está disponible.
        $enabled = config('auth.preauth.enabled');
        if (!$enabled) {
            return redirect('/usuarios/ingresar')
                ->withError(
                    __('La preautenticación no está disponible.')
                );
        }
        if ($usuario) {
            $key = config('app.key');
            if (!$key) {
                return redirect('/usuarios/ingresar')
                    ->withError(
                        __('No hay clave global para preautenticación.')
                    );
            }
        }
        // Definir URL de redirección.
        $url = $url ? base64_decode($url) : '/';
        // Si ya está logueado se redirecciona de forma silenciosa.
        if (auth()->check()) {
            return redirect($url);
        }
        // Procesar inicio de sesión con preauth, si no se puede autenticar se
        // genera un error.
        $auth2_token = !empty($_GET['auth2_token']) ? $_GET['auth2_token'] : null;
        if (!auth()->preauth($token, $usuario, $auth2_token)) {
            SessionMessage::error(__(
                'La preautenticación del usuario falló.'
            ));
            return redirect('/usuarios/ingresar')
                ->withError(
                    __('La preautenticación del usuario falló.')
                );
        }
        // todo ok -> redirigir
        return redirect($url);
    }

    /**
     * Acción que verifica el token ingresado y hace el pareo con telegram.
     */
    public function telegram_parear(Request $request)
    {
        $user = $request()->user();
        if (!empty($_POST['telegram_token'])) {
            $token = $_POST['telegram_token'];
            $telegram_user = cache()->get('telegram.pairing.'.$token);
            // si no se encontró el usuario el token no es válido o expiró
            if (!$telegram_user) {
                SessionMessage::error(__(
                    'Token no válido o expiró, por favor, solicite uno nuevo al Bot con <strong><em>/token</em></strong>'
                ));
            }
            // se encontró el usuario, entonces guardar los datos del usuario de Telegram en el usuario de la aplicación web
            else {
                // verificar que no exista ya el usuario
                $Usuario = (new \sowerphp\app\Sistema\Usuarios\Model_Usuarios())->getUserByTelegramID(
                    $telegram_user['id']
                );
                // cuenta de telegram ya está pareada
                if ($Usuario) {
                    SessionMessage::error(__(
                        'La cuenta de Telegram ya está pareada al usuario %s, primero debe cerrar la sesión de dicha cuenta.',
                        $Usuario->usuario
                    ));
                }
                // cuenta de telegram no está pareada, guardar
                else {
                    $user->fill([
                        'config_telegram_id' => $telegram_user['id'],
                        'config_telegram_username' => $telegram_user['username'],
                    ]);
                    try {
                        $user->save();
                        auth()->save();
                        cache()->forget('telegram.pairing.'.$token);
                        SessionMessage::success(__(
                            'Usuario @%s pareado con éxito.',
                            $telegram_user['username']
                        ));
                    } catch (\Exception $e) {
                        SessionMessage::error(__(
                            'Ocurrió un error al parear con Telegram: '.$e->getMessage()
                        ));
                    }
                }
            }
        }
        return redirect('/usuarios/perfil#apps');
    }

    /**
     * Acción que desparea al usuario de Telegram.
     */
    public function telegram_desparear(Request $request)
    {
        $user = $request->user();
        $user->fill([
            'config_telegram_id' => null,
            'config_telegram_username' => null,
        ]);
        try {
            $user->save();
            auth()->save();
            return redirect('/usuarios/perfil#apps')
                ->withSuccess(
                    __('Su cuenta ya no está asociada a Telegram.')
                );
        } catch (\Exception $e) {
            return redirect('/usuarios/perfil#apps')
                ->withError(
                    __('Ocurrió un error al eliminar su cuenta de Telegram: %(error_message)s',
                        [
                            'error_message' => $e->getMessage()
                        ])
                );
        }
    }

    /**
     * Método que permite cambiar el layout por defecto del usuario.
     */
    public function layout(Request $request, $layout = null)
    {
        $user = $request->user();
        // Verificar se haya indicado un layout.
        $layout = !empty($_POST['layout']) ? $_POST['layout'] : $layout;
        if (!$layout) {
            return redirect('/usuarios/perfil')->withError(__(
                'Debe indicar el nuevo diseño que desea utilizar en la aplicación.'
            ));
        }
        // Cambiar layout.
        $user->config_app_ui_layout = $layout;
        $user->save();
        auth()->save();
        return redirect('/app/session/app.ui.layout/'.$layout.'/'.base64_encode('/usuarios/perfil#libredte'))
            ->withSuccess('Se modificó el diseño por defecto de su cuenta.')
        ;
    }

    /**
     * Función de la API que permite obtener el perfil del usuario autenticado.
     */
    public function _api_perfil_GET(Request $request)
    {
        $user = $request->user();
        extract($request->getValidatedData([
            'login' => false,
        ]));
        if ($login) {
            $user->createRememberToken($request->fromIp(true));
        }
        return [
            'id' => $user->id,
            'nombre' => $user->nombre,
            'usuario' => $user->usuario,
            'email' => $user->email,
            'hash' => $login ? $user->hash : null,
            'ultimo_ingreso' => $user->lastLogin(),
            'grupos' => array_values($user->groups()),
        ];
    }

}
