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

// namespace del modelo
namespace sowerphp\app;

/**
 * Wrapper para la autorización secundaria usando Latch
 * Requiere (en Debian GNU/Linux) paquete: php5-curl
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2017-12-23
 */
class Model_Datasource_Auth2_Latch extends Model_Datasource_Auth2_Base
{

    /**
     * Constructor de la clase
     * @param config Configuración de la autorización secundaria
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-12-23
     */
    public function __construct($config)
    {
        $this->config = array_merge([
            'name' => 'Latch',
            'url' => 'https://latch.elevenpaths.com',
            'default' => false,
        ], $config);
        $this->Auth2 = new \ElevenPaths\Latch\Latch(
            $this->config['app_id'],
            $this->config['app_key']
        );
    }

    /**
     * Método que crea un token a partir del código entregado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-12-23
     */
    public function create(array $data = [])
    {
        $Response = $this->Auth2->pair($data['verification']);
        if ($Response->error) {
            throw new \Exception($Response->error->getMessage());
        }
        return [
            'accountId' => $Response->data->accountId,
        ];
    }

    /**
     * Método que destruye el token en la autorización secundaria
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-12-23
     */
    public function destroy(array $data = [])
    {
        $Response = $this->Auth2->unpair($data['accountId']);
        if ($Response->error) {
            throw new \Exception($Response->error->getMessage());
        }
        return true;
    }

    /**
     * Método que valida el estado del token con la autorización secundaria
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-12-23
     */
    public function check(array $data = [])
    {
        $Response = $this->Auth2->status($data['accountId']);
        if ($Response->error || $Response->data===null) {
            if (!$this->config['default']) {
                if ($Response->error) {
                    $msg = $Response->error->getMessage();
                } else {
                    $msg = 'No fue posible comunicar con Latch para validar token';
                }
                throw new \Exception($msg);
            }
        }
        if ($Response->data->operations->{$this->config['app_id']}->status != 'on') {
            throw new \Exception('El token de Latch se encuentra bloqueado');
        }
        return true;
    }

}
