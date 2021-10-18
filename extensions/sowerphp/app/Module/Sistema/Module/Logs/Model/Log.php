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
namespace sowerphp\app\Sistema\Logs;

/**
 * Clase para mapear la tabla log de la base de datos
 * Comentario de la tabla:
 * Esta clase permite trabajar sobre un registro de la tabla log
 * @author SowerPHP Code Generator
 * @version 2015-04-28 11:56:29
 */
class Model_Log extends \Model_App
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'log'; ///< Tabla del modelo

    // Atributos de la clase (columnas en la base de datos)
    public $id; ///< bigint(20) NOT NULL DEFAULT '' AUTO PK
    public $fechahora; ///< datetime() NOT NULL DEFAULT 'CURRENT_TIMESTAMP'
    public $identificador; ///< varchar(255) NOT NULL DEFAULT ''
    public $origen; ///< smallint(5) NOT NULL DEFAULT ''
    public $gravedad; ///< smallint(5) NOT NULL DEFAULT ''
    public $usuario; ///< int(10) NULL DEFAULT '' FK:usuario.id
    public $ip; ///< varchar(45) NULL DEFAULT ''
    public $solicitud; ///< varchar(2000) NULL DEFAULT ''
    public $mensaje; ///< text(65535) NOT NULL DEFAULT ''

    // Información de las columnas de la tabla en la base de datos
    public static $columnsInfo = array(
        'id' => array(
            'name'      => 'ID',
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
            'name'      => 'Fecha y hora',
            'comment'   => '',
            'type'      => 'datetime',
            'length'    => null,
            'null'      => false,
            'default'   => 'CURRENT_TIMESTAMP',
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'identificador' => array(
            'name'      => 'Identificador',
            'comment'   => '',
            'type'      => 'varchar',
            'length'    => 255,
            'null'      => false,
            'default'   => '',
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'origen' => array(
            'name'      => 'Origen',
            'comment'   => '',
            'type'      => 'smallint',
            'length'    => 5,
            'null'      => false,
            'default'   => '',
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
        'usuario' => array(
            'name'      => 'Usuario',
            'comment'   => '',
            'type'      => 'int',
            'length'    => 10,
            'null'      => true,
            'default'   => '',
            'auto'      => false,
            'pk'        => false,
            'fk'        => array('table' => 'usuario', 'column' => 'id')
        ),
        'ip' => array(
            'name'      => 'IP',
            'comment'   => '',
            'type'      => 'varchar',
            'length'    => 45,
            'null'      => true,
            'default'   => '',
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'solicitud' => array(
            'name'      => 'Solicitud',
            'comment'   => '',
            'type'      => 'varchar',
            'length'    => 2000,
            'null'      => true,
            'default'   => '',
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'mensaje' => array(
            'name'      => 'Mensaje',
            'comment'   => '',
            'type'      => 'text',
            'length'    => 65535,
            'null'      => false,
            'default'   => '',
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),

    );

    // Comentario de la tabla en la base de datos
    public static $tableComment = '';

    public static $fkNamespace = array(
        'Model_Usuario' => 'sowerphp\app\Sistema\Usuarios'
    ); ///< Namespaces que utiliza esta clase

    /**
     * Método que recupera el objeto del origen
     * @param facility Origen que se quiere obtener su objeto
     * @return std_class
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-04-29
     */
    public function getFacility($facility = null)
    {
        if ($facility===null)
            $facility = $this->origen;
        $data = [
            LOG_KERN => (object)['glosa'=>'KERN'],
            LOG_USER => (object)['glosa'=>'USER'],
            LOG_MAIL => (object)['glosa'=>'MAIL'],
            LOG_AUTH => (object)['glosa'=>'AUTH'],
            LOG_NEWS => (object)['glosa'=>'NEWS'],
            LOG_CRON => (object)['glosa'=>'CRON'],
            LOG_LOCAL0 => (object)['glosa'=>'LOCAL0'],
            LOG_LOCAL1 => (object)['glosa'=>'LOCAL1'],
            LOG_LOCAL2 => (object)['glosa'=>'LOCAL2'],
            LOG_LOCAL3 => (object)['glosa'=>'LOCAL3'],
            LOG_LOCAL4 => (object)['glosa'=>'LOCAL4'],
            LOG_LOCAL5 => (object)['glosa'=>'LOCAL5'],
            LOG_LOCAL6 => (object)['glosa'=>'LOCAL6'],
            LOG_LOCAL7 => (object)['glosa'=>'LOCAL7'],
        ];
        return isset($data[$facility]) ? $data[$facility] : $facility;
    }

    /**
     * Método que recupera el objeto de la gravedad
     * @param severity Gravedad que se quiere obtener su objeto
     * @return std_class
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-04-29
     */
    public function getSeverity($severity = null)
    {
        if ($severity===null)
            $severity = $this->gravedad;
        $data = [
            LOG_EMERG => (object)['glosa'=>'EMERG', 'style'=>'danger'],
            LOG_ALERT => (object)['glosa'=>'ALERT', 'style'=>'danger'],
            LOG_CRIT => (object)['glosa'=>'CRIT', 'style'=>'danger'],
            LOG_ERR => (object)['glosa'=>'ERR', 'style'=>'danger'],
            LOG_WARNING => (object)['glosa'=>'WARNING', 'style'=>'warning'],
            LOG_NOTICE => (object)['glosa'=>'NOTICE', 'style'=>'info'],
            LOG_INFO => (object)['glosa'=>'INFO', 'style'=>'success'],
            LOG_DEBUG => (object)['glosa'=>'DEBUG', 'style'=>'default'],
        ];
        return isset($data[$severity]) ? $data[$severity] : $severity;
    }

}
