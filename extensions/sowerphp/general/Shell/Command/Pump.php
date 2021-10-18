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

namespace sowerphp\general;

/**
 * Obtiene los últimos mensajes publicados en Pump.io u otra red que lo use
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2015-01-06
 */
class Shell_Command_Pump extends \sowerphp\core\Shell_App
{

    private $config = [
        'sslcheck' => false,
    ]; ///< Configuración del comando

    public function main($user, $limit = 5)
    {
        $this->out('Obteniendo mensajes de '.$user.'');
        $entries = $this->getEntries($user, $limit, $this->config['sslcheck']);
        $buffer = '';
        foreach ($entries as &$entry) {
            $buffer .= '<div>'.$entry['content'].' <span><a href="'.$entry['link'].'">'.$entry['published'].'</a></span></div>'."\n";
        }
        $this->createFile(TMP.'/pump', $buffer);
        $this->showStats();
    }

    private function getEntries($user, $limit = false, $sslcheck = true)
    {
        $body = file_get_contents(
            'https://pump2rss.com/feed/'.$user.'.atom',
            false,
            stream_context_create([
                'ssl' => [
                    'verify_peer' => $sslcheck,
                    'allow_self_signed' => !$sslcheck,
                ]
            ])
        );
        $xml = new \SimpleXMLElement($body);
        $count = 0;
        $entries = [];
        foreach($xml->entry as $entry) {
            $entries[] = [
                'id' => (string)$entry->id,
                'link' => (string)$entry->link->attributes()['href'],
                'published' => date('d/m/Y H:i', strtotime($entry->published)),
                'content' => (string)$entry->content,
            ];
            if ($limit and ++$count==$limit)
                break;
        }
        return $entries;
    }

}
