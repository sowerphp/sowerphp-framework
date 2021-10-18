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

namespace sowerphp\app;

/**
 * Controlador base para Bot de la aplicación web
 * Para usar con Telegram se debe configurar el webhook en la URL:
 *   https://api.telegram.org/bot<token>/setWebhook?url=https://example.com/api/bot/telegram
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2017-10-17
 */
abstract class Controller_Bot extends \Controller_App
{

    protected $Bot; ///< Objeto para el Bot
    public $log_facility = LOG_DAEMON; ///< Origen para sistema de Logs
    protected $messages = [
        'hello' => '¡Hola %s! ¿Qué necesitas?',
        'doNotUnderstand' => "No entiendo lo que me dices \xF0\x9F\x98\x9E Si necesitas ayuda dime /help",
        'helpMiss' => "No sé como explicar lo que puedo hacer por ti \xF0\x9F\x98\x85",
        'canceled' => "He cancelado lo último que estábamos haciendo \xF0\x9F\x91\x8D",
        'nothingToCancel' => "Aun no me has pedido algo, no sé que quieres cancelar \xF0\x9F\x98\x96",
        'doNotKnow' => "No sé que me estás pidiendo, no sé nada sobre /%s \xF0\x9F\x98\x95",
        'argsMiss' => "Por favor háblame claro \xF0\x9F\x98\x91 Dime lo que necesitas así:\n/%s %s",
        'whoami' => "%s \nUsuario: %s\nID: %s",
        'auth' => [
            'invalid' => "@%s no te conozco \xF0\x9F\x98\x9E",
            'logout' => '¡Hasta pronto @%s!',
            'token' => 'Tu token para pareo es: %d',
            'notoken' => 'Tu cuenta de Telegram ya está asociada al usuario %s del sistema, usa /logout para cerrar sesión y pedir un nuevo /token',
        ],
        'settings' => [
            'select' => 'Dime qué opción quieres configurar',
            'miss' => "No tengo opciones que se puedan configurar \xF0\x9F\x98\x9E",
        ],
        'support' => [
            'msg' => 'Dime el mensaje que quieres que envíe a mis creadores',
            'subject' => '@%s necesita ayuda con %s #%d',
            'ok' => "He enviado tu mensaje a mis creadores \xF0\x9F\x91\x8D",
            'bad' => "Ups, no pude enviar el mensaje \xF0\x9F\x98\xA2",
        ],
    ]; ///< Mensajes del Bot (http://apps.timwhitlock.info/emoji/tables/unicode)
    private $auto_previous_command = true; ///< ¿Colocar automáticamente el último comando usado?
    protected $keyboards = [
        'numbers' => [['1','2','3'], ['4','5','6'], ['7','8','9'], ['0']],
        'like' => [["\xF0\x9F\x91\x8D", "\xF0\x9F\x91\x8E"]],
    ]; ///< Layouts de teclados
    private $Usuario; ///< Usuario autenticado (asociado a la aplicación web)

    /**
     * Método para permitir acceder a la API sin estar autenticado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-23
     */
    public function beforeFilter()
    {
        $this->Auth->allow('_api_telegram_POST');
        parent::beforeFilter();
    }

    /**
     * Acción principal de la API, se encargará de llamar los comandos del Bot
     * @param id_bot ID del Bot de Telegram, permite validar que es Telegram quien escribe al Bot
     * @return Entrega el retorno entregado por el método del bot ejecutado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-10-24
     */
    public function _api_telegram_POST($id_bot)
    {
        $telegram = \sowerphp\core\Configure::read('telegram');
        $config = false;
        foreach ($telegram as $c) {
            if (isset($c['bot']) and $id_bot==explode(':', $c['token'])[0]) {
                $config = $c;
                break;
            }
        }
        if (!$config)
            $this->Api->send('ID del Bot de Telegram incorrecto', 401);
        $this->Bot = new \sowerphp\app\Utility_Bot_Telegram($config);
        $command = $this->Bot->getCommand();
        $this->beforeRun($command);
        $rc = $this->run($command);
        $this->afterRun($command);
        return $rc;
    }

    /**
     * Método que ejecuta un comando solicitado al Bot
     * @param command String completo con el comando y sus argumentos
     * @return Entrega el retorno entregado por el método del bot ejecutado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-10-24
     */
    protected function run($command)
    {
        if (!$command) {
            return false;
        }
        $argv = $this->string2argv($command);
        $next_command = $this->getNextCommand();
        if ($next_command) {
            if (!isset($argv[0]) or $argv[0][0]!='/') {
                array_unshift($argv, '/'.$next_command);
            } else {
                if ($argv[0]=='/cancel') {
                    $this->setNextCommand();
                    $this->Bot->send(__($this->messages['canceled']));
                    return;
                }
                else if ($argv[0]=='/start') {
                    $this->setNextCommand();
                }
                else {
                    $argv[0] = '/'.$next_command;
                }
            }
        }
        if ($argv[0][0]!='/') {
            return $this->defaultCommand($command);
        }
        if (in_array($argv[0], ['/', '/ayuda'])) {
            $argv[0] = '/help';
        }
        $command = substr(array_shift($argv), 1);
        $method = '_bot_'.$command;
        if (!method_exists($this, $method)) {
            $this->Bot->send(__($this->messages['doNotKnow'], $command));
            return;
        }
        $reflectionMethod = new \ReflectionMethod($this, $method);
        if (count($argv)<$reflectionMethod->getNumberOfRequiredParameters()) {
            $args = [];
            foreach($reflectionMethod->getParameters() as &$p) {
                $args[] = $p->isOptional() ? '['.$p->name.']' : $p->name;
            }
            $this->Bot->send(__($this->messages['argsMiss'], $command, implode(' ', $args)));
            return;
        }
        return call_user_func_array([$this, $method], $argv);
    }

