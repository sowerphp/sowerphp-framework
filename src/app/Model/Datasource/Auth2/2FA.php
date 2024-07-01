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

namespace sowerphp\app;

/**
 * Wrapper para el uso de 2FA
 */
class Model_Datasource_Auth2_2FA extends Model_Datasource_Auth2_Base
{

    protected $need_token = true; ///< 2FA asigna un token aleatorio al usuario

    /**
     * Constructor de la clase
     * @param config Configuración de la autorización secundaria con 2FA
     */
    public function __construct($config)
    {
        $this->config = array_merge([
            'name' => '2FA',
            'url' => 'https://authy.com',
            'secret' => true,
        ], $config);
        $this->Auth2 = new \RobThree\Auth\TwoFactorAuth($this->config['app_url']);
    }

    /**
     * Método que crea el código secreato para parear la aplicación
     * @param user Nombre del usuario que se desea parear
     */
    public function createSecret($user = null)
    {
        $secret = $this->Auth2->createSecret();
        return (object)[
            'text' => $secret,
            'qr' => $this->Auth2->getQRCodeImageAsDataUri($this->config['app_url'].':'.$user, $secret),
        ];
    }

    /**
     * Método que crea un token a partir del código entregado
     */
    public function create(array $data = [])
    {
        if (!$this->Auth2->verifyCode($data['secret'], $data['verification'])) {
            throw new \Exception('Token de pareo no válido.');
        }
        return [
            'secret' => $data['secret'],
        ];
    }

    /**
     * Método que destruye el token en la autorización secundaria
     */
    public function destroy(array $data = [])
    {
        return true;
    }

    /**
     * Método que valida el estado del token con la autorización secundaria
     */
    public function check(array $data = [])
    {
        if (empty($data['token']) || !$this->Auth2->verifyCode((string)$data['secret'], (string)$data['token'])) {
            throw new \Exception('Token de 2FA no es válido.');
        }
        return true;
    }

}
