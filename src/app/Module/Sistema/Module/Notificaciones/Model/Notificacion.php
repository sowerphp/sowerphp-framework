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

// namespace del modelo
namespace sowerphp\app\Sistema\Notificaciones;

/**
 * Clase para mapear la tabla notificacion de la base de datos
 * Comentario de la tabla:
 * Esta clase permite trabajar sobre un registro de la tabla notificacion
 */
class Model_Notificacion extends \Model_App
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'notificacion'; ///< Tabla del modelo

    // Atributos de la clase (columnas en la base de datos)
    public $id; ///< bigint(20) NOT NULL DEFAULT '' AUTO PK
    public $fechahora; ///< datetime() NOT NULL DEFAULT 'CURRENT_TIMESTAMP'
    public $gravedad; ///< smallint(5) NOT NULL DEFAULT ''
    public $de; ///< int(10) NULL DEFAULT '' FK:usuario.id
    public $para; ///< int(10) NOT NULL DEFAULT '' FK:usuario.id
    public $descripcion; ///< text(65535) NOT NULL DEFAULT ''
    public $icono; ///< varchar(50) NULL DEFAULT ''
    public $enlace; ///< varchar(2000) NULL DEFAULT ''
    public $leida; ///< tinyint(3) NOT NULL DEFAULT '0'

    // Información de las columnas de la tabla en la base de datos
    public static $columnsInfo = array(
        'id' => array(
            'name'      => 'Id',
            'comment'   => '',
            'type'      => 'bigint',
            'length'    => 20,
            'null'      => false,
            'default'   => '',
            'auto'      => true,
            'pk'        => true,
            'fk'        => null
        ),
        'fechahora' => array(
            'name'      => 'Fechahora',
            'comment'   => '',
            'type'      => 'datetime',
            'length'    => null,
            'null'      => false,
            'default'   => 'CURRENT_TIMESTAMP',
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'gravedad' => array(
            'name'      => 'Gravedad',
            'comment'   => '',
            'type'      => 'smallint',
            'length'    => 5,
            'null'      => false,
            'default'   => '',
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'de' => array(
            'name'      => 'De',
            'comment'   => '',
            'type'      => 'int',
            'length'    => 10,
            'null'      => true,
            'default'   => '',
            'auto'      => false,
            'pk'        => false,
            'fk'        => array('table' => 'usuario', 'column' => 'id')
        ),
        'para' => array(
            'name'      => 'Para',
            'comment'   => '',
            'type'      => 'int',
            'length'    => 10,
            'null'      => false,
            'default'   => '',
            'auto'      => false,
            'pk'        => false,
            'fk'        => array('table' => 'usuario', 'column' => 'id')
        ),
        'descripcion' => array(
            'name'      => 'Descripcion',
            'comment'   => '',
            'type'      => 'text',
            'length'    => 65535,
            'null'      => false,
            'default'   => '',
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'icono' => array(
            'name'      => 'Icono',
            'comment'   => '',
            'type'      => 'varchar',
            'length'    => 50,
            'null'      => true,
            'default'   => '',
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'enlace' => array(
            'name'      => 'Enlace',
            'comment'   => '',
            'type'      => 'varchar',
            'length'    => 2000,
            'null'      => true,
            'default'   => '',
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'leida' => array(
            'name'      => 'Leida',
            'comment'   => '',
            'type'      => 'tinyint',
            'length'    => 3,
            'null'      => false,
            'default'   => '0',
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),

    );

    // Comentario de la tabla en la base de datos
    public static $tableComment = '';

    public static $fkNamespace = array(
        'Model_Usuario' => 'sowerphp\app\Sistema\Usuarios',
    ); ///< Namespaces que utiliza esta clase

    /**
     * Método que marca una notifiación como leída
     */
    public function leida()
    {
        $this->leida = true;
        $this->save();
    }

    /**
     * Método que recupera el objeto del origen
     * @param facility Origen que se quiere obtener su objeto
     * @return std_class
     */
    public function getFacility($facility = null)
    {
        if ($facility === null) {
            $facility = $this->de;
        }
        return (object)['glosa' => $facility ? 'USER' : 'KERN'];
    }

    /**
     * Método que recupera el objeto de la gravedad
     * @param severity Gravedad que se quiere obtener su objeto
     * @return std_class
     */
    public function getSeverity($severity = null)
    {
        if ($severity === null) {
            $severity = $this->gravedad;
        }
        $data = [
            LOG_EMERG => (object)['glosa' => 'EMERG', 'style' => 'danger'],
            LOG_ALERT => (object)['glosa' => 'ALERT', 'style' => 'danger'],
            LOG_CRIT => (object)['glosa' => 'CRIT', 'style' => 'danger'],
            LOG_ERR => (object)['glosa' => 'ERR', 'style' => 'danger'],
            LOG_WARNING => (object)['glosa' => 'WARNING', 'style' => 'warning'],
            LOG_NOTICE => (object)['glosa' => 'NOTICE', 'style' => 'info'],
            LOG_INFO => (object)['glosa' => 'INFO', 'style' => 'success'],
            LOG_DEBUG => (object)['glosa' => 'DEBUG', 'style' => 'default'],
        ];
        return isset($data[$severity]) ? $data[$severity] : $severity;
    }

}
