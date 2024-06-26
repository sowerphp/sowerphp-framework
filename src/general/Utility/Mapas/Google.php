<?php

/**
 * SowerPHP: Framework PHP hecho en Chile.
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

namespace sowerphp\general;

/**
 * Clase para consumir datos de la API de Google Maps.
 */
class Utility_Mapas_Google
{

    private $api_key; ///< Llave para la API de Google Maps

    /**
     * Constructor del objeto de mapas para Google.
     */
    public function __construct($api_key = null)
    {
        $this->api_key = $api_key ?? config('services.google.api_key');
    }

    /**
     * Método que entrega las coordenadas geográficas a partir de una dirección
     */
    public function getCoordenadas($direccion)
    {
        $rest = new \sowerphp\core\Network_Http_Rest();
        $response = $rest->get(
            'https://maps.google.com/maps/api/geocode/json?address='.urlencode($direccion).'&key='.$this->api_key
        );
        if ($response['status']['code'] != 200 || empty($response['body']['results'][0]['geometry']['location'])) {
            return [false, false];
        }
        return [$response['body']['results'][0]['geometry']['location']['lat'], $response['body']['results'][0]['geometry']['location']['lng']];
    }

}
