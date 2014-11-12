<?php

/**
 * SowerPHP: Minimalist Framework for PHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
 */

namespace sowerphp\core;

/**
 * Clase para el envío de correo electrónico
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-09-16
 */
class Network_Email
{

    protected $_config = null; ///< Arreglo con la configuración para el correo electrónico
    protected $_replyTo = null; ///< A quien se debe responder el correo enviado
    protected $_to = array(); ///< Listado de destinatarios
    protected $_bcc = array(); ///< Listado de destinatarios BCC
    protected $_subject = null; ///< Asunto del correo que se enviará
    protected $_attach = array(); ///< Archivos adjuntos
    protected $_debug = false; ///< Si se debe mostrar datos de debug o no

    /**
     * Constructor de la clase
     * @param config Configuración del correo electrónico que se usará
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-04
     */
    public function __construct($config = 'default')
    {
        // Si es un arreglo, se asume es la configuración directamente
        if (is_array($config)) {
            $this->_config = $config;
        }
        // Si no es arreglo, es el nombre de la configuración
        else {
            $this->_config = \SowerPHP\core\Configure::read('email.'.$config);
        }
        // si no están los campos mínimos necesarios error
        if (empty($this->_config['type']) || empty($this->_config['host']) || empty($this->_config['port']) || empty($this->_config['user']) || empty($this->_config['pass'])) {
             throw new Exception('Configuración del correo electrónico incompleta');
        }
        // si from no existe se asigna
        if (!isset($this->_config['from'])) {
            $this->_config['from'] = array (
                'email' => $this->_config['user'],
                'name' => $this->_config['user'],
            );
        }
        // si from no es arreglo se asume que se indicó el nombre y se crea arreglo
        else if (!is_array($this->_config['from'])) {
            $this->_config['from'] = array (
                'email' => $this->_config['user'],
                'name' => $this->_config['from'],
            );
        }
    }

    /**
     * Método para asignar si hay o no (por defecto) debug
     * @param debug =true hay debug, =false no hay debug
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-02-22
     */
    public function setDebug ($debug = false)
    {
        $this->_debug = $debug;
    }

    /**
     * Asignar desde que cuenta enviar el correo
     * @param email Correo desde donde se envía supuestamente el email
     * @param name Nombre de quien envía supuestamente el email
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-06-03
     */
    public function from ($email, $name = null) {
        $this->_config['from'] = array (
            'email' => $email,
            'name' => $name?$name:$email,
        );
    }

    /**
     * Define a quién se debe responder el correo
     * @param email Correo electrónico a quien responder
     * @param name Nombre a quien responder
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2010-10-09
     */
    public function replyTo ($email, $name = null)
    {
        if ($name==null) $name = $email;
            $this->_replyTo = array($name.' <'.$email.'>');
    }

    /**
     * Asigna la lista de destinatarios
     * @param email Email o arreglo con los emails que se desean agregar como destinatarios
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-26
     */
    public function to ($email)
    {
        // En caso que se haya pasado un arreglo con los correos
        if (is_array($email)) {
            // Asignar los correos, no se copia directamente el arreglo para
            // Poder eliminar los duplicados
            foreach ($email as &$e)
                $this->to($e);
        }
        // En caso que se haya pasado un solo correo
        else if (!in_array($email, $this->_to))
            $this->_to[] = $email;
    }

    /**
     * Asigna la lista de destinatarios BCC
     * @param email Email o arreglo con los emails que se desean agregar como destinatarios
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-09-16
     */
    public function bcc ($email)
    {
        // En caso que se haya pasado un arreglo con los correos
        if (is_array($email)) {
            // Asignar los correos, no se copia directamente el arreglo para
            // Poder eliminar los duplicados
            foreach ($email as &$e)
                $this->bcc($e);
        }
        // En caso que se haya pasado un solo correo
        else if (!in_array($email, $this->_bcc))
            $this->_bcc[] = $email;
    }

    /**
     * Asignar asunto del correo electrónico
     * @param subject Asunto del correo electrónico
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2010-10-09
     */
    public function subject ($subject)
    {
        $this->_subject = $subject;
    }

    /**
     * Agregar un archivo para enviar en el correo
     * @param src Arreglo (formato de $_FILES) con el archivo a adjuntar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2010-10-09
     */
    public function attach ($src)
    {
        $this->_attach[] = $src;
    }

    /**
     * Enviar correo electrónico
     * @param msg Cuerpo del mensaje que se desea enviar (arreglo o string)
     * @return Arreglo asociativo con los estados de cada correo enviado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-11-11
     */
    public function send ($msg)
    {
        // Si el mensaje no es un arreglo se crea, asumiendo que se paso en formato texto
        if (!is_array($msg)) {
            $msg = array('text'=>$msg);
        }
        // Si no se ha indicado a quién responder usar el usuario que envía
        if (!$this->_replyTo) {
            if (is_array($this->_config['from']))
                $this->_replyTo = array($this->_config['from']['name'].' <'.$this->_config['from']['email'].'>');
            else
                $this->_replyTo = array($this->_config['from'].' <'.$this->_config['from'].'>');
        }
        // Si no se ha indicado a quién enviar el correo se utilizará el de la
        // configuración
        if (!isset($this->_to[0])) {
            if (isset($this->_config['to'])) {
                $this->to($this->_config['to']);
            } else {
                throw new Exception('No existe destinatario del correo electrónico');
            }
        }
        // Crear header
        $header = array(
            'from'=>$this->_config['from'],
            'replyTo'=>$this->_replyTo,
            'to'=>$this->_to,
            'bcc'=>$this->_bcc,
            'subject'=>$this->_subject
        );
        unset($this->_config['from']);
        // Crear datos (incluyendo adjuntos)
        $data = array(
            'text'=>$msg['text'],
            'html'=>isset($msg['html'])?$msg['html']:null,
            'attach'=>$this->_attach
        );
        // Crear correo
        $class = '\Network_Email_'.ucfirst($this->_config['type']);
        $email = new $class($this->_config, $header, $data, $this->_debug);
        // Enviar mensaje a todos los destinatarios
        return $email->send();
    }

}
