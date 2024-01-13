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
 * Clase abstracta de la que heredarán todas las entradas de Ldap
 */
abstract class Model_Datasource_Ldap_Entry
{

    /**
     * Método que asigna los atributos de la clase a partir de los datos de una
     * entrada de LDAP
     */
    public function setFromEntry($entry)
    {
        if (is_array($entry)) {
            $vars = array_keys(get_object_vars($this));
            foreach ($vars as $var) {
                $key = strtolower($var);
                if ($var == 'dn') {
                    $this->dn = $entry['dn'];
                } else if (isset($entry[$key]) && !empty($entry[$key]['count'])) {
                    if ($entry[$key]['count'] == 1) {
                        $this->{$var} = $entry[$key][0];
                    } else {
                        $this->{$var} = [];
                        for ($i=0; $i < $entry[$key]['count']; $i++) {
                            $this->{$var}[] = $entry[$key][$i];
                        }
                    }
                }
            }
        }
    }

}
