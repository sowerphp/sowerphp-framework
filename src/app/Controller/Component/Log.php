<?php

/**
 * SowerPHP: Framework PHP hecho en Chile.
 * Copyright (C) SowerPHP <https://www.sowerphp.org>
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

namespace sowerphp\app;

/**
 * Componente para proveer de un sistema de Logs para las aplicaciones.
 *
 * Se utiliza la misma idea de syslog, asumiendo la aplicación como si fuese un
 * sistema like Unix. Los tipos de programas que están registrando un mensaje
 * pueden ser:
 *
 *  - LOG_KERN: mensajes del núcleo de la aplicación (ej: automáticos por error).
 *  - LOG_USER: origen por defecto de los controladores (se pueden cambiar por LOG_LOCAL0 a LOG_LOCAL7).
 *  - LOG_MAIL: mensajes de la aplicación de correo electrónico.
 *  - LOG_AUTH: mensajes del sistema de autenticación.
 *  - LOG_NEWS: mensajes de notificaciones.
 *  - LOG_CRON: mensajes de tareas programadas (comandos Shell).
 *  - LOG_LOCAL0 a LOG_LOCAL7: se dejarán para ser utilizados por cada aplicación.
 *  - LOG_UUCP: mensajes de la API (recomendado, no forzado ni obligatorio).
 */
class Controller_Component_Log extends \sowerphp\core\Controller_Component
{

    public $settings = [
        'report' => [
            LOG_KERN => [
                LOG_EMERG => ['email'],
                LOG_ALERT => ['email'],
                LOG_CRIT => ['email'],
                LOG_ERR => ['email'],
                LOG_WARNING => ['syslog'],
                LOG_NOTICE => ['syslog'],
            ],
            LOG_AUTH => [
                LOG_ERR => ['db'],
                LOG_INFO => ['db'],
            ],
            LOG_DAEMON => [
                LOG_DEBUG => ['file'],
            ],
            LOG_MAIL => [
                LOG_DEBUG => ['email'],
            ],
        ],
        'report_email' => [
            'groups' => ['sysadmin'], // Grupos a quienes se les envía el email
            'attach' => false, // Si se debe o no adjuntar $_POST y $_FILES al email
        ],
        'syslog_facility' => LOG_LOCAL7,
    ]; ///< Opciones por defecto del componente
    protected $Log = null; ///< Objeto para escribir eventos en la base de datos
    protected $User = null;

    /**
     * Se registran automáticamente eventos que ocurrieron durante la ejecución
     * del controlador (incluyendo la renderización de la vista).
     */
    public function afterFilter($url = null, $status = null)
    {
        if (get_class($this->controller) == 'sowerphp\core\Controller_Error') {
            $message = [
                'exception' => $this->controller->viewVars['exception'],
                'message' => $this->controller->viewVars['message'],
                'trace' => $this->controller->viewVars['trace'],
                'code' => $this->controller->viewVars['code'],
            ];
            $this->report($message, [0, $this->controller->viewVars['severity']]);
        }
    }

    /**
     * Método que escribe un evento en el Log.
     * @param message Evento (mensaje) que se desea registrar.
     * @param severity Gravedad del evento (por defecto informativos).
     */
    public function write($message, $severity = LOG_INFO, $facility = null)
    {
        if (!$facility) {
            $facility = $this->controller->log_facility;
        }
        $priority = $facility * 8 + $severity;
        $this->report($message, $priority);
    }

    /**
     * Método que procesa y reporta (de ser necesario) un registro.
     * @param message Mensaje que se desea reportar (puede ser un arreglo asociativo).
     * @param priority Prioridad en un entero (formato Syslog) o arreglo [facility, severity].
     */
    private function report($message, $priority)
    {
        // determinar programa que envía el mensaje
        if (is_array($priority)) {
            list($facility, $severity) = $priority;
        } else {
            $facility = floor($priority/8);
            $severity = $priority - $facility * 8;
        }
        // reportar el mensaje de acuerdo la severidad del mismo
        if (isset($this->settings['report'][$facility][$severity])) {
            foreach ($this->settings['report'][$facility][$severity] as $method) {
                // se reporta a través de un método de esta clase
                if (is_string($method)) {
                    $method = 'report' . ucfirst($method);
                    $this->$method($message, $facility, $severity);
                }
                // se reporta a través de un handler (método de otra clase)
                else if (is_array($method) && isset($method[1])) {
                    $class = $method[0];
                    if (!class_exists($class)) {
                        throw new \Exception(
                            'Clase ' . $class . ' para reportar el log no existe.'
                        );
                    }
                    $handler = $method[1];
                    if (!method_exists($class, $handler)) {
                        throw new \Exception(
                            'Método ' . $class . '::' . $handler . ' para reportar el log no existe.'
                        );
                    }
                    $options = $method[2] ?? [];
                    $context = [
                        'ip' => $this->controller->Auth->ip(true),
                        'User' => $this->getUser(),
                        'options' => $options,
                    ];
                    $class::$handler($message, $facility, $severity, $context);
                } else {
                    throw new \Exception(
                        'Método ' . json_encode($method). ' no es válido para reportar el log.'
                    );
                }
            }
        }
    }

