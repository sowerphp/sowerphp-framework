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

namespace sowerphp\core;

/**
 * Clase para el envío de correo electrónico
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2020-02-09
 */
class Network_Email
{

    protected $Sender; ///< Objeto que hará el envío del correo electrónico
    protected $default_methods = [
        'smtp' => 'pear',
    ]; ///< Método por defecto a usar si no se indicó uno

    // datos del correo que se enviará
    protected $from = null; ///< Quién envía el correo
    protected $replyTo = null; ///< A quien se debe responder el correo enviado
    protected $to_default = null; ///< A quien se debe enviar el correo por defecto (si no se indican destinatarios)
    protected $to = []; ///< Listado de destinatarios
    protected $cc = []; ///< Listado de destinatarios CC
    protected $bcc = []; ///< Listado de destinatarios BCC
    protected $subject = null; ///< Asunto del correo que se enviará
    protected $attach = []; ///< Archivos adjuntos

    /**
     * Constructor de la clase
     * @param config Configuración del correo electrónico que se usará
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2020-02-09
     */
    public function __construct($config = 'default')
    {
        // Si no es arreglo, es el nombre de la configuración
        if (!is_array($config)) {
            $config = \SowerPHP\core\Configure::read('email.'.$config);
        }
        // determinar "from" por defecto
        if (isset($config['from']) or isset($config['user'])) {
            if (isset($config['from'])) {
                $this->from($config['from']);
                unset($config['from']);
            } else {
                $this->from($config['user']);
            }
        }
        // determinar "to" por defecto
        if (!empty($config['to'])) {
            $this->to_default = $config['to'];
            unset($config['to']);
        }
        // crear objeto que enviará el correo
        $this->Sender = $this->getSender($config);
    }

    /**
     * Método que obtiene el objeto que se usará para el envío de los correos
     * @param config Configuración del correo electrónico que se usará
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2020-02-09
     */
    private function getSender(array $config)
    {
        // se ponen valores por defecto
        $config = array_merge([
            'type' => 'smtp',
            'debug' => false,
        ], $config);
        // determinar método a usar para enviar correo
        if (strpos($config['type'], '-')) {
            list($protocol, $method) = explode('-', $config['type']);
        } else {
            $protocol = $config['type'];
            if (!empty($this->default_methods[$protocol])) {
                $method = $this->default_methods[$protocol];
            } else {
                throw new Exception('No existe un método por defecto para el protocolo '.$protocol);
            }
        }
        $class = __NAMESPACE__.'\Network_Email_'.ucfirst($protocol).'_'.ucfirst($method);
        // crear objeto que enviará los correos
        return new $class($config);
    }

    /**
     * Asignar desde que cuenta enviar el correo
     * @param email Correo desde donde se envía supuestamente el email
     * @param name Nombre de quien envía supuestamente el email
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-05-29
     */
    public function from($email, $name = null) {
        if (is_array($email)) {
            $name = $email['name'];
            $email = $email['email'];
        }
        if ($name) {
            $this->from = [
                'name' => $name,
                'email' => $email,
            ];
        } else {
            $this->from = $email;
        }
    }

    /**
     * Define a quién se debe responder el correo
     * @param email Correo electrónico a quien responder
     * @param name Nombre a quien responder
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-05-29
     */
    public function replyTo($email, $name = null)
    {
        if ($name) {
            $this->replyTo = [
                'email' => $email,
                'name' => $name,
            ];
        } else {
            $this->replyTo = $email;
        }
    }

    /**
     * Asigna la lista de destinatarios
     * @param email Email o arreglo con los emails que se desean agregar como destinatarios
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-26
     */
    public function to($email)
    {
        // En caso que se haya pasado un arreglo con los correos
        if (is_array($email)) {
            // Asignar los correos, no se copia directamente el arreglo para
            // Poder eliminar los duplicados
            foreach ($email as &$e) {
                $this->to($e);
            }
        }
        // En caso que se haya pasado un solo correo
        else if (!in_array($email, $this->to)) {
            $this->to[] = $email;
        }
    }

    /**
     * Asigna la lista de destinatarios CC
     * @param email Email o arreglo con los emails que se desean agregar como destinatarios
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-04-03
     */
    public function cc($email)
    {
        // En caso que se haya pasado un arreglo con los correos
        if (is_array($email)) {
            // Asignar los correos, no se copia directamente el arreglo para
            // Poder eliminar los duplicados
            foreach ($email as &$e) {
                $this->cc($e);
            }
        }
        // En caso que se haya pasado un solo correo
        else if (!in_array($email, $this->cc)) {
            $this->cc[] = $email;
        }
    }

    /**
     * Asigna la lista de destinatarios BCC
     * @param email Email o arreglo con los emails que se desean agregar como destinatarios
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-09-16
     */
    public function bcc($email)
    {
        // En caso que se haya pasado un arreglo con los correos
        if (is_array($email)) {
            // Asignar los correos, no se copia directamente el arreglo para
            // Poder eliminar los duplicados
            foreach ($email as &$e) {
                $this->bcc($e);
            }
        }
        // En caso que se haya pasado un solo correo
        else if (!in_array($email, $this->bcc)) {
            $this->bcc[] = $email;
        }
    }

    /**
     * Asignar asunto del correo electrónico
     * @param subject Asunto del correo electrónico
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2010-10-09
     */
    public function subject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * Agregar un archivo para enviar en el correo
     * @param src Arreglo (formato de $_FILES) con el archivo a adjuntar o ruta al archivo a adjuntar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-05-08
     */
    public function attach($src)
    {
        if (!is_array($src)) {
            $src = [
                'tmp_name' => $src,
                'name' => basename($src),
                'type' => (new \finfo(FILEINFO_MIME_TYPE))->file($src),
            ];
        }
        $this->attach[] = $src;
    }

    /**
     * Enviar correo electrónico
     * @param msg Cuerpo del mensaje que se desea enviar (arreglo o string)
     * @return Arreglo asociativo con los estados de cada correo enviado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2020-02-09
     */
    public function send($msg)
    {
        // Si el mensaje no es un arreglo se crea, asumiendo que se paso en
        // formato texto
        if (!is_array($msg)) {
            $msg = ['text' => $msg];
        }
        // Si no se ha indicado a quién enviar el correo se utilizará el por
        // defecto indicado en la configuración (si existiese)
        if (empty($this->to[0])) {
            if (!empty($this->to_default)) {
                $this->to($this->to_default);
            }
            else if (empty($this->cc) and empty($this->bcc)) {
                throw new Exception('No existe destinatario del correo electrónico');
            }
        }
        // Crear datos (incluyendo adjuntos)
        $data = [
            'text'      => !empty($msg['text']) ? $msg['text'] : null,
            'html'      => !empty($msg['html']) ? $msg['html'] : null,
            'attach'    => $this->attach
        ];
        // Crear header
        $header = [
            'from'      => $this->from,
            'replyTo'   => $this->replyTo,
            'to'        => $this->to,
            'cc'        => $this->cc,
            'bcc'       => $this->bcc,
            'subject'   => $this->subject
        ];
        // Enviar mensaje a todos los destinatarios
        return $this->Sender->send($data, $header);
    }

}
