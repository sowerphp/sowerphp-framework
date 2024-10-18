<?php

/**
 * SowerPHP: Simple and Open Web Ecosystem Reimagined for PHP.
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

namespace sowerphp\core;

use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

/**
 * Servicio de encriptación.
 */
class Service_Encryption implements Interface_Service
{
    /**
     * Servicio de configuración.
     *
     * @var Service_Config
     */
    protected $configService;

    /**
     * Método de cifrado por defecto.
     *
     * Se utiliza cuando no se especifica ninguno en la configuración.
     *
     * @var string
     */
    protected $defaultCipher = 'aes-256-cbc';

    /**
     * Métodos de cifrado y la clase que los encripta/decripta.
     *
     * @var array
     */
    protected $cipherMethods = [
        // Cipher oficial de Illuminate.
        'aes-128-cbc' => 'Aes',
        'aes-256-cbc' => 'Aes',
        'aes-128-gcm' => 'Aes',
        'aes-256-gcm' => 'Aes',
        // Cipher recomendado por PHP.
        'sodium' => 'Sodium',
        // Cipher obsoleto, jamás debería ser usado.
        'mcrypt' => 'Mcrypt',
    ];

    /**
     * Instancia del encriptador que se utilizará.
     *
     * Para utilizarlo se requiere en la configuración:
     *
     *   - app.key: el formato y largo de esta clave dependerá del app.cipher.
     *     Por ejemplo:
     *     - Para aes-128-cbc, la clave debe ser de 16 bytes.
     *     - Para aes-256-cbc, la clave debe ser de 32 bytes.
     *     - Para sodium, la clave debe ser de 32 bytes.
     *   - app.cipher:
     *     - Aes: aes-128-cbc, aes-256-cbc, aes-128-gcm, aes-256-gcm.
     *     - Sodium: sodium.
     *     - Mcrypt: mcrypt (cipher obsoleto, jamás debería ser usado).
     *
     * @var EncrypterContract
     */
    protected $encrypter;

    /**
     * Constructor del servicio de encriptación.
     *
     * @param Service_Config $configService Servicio de configuración.
     */
    public function __construct(Service_Config $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Registra el servicio de logging.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Inicializa el servicio de logging.
     *
     * @return void
     */
    public function boot(): void
    {
    }

    /**
     * Finaliza el servicio de logging.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Obtener la instancia del encriptador.
     *
     * @return EncrypterContract
     */
    protected function getEncrypter(): EncrypterContract
    {
        if (!isset($this->encrypter)) {
            $key = $this->getEncrypterKey();
            $cipher = $this->getEncrypterCipher();
            $this->encrypter = $this->getEncrypterInstance($key, $cipher);
        }

        return $this->encrypter;
    }

    /**
     * Obtener la llave (clave) de encriptación.
     *
     * @return string
     */
    protected function getEncrypterKey(): string
    {
        $key = $this->configService->get('app.key', '');
        if (empty($key)) {
            throw new \Exception(__(
                'Falta configuración de "%s".',
                'app.key'
            ));
        }

        if (strpos($key, 'base64:') === 0) {
            list($codificationMethod, $codedKey) = explode(':', $key);
            return base64_decode($codedKey);
        }

        return $key;
    }

    /**
     * Obtener el método de cifrado.
     *
     * El método de cifrado definirá la clase que se utilizará para el
     * encriptador. Ver atributo $cipherMethods para los métodos de cifrado y
     * la clase del encriptador asociada.
     *
     * @return string
     */
    protected function getEncrypterCipher(): string
    {
        $cipher = $this->configService->get('app.cipher');
        if (empty($cipher)) {
            $cipher = $this->defaultCipher;
        }

        return $cipher;
    }

    /**
     * Obtener la clase del encriptador.
     *
     * La clase de encriptación se obtiene a partir del método de cifrado
     * utilizando el mapa que relaciona ambos (métod/clase) que está en el
     * atributo $cipherMethods.
     *
     * @param string $cipher Método de encriptación.
     * @return string Clase de encriptador.
     */
    protected function getEncrypterClass(string $cipher): string
    {
        if (!isset($this->cipherMethods[$cipher])) {
            throw new \Exception(__(
                'El método de cifrado %s no está disponible.',
                $cipher
            ));
        }

        $class = '\sowerphp\autoload\Data_Encryption_'
            . $this->cipherMethods[$cipher]
        ;

        return $class;
    }

    /**
     * Obtener una instancia de encriptador según el método de cifrado.
     *
     * @param string $key Llave (clave) de encriptación.
     * @param string $cipher Método de encriptación.
     * @return EncrypterContract Instancia del encriptador asociado al método
     * de cifrado indicado.
     */
    public function getEncrypterInstance(string $key, string $cipher): EncrypterContract
    {
        $class = $this->getEncrypterClass($cipher);
        $encrypter = new $class($key, $cipher);

        return $encrypter;
    }

    /**
     * Método que encripta los datos con el encriptador configurado en el
     * servicio.
     *
     * @param mixed $value Datos que se desean encriptar.
     * @param boolean $serialize Indica si se deben serializar los datos.
     * @return string Datos encriptados.
     */
    public function encrypt($value, $serialize = true): string
    {
        return $this->getEncrypter()->encrypt($value, $serialize);
    }

    /**
     * Método que desencripta los datos con el encriptador configurado en el
     * servicio.
     *
     * @param mixed $value Datos que se desean desencriptar.
     * @param boolean $unserialize Indica si se deben deserializar los datos.
     * @return string Datos desencriptados.
     */
    public function decrypt($payload, $unserialize = true)
    {
        return $this->getEncrypter()->decrypt($payload, $unserialize);
    }
}