    /**
     * Método que parsea un string extrayendo el comango y sus argumentos
     * @param string String que se desea parsear
     * @return Arreglo con formamto argv (en 0 nombre del comando y desde 1 los argumentos)
     * @author http://stackoverflow.com/a/18217486
     * @version 2013-08-14
     */
    private function string2argv($string)
    {
        preg_match_all('#(?<!\\\\)("|\')(?<escaped>(?:[^\\\\]|\\\\.)*?)\1|(?<unescaped>\S+)#s', $string, $matches, PREG_SET_ORDER);
        $results = array();
        foreach ($matches as $array) {
            if (!empty($array['escaped'])) {
                $results[] = $array['escaped'];
            } else {
                $results[] = $array['unescaped'];
            }
        }
        return $results;
    }

    /**
     * Comando que se ejecutará por defecto al no encontrar un comando válido
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2016-08-23
     */
    protected function defaultCommand($command)
    {
        return $this->Bot->send(__($this->messages['doNotUnderstand']));
    }

    /**
     * Método que ejecuta antes de correr el comando
     * @param command Nombre del comando que se ejecutará
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-06-28
     */
    protected function beforeRun($command)
    {
    }

    /**
     * Método que ejecuta después de correr el comando
     * @param command Nombre del comando que se ejecutó
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-06-28
     */
    protected function afterRun($command)
    {
        if ($this->auto_previous_command)
            $this->setPreviousCommand($command);
    }

    /**
     * Método que asigna el próximo comando que se debe ejecutar, lo forzará
     * @param command Nombre del comando que se deberá ejecutar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-07-01
     */
    protected function setNextCommand($command = null)
    {
        if ($command)
            $this->Cache->set('bot_next_command_'.$this->Bot->getFrom()->id, $command);
        else
            $this->Cache->delete('bot_next_command_'.$this->Bot->getFrom()->id);
    }

    /**
     * Método que obtiene el próximo comando que se debe ejecutar
     * @return Nombre del comando que se deberá ejecutar próximamente
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-07-01
     */
    protected function getNextCommand()
    {
        return $this->Cache->get('bot_next_command_'.$this->Bot->getFrom()->id);
    }

    /**
     * Método que recuerda el comando que se ejecutó para ser usado en una
     * próxima llamada como referencia de donde se "venía"
     * @param command Nombre del comando que se desea recordar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-07-01
     */
    protected function setPreviousCommand($command = null)
    {
        if (!$command)
            $command = $this->getPreviousCommand();
        $this->auto_previous_command = false;
        $this->Cache->set('bot_previous_command_'.$this->Bot->getFrom()->id, $command);
    }

    /**
     * Método que obtiene el comando que se ejecuto antes que el actual, o bien
     * desde donde venía el comando actual
     * @return Nombre del comando que se ejecutó antes del comando que se está ejecutando ahora
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-07-01
     */
    protected function getPreviousCommand()
    {
        return $this->Cache->get('bot_previous_command_'.$this->Bot->getFrom()->id);
    }

    /**
     * Método que obtiene un layout de teclado para ser envíado al usuario
     * @param keyboard Teclado que se quiere recuperar
     * @return Layout (arreglo) con el teclado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-07-03
     */
    protected function getKeyboard($keyboard, $cols = 2)
    {
        if (is_string($keyboard)) {
            if (method_exists($this, 'getKeyboard'.ucfirst($keyboard)))
                return $this->{'getKeyboard'.ucfirst($keyboard)}();
            else if (isset($this->keyboards[$keyboard]))
                return $this->keyboards[$keyboard];
        }
        else if (is_array($keyboard)) {
            $kb = [];
            $i = 0;
            foreach ($keyboard as $option) {
                if (is_array($option))
                    $option = implode(' - ', $option);
                if ($i%$cols==0)
                    $kb[] = [];
                $kb[(int)($i/$cols)][] = $option;
                $i++;
            }
            return $kb;
        }
        return false;
    }

    /**
     * Comando del Bot para iniciar saludando al usuario
     * @param token Token de autenticación para el usuario que escribe al Bot
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-07-01
     */
    protected function _bot_start($token = null)
    {
        $this->Bot->sendChatAction();
        $this->Bot->send(__($this->messages['hello'], $this->Bot->getFrom()->first_name));
    }

