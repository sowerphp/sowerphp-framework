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

namespace sowerphp\core;

use Illuminate\Validation\Factory as ValidatorFactory;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\DatabasePresenceVerifier;

/**
 * Servicio de validación de datos.
 */
class Service_Validator implements Interface_Service
{

    /**
     * Aplicación.
     *
     * @var App
     */
    protected $app;

    /**
     * Servicio de traducción.
     *
     * @var Service_Translator
     */
    protected $translatorService;

    /**
     * Servicio de base de datos.
     *
     * @var Service_Database
     */
    protected $databaseService;

    /**
     * Fábrica para construir objetos de validación.
     *
     * @var \Illuminate\Validation\Factory
     */
    protected $validatorFactory;

    /**
     * Constructor del servicio de validación de datos.
     *
     * @param App $app
     * @param Service_Translator $translatorService
     * @param Service_Database $databaseService
     */
    public function __construct(
        App $app,
        Service_Translator $translatorService,
        Service_Database $databaseService
    )
    {
        $this->app = $app;
        $this->translatorService = $translatorService;
        $this->databaseService = $databaseService;
    }

    /**
     * Registra el servicio de validación de datos.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Inicializa el servicio de validación de datos.
     *
     * @return void
     */
    public function boot(): void
    {
    }

    /**
     * Finaliza el servicio de validación de datos.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Entrega la fábrica para construir objetos de validación.
     *
     * @return ValidatorFactory
     */
    protected function getValidatorFactory(): ValidatorFactory
    {
        if (!isset($this->validatorFactory)) {
            $this->validatorFactory = new ValidatorFactory(
                $this->translatorService,
                $this->app->getContainer()
            );
            $presenceVerifier = new DatabasePresenceVerifier(
                $this->databaseService->getDatabaseManager()
            );
            $this->validatorFactory->setPresenceVerifier($presenceVerifier);
        }
        return $this->validatorFactory;
    }

    /**
     * Valida los datos de la solicitud según las reglas definidas.
     *
     * Crea una instancia del `ValidatorFactory` utilizando los servicios de
     * traducción y eventos. Valida los datos de la solicitud y lanza una
     * `ValidationException` si la validación falla.
     *
     * @param array $data Arreglo con los datos que se desean validar.
     * @param array $rules Reglas de validación a aplicar.
     * @param array $messages Mensajes personalizados de validación.
     * @param array $customAttributes Atributos personalizados de los campos.
     * @return array Datos validados.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): array
    {
        $validator = $this->getValidatorFactory()->make(
            $data,
            $rules,
            $messages,
            $customAttributes
        );
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        return $validator->validated();
    }

    /**
     * Obtiene los datos validados según los parámetros y reglas definidas.
     *
     * Este método extiende la funcionalidad del método `validate()` para
     * manejar configuraciones adicionales como valores por defecto y
     * callbacks. Itera sobre los parámetros solicitados, aplica las reglas
     * de validación y maneja los mensajes de error personalizados. Si la
     * validación falla, captura la excepción, convierte los errores en
     * un string y relanza la excepción.
     *
     * @param array $data Arreglo con los datos que se desean validar.
     * @param array $params Arreglo de parámetros con sus configuraciones.
     * @param string|null $errorMessage Variable para almacenar el mensaje de
     * error completo (la suma de mensajes) si la validación falla.
     * @return array Datos validados con sus valores por defecto si corresponden.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function getValidatedData(array $data, array $params, &$errorMessage = null): array
    {
        // Argumentos para el método Request::validate().
        $validateArgs = [
            'rules' => [],
            'messages' => [],
            'customAttributes' => [],
        ];
        // Iterar cada uno de los parámetros solicitados.
        foreach ($params as $param => &$config) {
            // Si se pasó el nombre del parámetro solamente se corrige y se
            // arma como corresponde.
            if (is_int($param)) {
                $param = $config;
                $config = null;
            }
            // Si la configuración no es arreglo, entonces se pasó solo el
            // valor por defecto. Entonces se armga el arreglo config con dicho
            // valor por defecto.
            if (!is_array($config)) {
                $config = [
                    'default' => $config,
                ];
            }
            // Se asegura que la configuración del parámetro tenga todos los
            // índices con su configuración por defecto.
            $config = array_merge([
                'name' => $param,
                'default' => null,
                'rules' => 'nullable',
                'callback' => null,
                'messages' => [],
            ], $config);
            // Armar argumentos para el método Request::validate() que validará
            // lo solicitado.
            $validateArgs['rules'][$param] = $config['rules'];
            foreach ($config['messages'] as $key => $value) {
                $validateArgs['messages'][$param . '.'. $key] = $value;
            }
            $validateArgs['customAttributes'][$param] = $config['name'];
            // Armar datos por defecto.
            $default[$param] = $config['default'];
        }
        // Obtener los datos validados.
        try {
            $validatedData = array_merge($default, $this->validate(
                $data,
                $validateArgs['rules'],
                $validateArgs['messages'],
                $validateArgs['customAttributes']
            ));
        } catch (ValidationException $e) {
            $errorMessage = [];
            foreach ($e->errors() as $field => $messages) {
                $errorMessage[] = implode(' ', $messages);
            }
            $errorMessage = implode(' ', $errorMessage);
            throw $e;
        }
        // Aplicar callback a los datos (solo si no son el valor por defecto).
        foreach ($validatedData as $param => &$value) {
            if ($value !== $params[$param]['default']) {
                if (
                    isset($params[$param]['callback'])
                    && is_callable($params[$param]['callback'])
                ) {
                    $value = call_user_func($params[$param]['callback'], $value);
                }
            }
        }
        // Entregar los datos encontrados.
        return $validatedData;
    }

}