    /**
     * Método que entrega la URL completa que gatilló el regitro.
     * @return string URL completa (incluyendo parámetros por GET).
     */
    private function getURL()
    {
        $get = strpos($_SERVER['QUERY_STRING'], '&')
            ? ('?'.substr($_SERVER['QUERY_STRING'], strpos($_SERVER['QUERY_STRING'], '&')+1))
            : ''
        ;
        return $this->controller->request->url.$this->controller->request->request.$get;
    }

    /**
     * Método que envía el registro a Syslog.
     * @param message Mensaje que se desea reportar (puede ser un arreglo asociativo).
     * @param facility Origen del envío (no se usa, ya que se cambia por la configuración del componente). *                 Esto porque se envía al sistema operativo.
     * @param severity Gravedad del registro.
     */
    private function reportSyslog($message, $facility, $severity)
    {
        // si es arreglo se arma el mensaje
        if (is_array($message)) {
            if (isset($message['exception']) && isset($message['message']) && isset($message['code'])) {
                $message = '['.$message['code'].'] '.$message['exception'].' "'.$message['message'].'"';
            }
        } else {
            $message = '['.$this->getFacility($facility).'.'.$this->getSeverity($severity).'] '.get_class($this->controller).' "'.$message.'"';
        }
        // agregar datos de URL, usuario e IP
        $message .= ' in '.$this->getURL().'';
        if ($this->getUser()) {
            $message .= ' by '.$this->getUser()->usuario;
        }
        $message .= ' from '.$this->controller->Auth->ip(true);
        // enviar mensaje a syslog
        openlog(
            \sowerphp\core\Utility_String::normalize(\sowerphp\core\Configure::read('page.header.title')).'_app',
            LOG_ODELAY,
            $this->settings['syslog_facility']
        );
        syslog($severity, strip_tags($message));
        closelog();
    }

    /**
     * Método que envía el registro por email.
     * @param message Mensaje que se desea reportar (puede ser un arreglo asociativo).
     * @param facility Origen del envío.
     * @param severity Gravedad del registro.
     */
    private function reportEmail($message, $facility, $severity)
    {
        // verificar que exista soporte para correo
        $config = \sowerphp\core\Configure::read('email.default');
        if (!$config) {
            return false;
        }
        // crear reporte
        try {
            $email = new \sowerphp\core\Network_Email($config);
        } catch (\sowerphp\core\Exception $e) {
            return false;
        }
        if ($this->getUser()) {
            $email->replyTo($this->getUser()->email, $this->getUser()->nombre);
        }
        $Grupos = new \sowerphp\app\Sistema\Usuarios\Model_Grupos();
        $email->to($Grupos->emails($Grupos->getIDs($this->settings['report_email']['groups'])));
        $timestamp = microtime(true);
        // asunto
        $email->subject('['.\sowerphp\core\Configure::read('page.header.title').'] Log '.$this->getFacility($facility).'.'.$this->getSeverity($severity).' '.$timestamp);
        // inicio mensaje
        $msg = "Estimad@s,\n\nSe ha registrado el siguiente evento en la aplicación:\n\n";
        // usuario
        $msg .= str_repeat('=', 80)."\n";
        if ($this->getUser()) {
            $msg .= 'USUARIO AUTENTICADO:'."\n\n";
            $msg .= 'Nombre: '.$this->getUser()->nombre."\n";
            $msg .= 'Usuario: '.$this->getUser()->usuario.' ('.$this->getUser()->id.')'."\n";
            $msg .= 'Grupos: '.implode(', ', $this->getUser()->groups())."\n";
            $msg .= 'Email: '.$this->getUser()->email."\n";
        } else {
            $msg .= 'USUARIO NO AUTENTICADO:'."\n\n";
        }
        $msg .= 'IP: '.$this->controller->Auth->ip(true)."\n\n";
        // solicitud
        $msg .= str_repeat('=', 80)."\n";
        $msg .= 'SOLICITUD:'."\n\n";
        $msg .= $this->getURL()."\n\n";
        // mensaje
        $msg .= str_repeat('=', 80)."\n";
        $msg .= 'MENSAJE:'."\n\n";
        if (is_array($message)) {
            foreach ($message as $key => $value) {
                $msg .= $key.":\n".$value."\n\n";
            }
        } else {
            $msg .= $message."\n\n";
        }
        // firma
        $msg .= "-- \n";
        if ($this->getUser()) {
            $msg .= $this->getUser()->nombre."\n".$this->getUser()->email."\n";
        }
        $msg .= $this->controller->request->url;
        // si no se deben adjuntar archivos enviar email
        if (!$this->settings['report_email']['attach']) {
            $email->send($msg);
            return;
        }
        // adjuntar datos de POST
        if (!empty($_POST)) {
            $_POST_file = TMP . '/_POST_' . $timestamp . '.txt';
            file_put_contents($_POST_file, json_encode($_POST));
            $email->attach([
                'tmp_name' => $_POST_file,
                'type' => \sowerphp\general\Utility_File::mimetype($_POST_file),
                'name' => '_POST.txt',
            ]);
        }
        // adjuntos
        if (!empty($_FILES)) {
            // archivo _FILES.txt con los datos del arreglo $_FILES
            $_FILES_file = TMP . '/_FILES_' . $timestamp . '.txt';
            file_put_contents($_FILES_file, json_encode($_FILES));
            $email->attach([
                'tmp_name' => $_FILES_file,
                'type' => \sowerphp\general\Utility_File::mimetype($_FILES_file),
                'name' => '_FILES.txt',
            ]);
            // archivos adjuntos
            foreach ($_FILES as $key => $info) {
                // si es un arreglo de archivos
                if (is_array($_FILES[$key]['name'])) {
                    $n = count($_FILES[$key]['name']);
                    for ($i=0; $i<$n; $i++) {
                        if (!$_FILES[$key]['error'][$i]) {
                            $email->attach([
                                'name' => $_FILES[$key]['name'][$i],
                                'type' => $_FILES[$key]['type'][$i],
                                'tmp_name' => $_FILES[$key]['tmp_name'][$i],
                                'size' => $_FILES[$key]['size'][$i],
                            ]);
                        }
                    }
                }
                // si es solo un archivo
                else {
                    if (!$_FILES[$key]['error']) {
                        $email->attach([
                            'name' => $_FILES[$key]['name'],
                            'type' => $_FILES[$key]['type'],
                            'tmp_name' => $_FILES[$key]['tmp_name'],
                            'size' => $_FILES[$key]['size'],
                        ]);
                    }
                }
            }
        }
        // enviar el mensaje
        $email->send($msg);
        // eliminar archivo POST y/o FILES si existen
        if (isset($_POST_file)) {
            unlink($_POST_file);
        }
        if (isset($_FILES_file)) {
            unlink($_FILES_file);
        }
    }