    /**
     * Comando del Bot para mostrar un mensaje por defecto de no existencia de
     * ayuda el bot
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-06-29
     */
    protected function _bot_help()
    {
        $this->Bot->sendChatAction();
        if (!isset($this->help))
            $this->Bot->send(__($this->messages['helpMiss']));
        else {
            $help = '';
            foreach ($this->help as $cmd => $desc)
                $help .= '/'.$cmd.' - '.$desc."\n";
            $this->Bot->send($help);
        }
    }

    /**
     * Comando del Bot para mostrar un mensaje por defecto de no existencia de
     * opciones del bot
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-07-08
     */
    protected function _bot_settings()
    {
        $this->Bot->sendChatAction();
        if (isset($this->settings) and is_array($this->settings) and !empty($this->settings)) {
            $this->Bot->sendKeyboard(
                __($this->messages['settings']['select']),
                $this->getKeyboard($this->settings, 3)
            );
        } else {
            $this->Bot->send(__($this->messages['settings']['miss']));
        }
    }

    /**
     * Comando del Bot que muestra mensaje en caso de que se haya solicitado
     * cancelar una acción y no se esté esperando ninguna
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-06-28
     */
    protected function _bot_cancel()
    {
        $this->Bot->sendChatAction();
        $this->Bot->send(__($this->messages['nothingToCancel']));
    }

    /**
     * Comando del Bot que dice quien es el usuario que habla con él
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-07-01
     */
    protected function _bot_whoami()
    {
        $this->Bot->sendChatAction();
        $from = $this->Bot->getFrom();
        $this->Bot->send(__(
            $this->messages['whoami'],
            $from->first_name.' '.$from->last_name,
            $from->username,
            $from->id
        ));
    }

    /**
     * Comando del Bot que envía la fecha y hora del servidor
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-06-28
     */
    protected function _bot_date()
    {
        $this->Bot->sendChatAction();
        $this->Bot->send(date('Y-m-d H:i:s'));
    }

    /**
     * Comando del Bot que envía un mensaje al contacto de la aplicación
     * @param msg Mensaje que se desea enviar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-07-02
     */
    protected function _bot_support($msg = null)
    {
        if (!$msg) {
            $this->setNextCommand('support');
            $this->Bot->Send(__($this->messages['support']['msg']));
        } else {
            $this->setNextCommand();
            $this->Bot->sendChatAction();
            $msg = implode(' ', func_get_args());
            $email = new \sowerphp\core\Network_Email();
            $email->to(\sowerphp\core\Configure::read('email.default.to'));
            $email->subject(__($this->messages['support']['subject'], $this->Bot->getFrom()->username, $this->Bot, date('YmdHis')));
            $msg .= "\n\n".'-- '."\n".$this->Bot->getFrom()->first_name.' '.$this->Bot->getFrom()->last_name."\n".'https://telegram.me/'.$this->Bot->getFrom()->username."\n".$this->Bot.' - Telegram Bot';
            $status = $email->send($msg);
            if ($status===true) {
                $this->Bot->Send(__($this->messages['support']['ok']));
            } else {
                $this->Bot->Send(__($this->messages['support']['bad']));
            }
        }
    }

    /**
     * Comando del Bot que solicita un token para parear la cuenta de usuario con la de Telegram
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-10-18
     */
    protected function _bot_token()
    {
        // si la cuenta ya esta pareada no se puede solicitar nuevo token
        $Usuario = $this->getAuthUser(false);
        if ($Usuario) {
            $this->Bot->Send(__($this->messages['auth']['notoken'], $Usuario->usuario));
        }
        // obtener token, verificar que no esté ya en la cache y escribir para recordar
        else {
            do {
                $token = rand(111111, 999999);
            } while ($this->Cache->get('telegram.pairing.'.$token));
            $this->Cache->set('telegram.pairing.'.$token, ['id'=>$this->Bot->getFrom()->id, 'username'=>$this->Bot->getFrom()->username]);
            $this->Bot->Send(__($this->messages['auth']['token'], $token));
        }
    }

    /**
     * Comando del Bot que cierra la sesión (desparea) al usuario
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-10-17
     */
    protected function _bot_logout()
    {
        if (!$Usuario = $this->getAuthUser()) {
            return false;
        }
        $Usuario->set([
            'config_telegram_id' => null,
            'config_telegram_username' => null,
        ]);
        $Usuario->save();
        $this->Usuario = null;
        $this->Bot->Send(__($this->messages['auth']['logout'], $this->Bot->getFrom()->username));
    }

    /**
     * Método del Bot que permite obtener el usuario autenticado (si es que está pareado)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-10-24
     */
    protected function getAuthUser($mensaje = true)
    {
        if (!isset($this->Usuario)) {
            $this->Usuario = (new \sowerphp\app\Sistema\Usuarios\Model_Usuarios())->getUserByTelegramID(
                $this->Bot->getFrom()->id, $this->Auth->settings['model']
            );
            if (!$this->Usuario or !$this->Usuario->activo) {
                if ($mensaje) {
                    $this->Bot->Send(__($this->messages['auth']['invalid'], $this->Bot->getFrom()->username));
                }
                return false;
            }
        }
        return $this->Usuario;
    }

}
