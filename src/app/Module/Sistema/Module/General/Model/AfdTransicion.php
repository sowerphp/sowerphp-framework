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

namespace sowerphp\app\Sistema\General;

use sowerphp\autoload\Model;
use sowerphp\app\Sistema\General\Model_Afd;

/**
 * Modelo singular de la tabla "afd_transicion" de la base de datos.
 *
 * Permite interactuar con un registro de la tabla.
 */
class Model_AfdTransicion extends Model
{

    /**
     * Metadatos del modelo.
     *
     * @var array
     */
    protected $metadata = [
        'model' => [
            'db_table_comment' => 'Transición AFD',
            'ordering' => ['afd'],
        ],
        'fields' => [
            'afd' => [
                'type' => self::TYPE_STRING,
                'primary_key' => true,
                'relation' => Model_Afd::class,
                'belongs_to' => 'afd_estado',
                'related_field' => 'afd',
                'max_length' => 10,
                'verbose_name' => 'Afd',
                'display' => '(afd_estado.nombre)',
            ],
            'desde' => [
                'type' => self::TYPE_INTEGER,
                'primary_key' => true,
                'relation' => Model_Afd::class,
                'belongs_to' => 'afd_estado',
                'related_field' => 'codigo',
                'verbose_name' => 'Desde',
                'display' => '(afd_estado.codigo)',
            ],
            'valor' => [
                'type' => self::TYPE_STRING,
                'primary_key' => true,
                'max_length' => 5,
                'verbose_name' => 'Valor',
            ],
            'hasta' => [
                'type' => self::TYPE_INTEGER,
                'relation' => Model_Afd::class,
                'belongs_to' => 'afd_estado',
                'related_field' => 'codigo',
                'verbose_name' => 'Hasta',
                'display' => '(afd_estado.codigo)',
            ],
        ],
    ];

}