    /**
     * Método que abre el log para la base de datos.
     * @return bool =true si se pudo abrir el log (existe módulos Sistema.Logs).
     */
    private function openlog()
    {
        if (\sowerphp\core\Module::loaded('Sistema.Logs')) {
            if (!$this->Log) {
                $this->Log = new \sowerphp\app\Sistema\Logs\Model_Log();
            }
            return true;
        }
        return false;
    }

    /**
     * Método que envía el registro a la base de datos
     * @param message Mensaje que se desea reportar (puede ser un arreglo asociativo)
     * @param facility Origen del envío
     * @param severity Gravedad del registro
     */
    private function reportDb($message, $facility, $severity)
    {
        if (!$this->openlog()) {
            return;
        }
        $this->Log->fechahora = date('Y-m-d H:i:s');
        $this->Log->identificador = get_class($this->controller);
        $this->Log->origen = $facility;
        $this->Log->gravedad = $severity;
        $this->Log->usuario = $this->getUser() ? $this->getUser()->id : null;
        $this->Log->ip = $this->controller->Auth->ip(true);
        $this->Log->solicitud = $this->getURL();
        if (is_array($message)) {
            $this->Log->mensaje = '';
            foreach ($message as $key => $value) {
                $this->Log->mensaje .= $key . ":\n" . $value . "\n\n";
            }
        } else {
            $this->Log->mensaje = $message;
        }
        $this->Log->save();
    }

    /**
     * Método que envía el registro a un archivo de texto.
     * @param message Mensaje que se desea reportar (puede ser un arreglo asociativo).
     * @param facility Origen del envío.
     * @param severity Gravedad del registro.
     */
    private function reportFile($message, $facility, $severity)
    {
        $log = TMP.'/log_'.$this->getFacility($facility).'_'.$this->getSeverity($severity).'_'.date('Ymd').'.log';
        if (is_array($message) || is_object($message) || is_bool($message)) {
            $message = json_encode($message);
        }
        $info = date('Y-m-d H:i:s') . ' ' . $this->controller->Auth->ip(true) . ' '
                . ($this->getUser() ? $this->getUser()->usuario : '^_^');
        file_put_contents($log, $info.' '.$message."\n", FILE_APPEND);
    }

    /**
     * Método que recupera la glosa del origen.
     * @param facility Origen que se quiere obtener su glosa.
     * @return string Glosa del origen.
     */
    private function getFacility($facility)
    {
        if (!$this->openlog()) {
            return $facility;
        }
        return $this->Log->getFacility($facility)->glosa;
    }

    /**
     * Método que recupera la glosa de la gravedad.
     * @param severity Gravedad que se quiere obtener su glosa.
     * @return string Glosa de la gravedad.
     */
    private function getSeverity($severity)
    {
        if (!$this->openlog()) {
            return $severity;
        }
        return $this->Log->getSeverity($severity)->glosa;
    }

    /**
     * Método que obtiene el usuario que está reportando el log, si es que
     * existe uno.
     */
    protected function getUser()
    {
        if ($this->User === null) {
            // usuario autenticado en el controlador de la aplicación web
            if ($this->controller->Auth->User) {
                $this->User = $this->controller->Auth->User;
            } else {
                $User = $this->controller->Api->getAuthUser();
                $this->User = is_object($User) ? $User : false;
            }
        }
        return $this->User;
    }

}
