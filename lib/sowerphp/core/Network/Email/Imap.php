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
 * Clase para interacturar con un servidor de correo IMAP
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2015-01-05
 */
class Network_Email_Imap
{

    protected $config = [
        'host' => 'localhost',
        'port' => 993,
        'ssl' => true,
        'sslcheck' => true,
        'folder' => 'INBOX',
    ]; ///< Configuración para IMAP
    protected $link = null; ///< Conexión al servidor IMAP

    /**
     * Constructor de la clase
     * @param config Arreglo con la configuración del servidor IMAP
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-01-05
     */
    public function __construct($config)
    {
        if (!isset($config['port']) and isset($config['ssl']) and !$config['ssl'])
            $config['port'] = 143;
        $this->config = array_merge($this->config, $config);
        $this->link = @imap_open(
            $this->createMailbox(),
            $this->config['user'],
            $this->config['pass']
        );
    }

    /**
     * Destructor de la clase
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-01-05
     */
    public function __destruct()
    {
        if (is_resource($this->link))
            imap_close($this->link);
    }

    /**
     * Método que crea la dirección del Mailbox que se utilizará para las
     * funciones de IMAP
     * @param folder Carpeta en caso que se quira usar una diferente a la configuración de la conexión
     * @return Dirección completa para acceder al Mailbox en IMAP
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-01-05
     */
    private function createMailbox($folder = null)
    {
        if ($folder === null) $folder = $this->config['folder'];
        $options = ['imap'];
        if ($this->config['ssl']) {
            $options[] = 'ssl';
            if (!$this->config['sslcheck'])
                $options[] = 'novalidate-cert';
        }
        return '{'.$this->config['host'].':'.$this->config['port'].'/'.implode('/',$options).'}'.$folder;
    }

    /**
     * Método que indica si se está o no conectado al servidor IMAP
     * @return =true si se está conectado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-01-05
     */
    public function isConnected()
    {
        return (boolean)$this->link;
    }

    /**
     * Método que comprueba la casilla de correo
     * @return Arreglo con los datos de la casilla de correo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-01-05
     */
    public function check()
    {
        return (array)imap_check($this->link);
    }

    /**
     * Método que entrega la cantidad de mensajes de la casilla de correo
     * @return Cantidad de mensajes en la casilla (leídos y no leídos)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-01-05
     */
    public function countMessages()
    {
        return imap_num_msg($this->link);
    }

    /**
     * Método que entrega la cantidad de mensajes sin leer de la casilla de correo
     * @return Cantidad de mensajes sin leer
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-01-05
     */
    public function countUnreadMessages()
    {
        return imap_status($this->link, $this->createMailbox(), SA_ALL)->unseen;
    }

    /**
     * Método que entrega la información de estado de una casilla de correo
     * @return Arreglo con el estado de la casilla de correo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-01-05
     */
    public function status()
    {
        return (array)imap_status($this->link, $this->createMailbox(), SA_ALL);
    }

    /**
     * Método que realiza una búsqueda sobre los mensajes de la casilla de correo
     * @param filter Criterios de búsqueda para la casilla
     * @return Arreglo con UIDs de los mensajes que coincidían con el filtro
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-03-14
     */
    public function search($filter = 'UNSEEN')
    {
        $uids = imap_search($this->link, $filter, SE_UID);
        return $uids===false  ? [] : $uids;
    }

    /**
     * Método que entrega rescata un mensaje desde la casilla de correo
     * @param uid UID del mensaje que se desea obtener
     * @return Arreglo con los datos del mensaje, índices: header, body, charset y attachments
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-06-27
     */
    public function getMessage($uid)
    {
        $message = [
            'header' => imap_rfc822_parse_headers(imap_fetchheader($this->link, $uid, FT_UID)),
            'body' => ['plain'=>'', 'html'=>''],
            'charset' => '',
            'attachments' => [],
        ];
        $s = imap_fetchstructure($this->link, $uid, FT_UID);
        if (!is_object($s))
            return false;
        // el correo es simple y no tiene múltiples partes
        if (!$s->parts) {
            $this->getMessagePart($this->link, $uid, $s, 0);
        }
        // correo tiene múltiples partes, entonces se itera cada una de las partes
        else {
            foreach ($s->parts as $partno0 => $p) {
                $this->getMessagePart($uid, $p, $partno0+1, $message);
            }
        }
        // decodificar
        $message['header']->subject = utf8_encode(imap_mime_header_decode($message['header']->subject)[0]->text);
        // entregar mensaje
        return $message;
    }

    /**
     * Referencia: http://php.net/manual/es/function.imap-fetchstructure.php#85486
     * @param uid UID del mensaje del cual se desea obtener una parte
     * @param p Metadados de la parte del cuerpo
     * @param partno '1', '2', '2.1', '2.1.3', etc for multipart, 0 if simple
     * @param message Arreglo con el mensaje, se agregan partes por referencia
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-01-05
     */
    private function getMessagePart($uid, $p, $partno, &$message)
    {
        // DECODE DATA
        $data = $partno ?
                imap_fetchbody($this->link, $uid, $partno, FT_UID | FT_PEEK) :  // multipart
                imap_body($this->link, $uid, FT_UID | FT_PEEK); // simple
        // Any part may be encoded, even plain text messages, so check everything.
        if ($p->encoding==4)
            $data = quoted_printable_decode($data);
        elseif ($p->encoding==3)
            $data = base64_decode($data);

        // PARAMETERS
        // get all parameters, like charset, filenames of attachments, etc.
        $params = [];
        if ($p->ifparameters)
            foreach ($p->parameters as $x)
                $params[strtolower($x->attribute)] = $x->value;
        if ($p->ifdparameters)
            foreach ($p->dparameters as $x)
                $params[strtolower($x->attribute)] = $x->value;

        // ATTACHMENT
        if ($p->ifdisposition and $p->disposition=='attachment') {
            // filename may be given as 'Filename' or 'Name' or both
            $filename = isset($params['filename']) ? $params['filename'] : $params['name'];
            // filename may be encoded, so see imap_mime_header_decode()
            $message['attachments'][] = [
                'name' => $filename,
                'data' => $data,
                'size' => null,
                'type' => (new \finfo(FILEINFO_MIME_TYPE))->buffer($data),
            ];
        }

        // TEXT
        if ($p->type==0 && $data) {
            // Messages may be split in different parts because of inline attachments,
            // so append parts together with blank row.
            if (strtolower($p->subtype)=='plain')
                $message['body']['plain'] .= trim($data) ."\n\n";
            else
                $message['body']['html'] .= $data.'<br/><br/>';
            $message['charset'] = $params['charset'];  // assume all parts are same charset
        }

        // EMBEDDED MESSAGE
        // Many bounce notifications embed the original message as type 2,
        // but AOL uses type 1 (multipart), which is not handled here.
        // There are no PHP functions to parse embedded messages,
        // so this just appends the raw source to the main message.
        else if ($p->type==2 && $data) {
            $message['body']['plain'] .= $data."\n\n";
        }

        // SUBPART RECURSION
        if (isset($p->parts)) {
            foreach ($p->parts as $partno0=>$p2)
                $this->getMessagePart($uid, $p2, $partno.'.'.($partno0+1), $message);
        }

    }

    /**
     * Método que elimina un mensaje del buzón IMAP
     * @param uid UID del mensaje que se desea eliminar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-03-14
     */
    public function delete($uid)
    {
        imap_delete($this->link, $uid, FT_UID);
        imap_expunge($this->link);
    }

}
