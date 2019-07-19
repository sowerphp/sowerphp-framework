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
     * @version 2015-09-25
     */
    public function __construct(array $config)
    {
        if (!isset($config['mailbox'])) {
            // definir puerto si no se pasó
            if (!isset($config['port']) and isset($config['ssl']) and !$config['ssl'])
                $config['port'] = 143;
            $this->config = array_merge($this->config, $config);
            // extraer puerto si se pasó en el host
            $url = parse_url($this->config['host']);
            if (isset($url['port'])) {
                $this->config['host'] = str_replace(':'.$url['port'], '', $this->config['host']);
                $this->config['port'] = $url['port'];
            }
            // definir mailbox
            $this->config['mailbox'] = $this->createMailbox();
        } else {
            $this->config = $config;
        }
        // conectar
        $this->link = @imap_open(
            $this->config['mailbox'],
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
        if (is_resource($this->link)) {
            imap_close($this->link);
        }
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
     * Método que entrega el número de mensaje a partir del UID
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-04-07
     */
    public function getMsgNumber($uid)
    {
        return imap_msgno($this->link, $uid);
    }

    /**
     * Método que entrega la información de la cabecera del mensaje
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-04-07
     */
    public function getHeaderInfo($uid)
    {
        return imap_headerinfo($this->link, $this->getMsgNumber($uid));
    }

    /**
     * Método que entrega rescata un mensaje desde la casilla de correo
     * @param uid UID del mensaje que se desea obtener
     * @param filter Arreglo con filtros a usar para las partes del mensaje. Ej: ['subtype'=>['PLAIN', 'XML'], 'extension'=>['xml']]
     * @return Arreglo con los datos del mensaje, índices: header, body, charset y attachments
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-06-11
     */
    public function getMessage($uid, $filter = [])
    {
        imap_errors();
        $header_string = imap_fetchheader($this->link, $uid, FT_UID);
        $errors = imap_errors();
        if ($errors) {
            throw new \Exception(implode("\n", $errors));
        }
        $message = [
            'uid' => $uid,
            'date' => null,
            'charset' => '',
            'header' => imap_rfc822_parse_headers($header_string),
            'body' => ['plain'=>'', 'html'=>''],
            'attachments' => [],
        ];
        $message['date'] = $this->getMessageDate($message);
        $s = imap_fetchstructure($this->link, $uid, FT_UID);
        if (!is_object($s)) {
            return false;
        }
        // el correo es simple y no tiene múltiples partes
        if (empty($s->parts)) {
            $this->getMessagePart($uid, $s, 0, $message);
        }
        // correo tiene múltiples partes, entonces se itera cada una de las partes
        else {
            foreach ($s->parts as $partno0 => $p) {
                if (!$filter) {
                    $this->getMessagePart($uid, $p, $partno0+1, $message);
                } else {
                    // si es del subtipo de agrega
                    $subtype = isset($filter['subtype']) ? array_map('strtoupper', $filter['subtype']) : [];
                    if (in_array(strtoupper($p->subtype), $subtype)) {
                        $this->getMessagePart($uid, $p, $partno0+1, $message);
                    }
                    // buscar por extensión del archivo adjunto (si lo es)
                    else if (isset($filter['extension']) and (($p->ifdisposition and strtoupper($p->disposition)=='ATTACHMENT') or (in_array($p->subtype, ['OCTET-STREAM', '*']) and ($p->ifparameters or $p->ifdparameters)))) {
                        $extension = array_map('strtolower', $filter['extension']);
                        $add = false;
                        $params = $p->ifparameters ? $p->parameters : ( $p->ifdparameters ? $p->dparameters : [] );
                        foreach ($params as $parameter) {
                            $value = strpos($parameter->value, '=?UTF-8?Q?')===0 ? imap_utf8($parameter->value) : $parameter->value;
                            if (in_array(strtolower(substr($value, -3)), $extension)) {
                                $add = true;
                                break;
                            }
                        }
                        if ($add) {
                            $this->getMessagePart($uid, $p, $partno0+1, $message, true);
                        }
                    }
                }
            }
        }
        // decodificar
        if (!empty($message['header']->subject)) {
            $message['header']->subject = utf8_encode(imap_mime_header_decode($message['header']->subject)[0]->text);
        }
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
     * @version 2016-08-18
     */
    private function getMessagePart($uid, $p, $partno, &$message, $attachment = false)
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
        if ($attachment or ($p->ifdisposition and strtolower($p->disposition)=='attachment') or isset($params['filename'])) {
            // filename may be given as 'Filename' or 'Name' or both
            $filename = isset($params['filename']) ? $params['filename'] : $params['name'];
            // filename may be encoded, so see imap_mime_header_decode()
            $message['attachments'][] = [
                'name' => $filename,
                'data' => $data,
                'size' => !empty($p->bytes) ? $p->bytes : null, // WARNING a veces genera PHP Notice:  Undefined property: stdClass::$bytes
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
            if (isset($params['charset']))
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
     * Método que entrega la fecha de recepción del mensaje
     * @param uid UID del mensaje que se desea conocer la fecha
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-06-11
     */
    private function getMessageDate($m)
    {
        // opción 1: consultar datos existentes
        if (!empty($m['header']->date)) {
            $time = strtotime($m['header']->date);
            if ($time) {
                return date('Y-m-d H:i:s', strtotime($m['header']->date));
            }
        }
        // opción 2: consultar cabecera a través del uid
        $header_info = $this->getHeaderInfo($m['uid']);
        if (!empty($header_info->udate)) {
            return date('Y-m-d H:i:s', $header_info->udate);
        }
        // no se determinó la fecha
        return false;
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

    /**
     * Método que marca un mensaje como leído
     * @param uid UID del mensaje que se desea marcar como leído
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-09-25
     */
    public function setSeen($uid, $flag = '\Seen')
    {
        return imap_setflag_full($this->link, $uid, $flag, ST_UID);
    }

}
