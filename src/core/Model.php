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

use \stdClass;
use \Carbon\Carbon;
use \Illuminate\Config\Repository;
use \Illuminate\Support\Str;

/**
 * Clase abstracta para todos los modelos.
 *
 * Permite trabajar con un registro de la tabla.
 */
abstract class Model implements \ArrayAccess, \JsonSerializable
{

    /**
     * Se utiliza el trait de objetos para las funcionalidades básicas de un
     * objeto del modelo.
     */
    use Trait_Object;

    /**
     * Acción en cascada.
     *
     * Esta constante representa la acción de eliminar en cascada, donde los
     * registros relacionados también se eliminan.
     */
    const CASCADE = 'CASCADE';

    /**
     * Protección de la eliminación.
     *
     * Esta constante representa la acción de proteger el registro relacionado,
     * lanzando un error si se intenta eliminar.
     */
    const PROTECT = 'PROTECT';

    /**
     * Restricción de la eliminación.
     *
     * Esta constante representa la acción de restringir la eliminación a
     * través del registro relacionado, lanzando un error si se intenta
     * eliminar dicho registro relacionado.
     */
    const RESTRICT = 'RESTRICT';

    /**
     * Establecer a NULL.
     *
     * Esta constante representa la acción de establecer el valor del campo relacionado a NULL cuando el registro relacionado se elimina.
     */
    const SET_NULL = 'SET_NULL';

    /**
     * Establecer al valor por defecto.
     *
     * Esta constante representa la acción de establecer el valor del campo relacionado al valor por defecto cuando el registro relacionado se elimina.
     */
    const SET_DEFAULT = 'SET_DEFAULT';

    /**
     * No hacer nada.
     *
     * Esta constante representa la acción de no hacer nada cuando el registro relacionado se elimina.
     */
    const DO_NOTHING = 'DO_NOTHING';

    /**
     * Incremento grande.
     *
     * Categoría: Tipos Enteros y Flotantes.
     *
     * Este tipo representa una columna de incremento grande en la base de datos.
     *
     * @param string|null $column Nombre de la columna.
     * @example bigIncrements('id')
     */
    const TYPE_BIG_INCREMENTS = 'bigIncrements';

    /**
     * Entero grande.
     *
     * Categoría: Tipos Enteros y Flotantes.
     *
     * Este tipo representa una columna de entero grande en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example bigInteger('votes')
     */
    const TYPE_BIG_INTEGER = 'bigInteger';

    /**
     * Número decimal.
     *
     * Categoría: Tipos Enteros y Flotantes.
     *
     * Este tipo representa una columna de número decimal en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @param int|null $precision Precisión del número decimal (opcional).
     * @param int|null $scale Escala del número decimal (opcional).
     * @example decimal('amount', 8, 2)
     */
    const TYPE_DECIMAL = 'decimal';

    /**
     * Número doble.
     *
     * Categoría: Tipos Enteros y Flotantes.
     *
     * Este tipo representa una columna de número doble en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @param int|null $total Total de dígitos (opcional).
     * @param int|null $places Número de decimales (opcional).
     * @example double('amount', 15, 8)
     */
    const TYPE_DOUBLE = 'double';

    /**
     * Número flotante.
     *
     * Categoría: Tipos Enteros y Flotantes.
     *
     * Este tipo representa una columna de número flotante en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example float('amount')
     */
    const TYPE_FLOAT = 'float';

    /**
     * Incremento.
     *
     * Categoría: Tipos Enteros y Flotantes.
     *
     * Este tipo representa una columna de incremento en la base de datos.
     *
     * @param string|null $column Nombre de la columna.
     * @example increments('id')
     */
    const TYPE_INCREMENTS = 'increments';

    /**
     * Entero.
     *
     * Categoría: Tipos Enteros y Flotantes.
     *
     * Este tipo representa una columna de entero en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example integer('votes')
     */
    const TYPE_INTEGER = 'integer';

    /**
     * Incremento mediano.
     *
     * Categoría: Tipos Enteros y Flotantes.
     *
     * Este tipo representa una columna de incremento mediano en la base de datos.
     *
     * @param string|null $column Nombre de la columna.
     * @example mediumIncrements('id')
     */
    const TYPE_MEDIUM_INCREMENTS = 'mediumIncrements';

    /**
     * Entero mediano.
     *
     * Categoría: Tipos Enteros y Flotantes.
     *
     * Este tipo representa una columna de entero mediano en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example mediumInteger('votes')
     */
    const TYPE_MEDIUM_INTEGER = 'mediumInteger';

    /**
     * Incremento pequeño.
     *
     * Categoría: Tipos Enteros y Flotantes.
     *
     * Este tipo representa una columna de incremento pequeño en la base de datos.
     *
     * @param string|null $column Nombre de la columna.
     * @example smallIncrements('id')
     */
    const TYPE_SMALL_INCREMENTS = 'smallIncrements';

    /**
     * Entero pequeño.
     *
     * Categoría: Tipos Enteros y Flotantes.
     *
     * Este tipo representa una columna de entero pequeño en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example smallInteger('votes')
     */
    const TYPE_SMALL_INTEGER = 'smallInteger';

    /**
     * Incremento diminuto.
     *
     * Categoría: Tipos Enteros y Flotantes.
     *
     * Este tipo representa una columna de incremento diminuto en la base de datos.
     *
     * @param string|null $column Nombre de la columna.
     * @example tinyIncrements('id')
     */
    const TYPE_TINY_INCREMENTS = 'tinyIncrements';

    /**
     * Entero diminuto.
     *
     * Categoría: Tipos Enteros y Flotantes.
     *
     * Este tipo representa una columna de entero diminuto en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example tinyInteger('votes')
     */
    const TYPE_TINY_INTEGER = 'tinyInteger';

    /**
     * Entero grande sin signo.
     *
     * Categoría: Tipos Enteros y Flotantes.
     *
     * Este tipo representa una columna de entero grande sin signo en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example unsignedBigInteger('votes')
     */
    const TYPE_UNSIGNED_BIG_INTEGER = 'unsignedBigInteger';

    /**
     * Número decimal sin signo.
     *
     * Categoría: Tipos Enteros y Flotantes.
     *
     * Este tipo representa una columna de número decimal sin signo en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @param int|null $precision Precisión del número decimal (opcional).
     * @param int|null $scale Escala del número decimal (opcional).
     * @example unsignedDecimal('amount', 8, 2)
     */
    const TYPE_UNSIGNED_DECIMAL = 'unsignedDecimal';

    /**
     * Entero sin signo.
     *
     * Categoría: Tipos Enteros y Flotantes.
     *
     * Este tipo representa una columna de entero sin signo en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example unsignedInteger('votes')
     */
    const TYPE_UNSIGNED_INTEGER = 'unsignedInteger';

    /**
     * Entero mediano sin signo.
     *
     * Categoría: Tipos Enteros y Flotantes.
     *
     * Este tipo representa una columna de entero mediano sin signo en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example unsignedMediumInteger('votes')
     */
    const TYPE_UNSIGNED_MEDIUM_INTEGER = 'unsignedMediumInteger';

    /**
     * Entero pequeño sin signo.
     *
     * Categoría: Tipos Enteros y Flotantes.
     *
     * Este tipo representa una columna de entero pequeño sin signo en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example unsignedSmallInteger('votes')
     */
    const TYPE_UNSIGNED_SMALL_INTEGER = 'unsignedSmallInteger';

    /**
     * Entero diminuto sin signo.
     *
     * Categoría: Tipos Enteros y Flotantes.
     *
     * Este tipo representa una columna de entero diminuto sin signo en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example unsignedTinyInteger('votes')
     */
    const TYPE_UNSIGNED_TINY_INTEGER = 'unsignedTinyInteger';

    /**
     * Columna de tipo char.
     *
     * Categoría: Tipos de Cadenas de Texto.
     *
     * Este tipo representa una columna de tipo char en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @param int|null $length Longitud de la cadena (opcional).
     * @example char('name', 100)
     */
    const TYPE_CHAR = 'char';

    /**
     * Columna de tipo string.
     *
     * Categoría: Tipos de Cadenas de Texto.
     *
     * Este tipo representa una columna de tipo string en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @param int|null $length Longitud de la cadena (opcional).
     * @example string('name', 100)
     */
    const TYPE_STRING = 'string';

    /**
     * Columna de tipo texto largo.
     *
     * Categoría: Tipos de Cadenas de Texto.
     *
     * Este tipo representa una columna de tipo texto largo en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example text('description')
     */
    const TYPE_TEXT = 'text';

    /**
     * Columna de tipo texto mediano.
     *
     * Categoría: Tipos de Cadenas de Texto.
     *
     * Este tipo representa una columna de tipo texto mediano en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example mediumText('description')
     */
    const TYPE_MEDIUM_TEXT = 'mediumText';

    /**
     * Columna de tipo texto largo.
     *
     * Categoría: Tipos de Cadenas de Texto.
     *
     * Este tipo representa una columna de tipo texto largo en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example longText('description')
     */
    const TYPE_LONG_TEXT = 'longText';

    /**
     * Columna de tipo UUID.
     *
     * Categoría: Tipos de Cadenas de Texto.
     *
     * Este tipo representa una columna de tipo UUID en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example uuid('id')
     */
    const TYPE_UUID = 'uuid';

    /**
     * Columna de tipo booleano.
     *
     * Categoría: Tipos Booleanos.
     *
     * Este tipo representa una columna de tipo booleano en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example boolean('is_active')
     */
    const TYPE_BOOLEAN = 'boolean';

    /**
     * Columna de tipo fecha.
     *
     * Categoría: Tipos de Fecha y Hora.
     *
     * Este tipo representa una columna de tipo fecha en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example date('created_at')
     */
    const TYPE_DATE = 'date';

    /**
     * Columna de tipo fecha y hora.
     *
     * Categoría: Tipos de Fecha y Hora.
     *
     * Este tipo representa una columna de tipo fecha y hora en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example dateTime('created_at')
     */
    const TYPE_DATE_TIME = 'dateTime';

    /**
     * Columna de tipo fecha y hora con zona horaria.
     *
     * Categoría: Tipos de Fecha y Hora.
     *
     * Este tipo representa una columna de tipo fecha y hora con zona horaria en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example dateTimeTz('created_at')
     */
    const TYPE_DATE_TIME_TZ = 'dateTimeTz';

    /**
     * Columna de tipo hora.
     *
     * Categoría: Tipos de Fecha y Hora.
     *
     * Este tipo representa una columna de tipo hora en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example time('created_at')
     */
    const TYPE_TIME = 'time';

    /**
     * Columna de tipo hora con zona horaria.
     *
     * Categoría: Tipos de Fecha y Hora.
     *
     * Este tipo representa una columna de tipo hora con zona horaria en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example timeTz('created_at')
     */
    const TYPE_TIME_TZ = 'timeTz';

    /**
     * Columna de tipo timestamp.
     *
     * Categoría: Tipos de Fecha y Hora.
     *
     * Este tipo representa una columna de tipo timestamp en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example timestamp('created_at')
     */
    const TYPE_TIMESTAMP = 'timestamp';

    /**
     * Columna de tipo timestamp con zona horaria.
     *
     * Categoría: Tipos de Fecha y Hora.
     *
     * Este tipo representa una columna de tipo timestamp con zona horaria en
     * la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example timestampTz('created_at')
     */
    const TYPE_TIMESTAMP_TZ = 'timestampTz';

    /**
     * Columna de tipo año.
     *
     * Categoría: Tipos de Fecha y Hora.
     *
     * Este tipo representa una columna de tipo año en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example year('birth_year')
     */
    const TYPE_YEAR = 'year';

    /**
     * Columna de tipo binario.
     *
     * Categoría: Tipos de Binarios.
     *
     * Este tipo representa una columna de tipo binario en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example binary('data')
     */
    const TYPE_BINARY = 'binary';

    /**
     * Columna de tipo JSON.
     *
     * Categoría: Tipos de JSON.
     *
     * Este tipo representa una columna de tipo JSON en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example json('data')
     */
    const TYPE_JSON = 'json';

    /**
     * Columna de tipo JSONB.
     *
     * Categoría: Tipos de JSON.
     *
     * Este tipo representa una columna de tipo JSONB en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example jsonb('data')
     */
    const TYPE_JSONB = 'jsonb';

    /**
     * Columna de tipo Geometry.
     *
     * Categoría: Tipos Geográficos.
     *
     * Este tipo representa una columna de tipo geometry en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example geometry('coordinates')
     */
    const TYPE_GEOMETRY = 'geometry';

    /**
     * Columna de tipo Point.
     *
     * Categoría: Tipos Geográficos.
     *
     * Este tipo representa una columna de tipo point en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example point('location')
     */
    const TYPE_POINT = 'point';

    /**
     * Columna de tipo LineString.
     *
     * Categoría: Tipos Geográficos.
     *
     * Este tipo representa una columna de tipo linestring en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example linestring('path')
     */
    const TYPE_LINESTRING = 'linestring';

    /**
     * Columna de tipo Polygon.
     *
     * Categoría: Tipos Geográficos.
     *
     * Este tipo representa una columna de tipo polygon en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example polygon('area')
     */
    const TYPE_POLYGON = 'polygon';

    /**
     * Columna de tipo MultiPoint.
     *
     * Categoría: Tipos Geográficos.
     *
     * Este tipo representa una columna de tipo multipoint en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example multipoint('locations')
     */
    const TYPE_MULTIPOINT = 'multipoint';

    /**
     * Columna de tipo MultiLineString.
     *
     * Categoría: Tipos Geográficos.
     *
     * Este tipo representa una columna de tipo multilinestring en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example multilinestring('paths')
     */
    const TYPE_MULTILINESTRING = 'multilinestring';

    /**
     * Columna de tipo MultiPolygon.
     *
     * Categoría: Tipos Geográficos.
     *
     * Este tipo representa una columna de tipo multipolygon en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example multipolygon('areas')
     */
    const TYPE_MULTIPOLYGON = 'multipolygon';

    /**
     * Columna de tipo GeometryCollection.
     *
     * Categoría: Tipos Geográficos.
     *
     * Este tipo representa una columna de tipo geometrycollection en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example geometrycollection('shapes')
     */
    const TYPE_GEOMETRYCOLLECTION = 'geometrycollection';

    /**
     * Columna de tipo IP Address.
     *
     * Categoría: Otros Tipos.
     *
     * Este tipo representa una columna de dirección IP en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example ipAddress('ip_address')
     */
    const TYPE_IP_ADDRESS = 'ipAddress';

    /**
     * Columna de tipo MAC Address.
     *
     * Categoría: Otros Tipos.
     *
     * Este tipo representa una columna de dirección MAC en la base de datos.
     *
     * @param string $column Nombre de la columna.
     * @example macAddress('mac_address')
     */
    const TYPE_MAC_ADDRESS = 'macAddress';

    /**
     * Columna de tipo Morphs.
     *
     * Categoría: Otros Tipos.
     *
     * Este tipo representa una columna para relaciones polymorphic en la base de datos.
     *
     * @param string $name Nombre de la relación.
     * @example morphs('taggable')
     */
    const TYPE_MORPHS = 'morphs';

    /**
     * Columna de tipo Nullable Morphs.
     *
     * Categoría: Otros Tipos.
     *
     * Este tipo representa una columna nullable para relaciones polymorphic en la base de datos.
     *
     * @param string $name Nombre de la relación.
     * @example nullableMorphs('taggable')
     */
    const TYPE_NULLABLE_MORPHS = 'nullableMorphs';

    /**
     * Columna de tipo Nullable UUID Morphs.
     *
     * Categoría: Otros Tipos.
     *
     * Este tipo representa una columna nullable UUID para relaciones polymorphic en la base de datos.
     *
     * @param string $name Nombre de la relación.
     * @example nullableUuidMorphs('taggable')
     */
    const TYPE_NULLABLE_UUID_MORPHS = 'nullableUuidMorphs';

    /**
     * Columna de tipo UUID Morphs.
     *
     * Categoría: Otros Tipos.
     *
     * Este tipo representa una columna UUID para relaciones polymorphic en la base de datos.
     *
     * @param string $name Nombre de la relación.
     * @example uuidMorphs('taggable')
     */
    const TYPE_UUID_MORPHS = 'uuidMorphs';

    /**
     * Columna de tipo Remember Token.
     *
     * Categoría: Otros Tipos.
     *
     * Este tipo representa una columna de token de recordatorio en la base de datos.
     *
     * @param string|null $column Nombre de la columna.
     * @example rememberToken()
     */
    const TYPE_REMEMBER_TOKEN = 'rememberToken';

    /**
     * Entrada de texto estándar.
     */
    const INPUT_TEXT = 'text';

    /**
     * Área de texto para múltiples líneas.
     */
    const INPUT_TEXTAREA = 'textarea';

    /**
     * Campo de entrada específico para direcciones de correo electrónico.
     */
    const INPUT_EMAIL = 'email';

    /**
     * Campo de entrada para contraseñas.
     */
    const INPUT_PASSWORD = 'password';

    /**
     * Campo de entrada para números.
     */
    const INPUT_NUMBER = 'number';

    /**
     * Selector de fechas.
     */
    const INPUT_DATE = 'date';

    /**
     * Selector de fecha y hora local.
     */
    const INPUT_DATETIME_LOCAL = 'datetime-local';

    /**
     * Selector de tiempo.
     */
    const INPUT_TIME = 'time';

    /**
     * Campo de entrada para URLs.
     */
    const INPUT_URL = 'url';

    /**
     * Campo de entrada para números de teléfono.
     */
    const INPUT_TEL = 'tel';

    /**
     * Casilla de verificación.
     */
    const INPUT_CHECKBOX = 'checkbox';

    /**
     * Botón de opción.
     */
    const INPUT_RADIO = 'radio';

    /**
     * Menú desplegable.
     */
    const INPUT_SELECT = 'select';

    /**
     * Campo de entrada oculto.
     */
    const INPUT_HIDDEN = 'hidden';

    /**
     * Campo de entrada para subir archivos.
     */
    const INPUT_FILE = 'file';

    /**
     * Control deslizante para seleccionar un rango de valores.
     */
    const INPUT_RANGE = 'range';

    /**
     * Selector de color.
     */
    const INPUT_COLOR = 'color';

    /**
     * Campo de entrada para búsquedas.
     */
    const INPUT_SEARCH = 'search';

    /**
     * Configuración por defecto del modelo.
     *
     * @var array
     */
    protected $defaultModelConfig = [
        // Nombre de la base de datos.
        'db_name' => 'default',
        // Nombre de la tabla en la base de datos.
        'db_table' => null,
        // Comentario de la tabla en la base de datos.
        'db_table_comment' => null,
        // Nombre singular del modelo para mostrar.
        'verbose_name' => null,
        // Nombre plural del modelo para mostrar.
        'verbose_name_plural' => null,
        // Espacio de nombres de la clase del modelo.
        'namespace' => null,
        // Clase del modelo en singular.
        'singular' => null,
        // Clase del modelo en plural.
        'plural' => null,
        // Etiqueta del modelo para mostrar.
        'label' => null,
        // Etiqueta del modelo en minúsculas.
        'label_lower' => null,
        // Clave primaria del modelo.
        'primary_key' => [],
        // Campos para ordenar por defecto.
        'ordering' => [],
        // Índices definidos en la tabla.
        'indexes' => [],
        // Restricciones como unique_together.
        'constraints' => [],
        // Campos para obtener el último registro.
        'get_latest_by' => [],
        // Campos para usar en choices.
        'choices' => ['id' => null, 'name' => null],
        // Permisos básicos por defecto.
        'default_permissions' => ['add', 'change', 'delete', 'view'],
        // Permisos adicionales específicos.
        'permissions' => [],
        // Características requeridas de la base de datos.
        'required_db_features' => [],
        // Proveedor de base de datos requerido.
        'required_db_vendor' => null,
        // Seleccionar el registro tras guardarlo.
        'select_on_save' => false,
    ];

    /**
     * Configuración por defecto de los campos.
     *
     * @var array
     */
    protected $defaultFieldConfig = [
        // Nombre del campo, usado internamente.
        'name' => null,
        // Etiqueta del campo para mostrar en formularios y vistas.
        'label' => null,
        // Nombre de la columna en la base de datos.
        'db_column' => null,
        // Tipo de dato del campo, como string, integer, etc.
        'type' => self::TYPE_STRING,
        // Indica si el campo es automático o no (no asignar, automático).
        'auto' => false,
        // Transforma el tipo del atributo al acceder o establecer su valor.
        'cast' => null,
        // Valor actual del campo, usado para pre-llenar formularios.
        'value' => null,
        // Indica si el campo debe ser único en la base de datos.
        'unique' => false,
        // Indica si el campo es la clave primaria de la tabla.
        'primary_key' => false,
        // Modelo relacionado para llaves foráneas.
        'foreign_key' => null,
        // Tabla del campo relacionado en la llave foránea.
        'to_table' => null,
        // Campo relacionado en la llave foránea.
        'to_field' => null,
        // Filtros para limitar las opciones disponibles en relaciones.
        'limit_choices_to' => [],
        // Modelo intermedio para relaciones ManyToMany.
        'through' => null,
        // Campos intermedios en relaciones ManyToMany.
        'through_fields' => null,
        // Acción a realizar al eliminar la llave foránea.
        'on_delete' => self::PROTECT,
        // Permite valores nulos en la base de datos.
        'null' => false,
        // Permite valores vacíos en formularios (no siempre igual a null).
        'blank' => false,
        // Longitud mínima permitida para el campo.
        'min_length' => null,
        // Longitud máxima permitida para el campo.
        'max_length' => null,
        // Opciones disponibles para el campo, usado en select inputs.
        'choices' => null,
        // Nombre descriptivo del campo, usado en interfaces de usuario.
        'verbose_name' => null,
        // Texto de ayuda mostrado junto al campo en formularios.
        'help_text' => null,
        // Valor por defecto en la base de datos, usado si no se provee otro.
        'default' => null,
        // Tipo de entrada en formularios, como text, number, etc.
        'input_type' => null,
        // Texto de marcador de posición para formularios.
        'placeholder' => null,
        // Valor inicial del campo en formularios antes de cualquier cambio.
        'initial_value' => null,
        // Indica si el campo es requerido en formularios.
        'required' => null,
        // Reglas de validación del campo, como min:3, max:255, etc.
        'validation' => null,
        // Reglas de sanitización del campo, como strip_tags, trim etc.
        'sanitize' => null,
        // Indica si el campo es editable en formularios.
        'editable' => true,
        // Indica si el campo es de solo lectura.
        'readonly' => false,
        // Indica si el campo se puede asignar masivamente.
        'fillable' => null,
        // Indica si el campo se almacena encriptado en la base de datos.
        'encrypt' => false,
        // Indica si el campo se debe mostrar en la vista de listado.
        'show_in_list' => true,
    ];

    /**
     * Configuración por defecto, adicional, de los campos según su tipo.
     *
     * @var array
     */
    protected $defaultFieldConfigByType = [
        // Tipos Enteros y Flotantes.
        self::TYPE_BIG_INCREMENTS => [
            'auto' => true,
            'cast' => 'integer',
            'input_type' => self::INPUT_NUMBER,
            'primary_key' => true,
            'unique' => true,
            'editable' => false,
            'verbose_name' => 'ID',
        ],
        self::TYPE_BIG_INTEGER => [
            'cast' => 'integer',
            'input_type' => self::INPUT_NUMBER,
        ],
        self::TYPE_DECIMAL => [
            'cast' => 'decimal:2',
            'input_type' => self::INPUT_NUMBER,
        ],
        self::TYPE_DOUBLE => [
            'cast' => 'double',
            'input_type' => self::INPUT_NUMBER,
        ],
        self::TYPE_FLOAT => [
            'cast' => 'float',
            'input_type' => self::INPUT_NUMBER,
        ],
        self::TYPE_INCREMENTS => [
            'auto' => true,
            'cast' => 'integer',
            'input_type' => self::INPUT_NUMBER,
            'primary_key' => true,
            'unique' => true,
            'editable' => false,
            'verbose_name' => 'ID',
        ],
        self::TYPE_INTEGER => [
            'input_type' => self::INPUT_NUMBER,
            'cast' => 'integer',
        ],
        self::TYPE_MEDIUM_INCREMENTS => [
            'auto' => true,
            'cast' => 'integer',
            'input_type' => self::INPUT_NUMBER,
            'primary_key' => true,
            'unique' => true,
            'editable' => false,
            'verbose_name' => 'ID',
        ],
        self::TYPE_MEDIUM_INTEGER => [
            'cast' => 'integer',
            'input_type' => self::INPUT_NUMBER,
        ],
        self::TYPE_SMALL_INCREMENTS => [
            'auto' => true,
            'cast' => 'integer',
            'input_type' => self::INPUT_NUMBER,
            'primary_key' => true,
            'unique' => true,
            'editable' => false,
            'verbose_name' => 'ID',
        ],
        self::TYPE_SMALL_INTEGER => [
            'cast' => 'integer',
            'input_type' => self::INPUT_NUMBER,
        ],
        self::TYPE_TINY_INCREMENTS => [
            'auto' => true,
            'cast' => 'integer',
            'input_type' => self::INPUT_NUMBER,
            'primary_key' => true,
            'unique' => true,
            'editable' => false,
            'verbose_name' => 'ID',
        ],
        self::TYPE_TINY_INTEGER => [
            'cast' => 'integer',
            'input_type' => self::INPUT_NUMBER,
        ],
        self::TYPE_UNSIGNED_BIG_INTEGER => [
            'cast' => 'integer',
            'input_type' => self::INPUT_NUMBER,
        ],
        self::TYPE_UNSIGNED_DECIMAL => [
            'cast' => 'decimal:2',
            'input_type' => self::INPUT_NUMBER,
        ],
        self::TYPE_UNSIGNED_INTEGER => [
            'cast' => 'integer',
            'input_type' => self::INPUT_NUMBER,
        ],
        self::TYPE_UNSIGNED_MEDIUM_INTEGER => [
            'cast' => 'integer',
            'input_type' => self::INPUT_NUMBER,
        ],
        self::TYPE_UNSIGNED_SMALL_INTEGER => [
            'cast' => 'integer',
            'input_type' => self::INPUT_NUMBER,
        ],
        self::TYPE_UNSIGNED_TINY_INTEGER => [
            'cast' => 'integer',
            'input_type' => self::INPUT_NUMBER,
        ],
        // Tipos de Cadenas de Texto.
        self::TYPE_CHAR => [
            'cast' => 'string',
            'input_type' => self::INPUT_TEXT,
            'max_length' => 1,
            'sanitize' => ['strip_tags', 'spaces', 'trim'],
        ],
        self::TYPE_STRING => [
            'cast' => 'string',
            'input_type' => self::INPUT_TEXT,
            'max_length' => 255,
            'sanitize' => ['strip_tags', 'spaces', 'trim'],
        ],
        self::TYPE_TEXT => [
            'cast' => 'string',
            'input_type' => self::INPUT_TEXTAREA,
            'sanitize' => ['strip_tags', 'spaces', 'trim'],
        ],
        self::TYPE_MEDIUM_TEXT => [
            'cast' => 'string',
            'input_type' => self::INPUT_TEXTAREA,
            'sanitize' => ['strip_tags', 'spaces', 'trim'],
        ],
        self::TYPE_LONG_TEXT => [
            'cast' => 'string',
            'input_type' => self::INPUT_TEXTAREA,
            'sanitize' => ['strip_tags', 'spaces', 'trim'],
        ],
        self::TYPE_UUID => [
            'cast' => 'string',
            'input_type' => self::INPUT_TEXT,
            'min_length' => 36,
            'max_length' => 36,
            'sanitize' => ['strip_tags', 'spaces', 'trim'],
        ],
        // Tipos Booleanos.
        self::TYPE_BOOLEAN => [
            'cast' => 'boolean',
            'input_type' => self::INPUT_SELECT,
            'choices' => [
                0 => 'No',
                1 => 'Si',
            ],
            'default' => 0,
        ],
        // Tipos de Fecha y Hora.
        self::TYPE_DATE => [
            'cast' => 'date',
            'input_type' => self::INPUT_DATE,
        ],
        self::TYPE_DATE_TIME => [
            'cast' => 'datetime',
            'input_type' => self::INPUT_DATETIME_LOCAL,
        ],
        self::TYPE_DATE_TIME_TZ => [
            'cast' => 'datetime',
            'input_type' => self::INPUT_DATETIME_LOCAL,
        ],
        self::TYPE_TIME => [
            'cast' => 'datetime',
            'input_type' => self::INPUT_TIME,
        ],
        self::TYPE_TIME_TZ => [
            'cast' => 'datetime',
            'input_type' => self::INPUT_TIME,
        ],
        self::TYPE_TIMESTAMP => [
            'cast' => 'timestamp',
            'input_type' => self::INPUT_DATETIME_LOCAL,
        ],
        self::TYPE_TIMESTAMP_TZ => [
            'cast' => 'timestamp',
            'input_type' => self::INPUT_DATETIME_LOCAL,
        ],
        self::TYPE_YEAR => [
            'input_type' => self::INPUT_TEXT,
        ],
        // Tipos de Binarios.
        self::TYPE_BINARY => [
            'input_type' => self::INPUT_FILE,
        ],
        // Tipos de JSON.
        self::TYPE_JSON => [
            'cast' => 'array',
            'input_type' => self::INPUT_TEXTAREA,
        ],
        self::TYPE_JSONB => [
            'cast' => 'array',
            'input_type' => self::INPUT_TEXTAREA,
        ],
        // Tipos Geográficos.
        self::TYPE_GEOMETRY => [
            'input_type' => self::INPUT_HIDDEN,
        ],
        self::TYPE_POINT => [
            'input_type' => self::INPUT_HIDDEN,
        ],
        self::TYPE_LINESTRING => [
            'input_type' => self::INPUT_HIDDEN,
        ],
        self::TYPE_POLYGON => [
            'input_type' => self::INPUT_HIDDEN,
        ],
        self::TYPE_MULTIPOINT => [
            'input_type' => self::INPUT_HIDDEN,
        ],
        self::TYPE_MULTILINESTRING => [
            'input_type' => self::INPUT_HIDDEN,
        ],
        self::TYPE_MULTIPOLYGON => [
            'input_type' => self::INPUT_HIDDEN,
        ],
        self::TYPE_GEOMETRYCOLLECTION => [
            'input_type' => self::INPUT_HIDDEN,
        ],
        // Otros Tipos.
        self::TYPE_IP_ADDRESS => [
            'cast' => 'string',
            'input_type' => self::INPUT_TEXT,
            'sanitize' => ['strip_tags', 'spaces', 'trim'],
        ],
        self::TYPE_MAC_ADDRESS => [
            'cast' => 'string',
            'input_type' => self::INPUT_TEXT,
            'sanitize' => ['strip_tags', 'spaces', 'trim'],
        ],
        self::TYPE_MORPHS => [
            'input_type' => self::INPUT_HIDDEN,
        ],
        self::TYPE_NULLABLE_MORPHS => [
            'input_type' => self::INPUT_HIDDEN,
        ],
        self::TYPE_NULLABLE_UUID_MORPHS => [
            'input_type' => self::INPUT_HIDDEN,
        ],
        self::TYPE_UUID_MORPHS => [
            'input_type' => self::INPUT_HIDDEN,
        ],
        self::TYPE_REMEMBER_TOKEN => [
            'cast' => 'string',
            'input_type' => self::INPUT_HIDDEN,
            'sanitize' => ['strip_tags', 'spaces', 'trim'],
        ],
    ];

    /**
     * Filtros disponibles para usar en la sanitización con filter_var().
     *
     * @var array
     */
    protected $filters = [
        'default' => FILTER_DEFAULT,
        'encoded' => FILTER_SANITIZE_ENCODED,
        'special_chars' => FILTER_SANITIZE_SPECIAL_CHARS,
        'email' => FILTER_SANITIZE_EMAIL,
        'url' => FILTER_SANITIZE_URL,
        'number_int' => FILTER_SANITIZE_NUMBER_INT,
        'number_float' => FILTER_SANITIZE_NUMBER_FLOAT,
        'add_slashes' => FILTER_SANITIZE_ADD_SLASHES,
    ];

    /**
     * Todos los metadatos del modelo y de los campos (atributos) del modelo.
     *
     * @var array|Repository
     */
    protected $meta = [];

    /**
     * Almacena los valores de los campos (atributos) del modelo.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Almacena la configuración extendida del modelo.
     *
     * Son atributos adicionales que estarán disponible a través de atributos
     * del modelo usando el prefijo `config_`.
     *
     * @var Repository
     */
    protected $configurations;

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * Para permitirlos todos, asignar '*' a $fillable;
     *
     * @var array|null
     */
    protected $fillable;

    /**
     * Los atributos que no se pueden asignar masivamente.
     *
     * Para protegerlos todos, asignar '*' a $guarded.
     *
     * @var array|null
     */
    protected $guarded;

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * Se soportan los siguientes tipos de casts:
     *
     *   - integer: Convierte el atributo a un entero.
     *   - real: Convierte el atributo a un número de punto flotante.
     *   - float: Alias para "real".
     *   - double: Alias para "real".
     *   - string: Convierte el atributo a una cadena de texto.
     *   - boolean: Convierte el atributo a un valor booleano.
     *   - object: Convierte el atributo a un objeto.
     *   - array: Convierte el atributo a un arreglo.
     *   - collection: Convierte el atributo a una instancia de
     *     Illuminate\Support\Collection.
     *   - date: Convierte el atributo a una instancia de Carbon\Carbon.
     *   - datetime: Convierte el atributo a una instancia de Carbon\Carbon,
     *     permite especificar el formato.
     *   - timestamp: Convierte el atributo a una marca de tiempo (timestamp).
     *
     * @var array
     */
    protected $casts;

    /**
     * Campos que pueden variar entre las operaciones de creación y edición.
     *
     * Este arreglo contiene los nombres de los campos que pueden tener
     * diferentes configuraciones dependiendo de si se trata de una operación
     * de creación o de edición.
     *
     * @var array
     */
    protected $variantFields = ['blank', 'required', 'validation'];

    /**
     * Instancia de la clase plural asociada a este modelo singular.
     *
     * @var Model_Plural
     */
    protected $pluralInstance;

    /**
     * Indica si el modelo existe en la base de datos.
     *
     * @var bool
     */
    protected $exists;

    /**
     * Constructor del modelo singular.
     *
     * @param array $id Arreglo con la llave primaria del modelo (opcional).
     */
    public function __construct(...$id)
    {
        $this->bootstrap();
        $this->retrieve($id);
    }

    /**
     * Obtiene una instancia de la clase plural asociada al modelo singular.
     *
     * @return Model_Plural
     */
    public function getPluralInstance(): Model_Plural
    {
        if (!isset($this->pluralInstance)) {
            $this->pluralInstance = new $this->meta['model.plural'](
                $this->meta
            );
        }
        return $this->pluralInstance;
    }

    /**
     * Recupera la conexión a la base de datos asociada al modelo.
     *
     * @return Database_Connection_Custom
     */
    protected function getDatabaseConnection(): Database_Connection_Custom
    {
        return $this->getPluralInstance()->getDatabaseConnection();
    }

    /**
     * Inicializar el modelo singular.
     *
     * Se realizan las siguientes acciones:
     *   - Crear repositorio con los metadatos del modelo.
     *   - Asignar la instancia del modelo plural.
     *
     * @return void
     */
    protected function bootstrap(): void
    {
        if (!is_object($this->meta)) {
            $this->meta = $this->getMeta($this->meta);
        }
        $this->pluralInstance = $this->getPluralInstance();
        self::$columnsInfo = $this->getColumnsInfo(); // TODO: eliminar al refactorizar.
    }

    protected function getUniqueFields(): array
    {
        $fields = [];
        foreach ($this->getMeta()['fields'] as $name => $config) {
            if ($config['unique']) {
                $fields[] = $name;
            }
        }
        return $fields;
    }

    /**
     * Obtiene los datos del modelo desde la base de datos mediante su llave
     * primaria u otras columnas que sean valores únicos si están disponibles.
     *
     * @param array $id Clave primaria del modelo.
     * @return stdClass|null
     */
    protected function retrieve(array $id): ?stdClass
    {
        if (empty($id)) {
            return null;
        }
        try {
            $filters = $this->getPrimaryKeyValues($id);
        } catch (\Exception $e) {
            $fields = $this->getUniqueFields();
            $filters = $this->toArray($fields, $id, false);
            $filters = array_filter($filters, function ($filter) {
                return $filter !== null;
            });
        }
        if (empty($filters)) {
            return null;
        }
        try {
            $result = $this->getPluralInstance()->retrieve($filters, true);
            $this->forceFill((array)$result);
            $this->exists = true;
        } catch (\Exception $e) {
            if ($e->getCode() != 404) {
                throw $e;
            }
            $result = null;
            $this->exists = false;
        }
        return $result;
    }

    /**
     * Entrega la instancia del modelo (objeto) como string.
     *
     * La arma utilizando los datos del modelo y la PK.
     *
     * Se recomienda sobrescribir este método en cada modelo.
     *
     * @return string
     */
    public function __toString(): string
    {
        return __($this->meta['model.label'] . '('
            . implode(', ', array_map(function ($pk) {
                return '%(' . $pk . ')s'; },
                $this->getPrimaryKey()
            ))
            . ')',
            $this->getPrimaryKeyValues()
        );
    }

    /**
     * Acciones que se ejecutan antes de serializar la instancia del modelo.
     *
     * Este método es llamado cuando se usa serialize() en un objeto.
     *
     * Permite realizar tareas de limpieza antes de que el objeto sea
     * serializado. Además, se puede usar para especificar qué propiedades del
     * objeto deben ser serializadas.
     */
    public function __sleep(): array
    {
        $unsetAttributes = [
            'defaultModelConfig',
            'defaultFieldConfig',
            'defaultFieldConfigByType',
            'filters',
            'fillable',
            'guarded',
            'casts',
            'variantFields',
            'reflector',
            'pluralInstance'
        ];
        foreach ($unsetAttributes as $attribute) {
            unset($this->$attribute);
        }
        return array_keys(get_object_vars($this));
    }

    /**
     * Acciones que se ejecutan después de deserializar la instancia del modelo.
     *
     * Este método es llamado cuando se usa unserialize() en un objeto.
     *
     * Permite restaurar cualquier recurso que el objeto pueda necesitar
     * después de ser deserializado.
     */
    public function __wakeup(): void
    {
        $this->bootstrap();
    }

    /**
     * Método que se llamará cuando se quiera serializar el objeto con
     * json_encode() permite especificar qué atributos se deben serializar y
     * cómomo se debe realizar dicha serialización.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Obtiene el valor de un atributo utilizando la sobrecarga de propiedades.
     *
     * Este método permite recuperar valores de:
     *
     *   - Atributos del modelo.
     *   - Configuraciones del modelo (opciones extendidas en otra tabla).
     *
     * @param string $key El nombre del atributo.
     * @return mixed|null El valor del atributo o null si no existe.
     */
    public function __get(string $key)
    {
        // Se solicita una opción de la configuración del modelo.
        if ($this->isConfigurationField($key)) {
            return $this->getConfiguration($key);
        }
        // Se solicita, probablemente, un atributo del modelo.
        return $this->getAttribute($key);
    }

    /**
     * Asigna el valor de un atributo utilizando la sobrecarga de propiedades.
     *
     * Este método permite asignar valores de:
     *
     *   - Atributos del modelo.
     *   - Configuraciones del modelo (opciones extendidas en otra tabla).
     *
     * @param string $key El nombre del atributo.
     * @param mixed $value El valor del atributo.
     * @return void
     */
    public function __set(string $key, $value): void
    {
        // Se asigna una opción de la configuración del modelo.
        if ($this->isConfigurationField($key)) {
            $this->setConfiguration($key, $value);
        }
        // Se asigna, probablemente, un atributo del modelo.
        $this->setAttribute($key, $value);
    }

    /**
     * Entrega el label de un atributo del modelo.
     *
     * @param string $attribute Atributo del objeto que generará el label.
     * @return string Label del atributo (desde configuración o generado).
     */
    protected function getAttributeLabel(string $attribute): string
    {
        $label = $this->meta['fields.' . $attribute . '.label'];
        if (strpos($label, ':')) {
            list($module, $label) = explode(':', $label);
        }
        if ($label === null) {
            $label = ucfirst(Str::camel($attribute));
        }
        return $label;
    }

    /**
     * Obtiene el valor de un atributo.
     *
     * @param string $key El nombre del atributo.
     * @return mixed|null El valor del atributo o null si no existe.
     */
    public function getAttribute(string $key)
    {
        // Obtener con accessor (no se realiza cast automático).
        $label = $this->getAttributeLabel($key);
        $accessor = 'get' . $label . 'Attribute';
        if ($label && method_exists($this, $accessor)) {
            return $this->$accessor();
        }
        // Obtener el valor desde el arreglo de atributos del modelo.
        $value = $this->attributes[$key]
            ?? $this->getMeta()['fields.' . $key . '.default']
            ?? null
        ;
        // Realizar casteo si corresponde.
        if ($this->hasCast($key)) {
            return $this->castForGet($key, $value);
        }
        // Entregar valor del atributo.
        return $value;
    }

    /**
     * Asigna un valor a un atributo del modelo, llamando al mutador si existe.
     *
     * @param string $key El nombre del atributo.
     * @param mixed $value El valor del atributo.
     * @return self La misma instancia del objeto para encadenamiento.
     */
    public function setAttribute(string $key, $value): self
    {
        // Sanitizar el valor del atributo si es necesario.
        if ($this->hasSanitization($key)) {
            $value = $this->sanitizeForSet($key, $value);
        }
        // Asignar con mutador (no se realiza cast automático).
        $label = $this->getAttributeLabel($key);
        $mutator = 'set' . $label . 'Attribute';
        if ($label && method_exists($this, $mutator)) {
            $this->$mutator($value);
        }
        // Asignar directamente haciendo cast si es necesario.
        else {
            if ($this->hasCast($key)) {
                $value = $this->castForSet($key, $value);
            }
            $this->attributes[$key] = $value;
        }
        // Entregar la misma instancia para encadenamiento.
        return $this;
    }

    /**
     * Obtiene los métodos de sanitización que se deben aplicar sobre el campo.
     *
     * @param string $key Llave del campo (atributo o configuración).
     * @return array|null Arreglo con los métodos de sanitización o `null`si no
     * tiene ninguno definido.
     */
    protected function getSanitization(string $key): ?array
    {
        if ($this->isConfigurationField($key)) {
            $name = $this->getConfigurationName($key);
            return $this->meta['configurations.fields.' . $name . '.sanitize'];
        } else {
            return $this->meta['fields.' . $key . '.sanitize'];
        }
    }

    /**
     * Determina si se debe sanitizar el valor del campo.
     *
     * @param string $key Llave del campo (atributo o configuración).
     * @return bool `true` si el campo tiene sanitización definida, `false`
     * en caso contrario.
     */
    protected function hasSanitization(string $key): bool
    {
        return (bool)$this->getSanitization($key);
    }

    /**
     * Sanitiza el valor de un campo al asignarlo.
     *
     * @param string $key Llave del campo (atributo o configuración).
     * @param mixed $value El valor del campo que se desea sanitizar.
     * @return mixed El valor del campo sanitizado.
     */
    protected function sanitizeForSet(string $key, $value)
    {
        if ($value === null) {
            return $value;
        }
        $sanitize = $this->getSanitization($key);
        if (!$sanitize) {
            return $value;
        }
        foreach ($sanitize as $sanitizer) {
            list($type, $parameters) = explode(':', $sanitizer . ':');
            switch ($type) {
                case 'strip_tags':
                    $value = (isset($parameters) && $parameters != '')
                        ? strip_tags($value, $parameters)
                        : strip_tags($value)
                    ;
                    break;
                case 'spaces':
                    $value = preg_replace('/\s+/', ' ', $value);
                    break;
                case 'trim':
                    $value = (isset($parameters) && $parameters != '')
                        ? trim($value, $parameters)
                        : trim($value)
                    ;
                    break;
                case 'htmlspecialchars':
                    $value = htmlspecialchars($value);
                    break;
                case 'htmlentities':
                    $value = htmlentities($value);
                    break;
                case 'addslashes':
                    $value = addslashes($value);
                    break;
                case 'urlencode':
                    $value = urlencode($value);
                    break;
                case 'rawurlencode':
                    $value = rawurlencode($value);
                    break;
                case 'intval':
                    $value = (isset($parameters) && $parameters != '')
                        ? intval($value, $parameters)
                        : intval($value)
                    ;
                    break;
                case 'floatval':
                    $value = floatval($value);
                    break;
                case 'strtolower':
                    $value = strtolower($value);
                    break;
                case 'strtoupper':
                    $value = strtoupper($value);
                    break;
                case 'filter_var':
                    $filter = $this->filters[$parameters]
                        ?? $this->filters['default']
                    ;
                    $value = filter_var($value, $filter);
                    break;
                case 'substr':
                case 'mb_substr':
                    $value = mb_substr($value, 0, $parameters);
                    break;
            }
        }
        return $value;
    }

    /**
     * Obtiene el método de casteo que se debe aplicar sobre el campo.
     *
     * @param string $key Llave del campo (atributo o configuración).
     * @return string|null Método de casteo o `null`si no tiene ninguno
     * definido.
     */
    protected function getCast(string $key): ?string
    {
        if ($this->isConfigurationField($key)) {
            $name = $this->getConfigurationName($key);
            return $this->meta['configurations.fields.' . $name . '.cast'];
        } else {
            return $this->meta['fields.' . $key . '.cast'];
        }
    }

    /**
     * Determina si existe un cast para un campo específico.
     *
     * @param string $key Llave del campo (atributo o configuración).
     * @return bool `true` si el campo tiene un cast definido, `false` en
     * caso contrario.
     */
    protected function hasCast(string $key): bool
    {
        return (bool)$this->getCast($key);
    }

    /**
     * Castea el valor de un campo al obtenerlo.
     *
     * @param string $key Llave del campo (atributo o configuración).
     * @param mixed $value El valor del campo.
     * @return mixed El valor casteado del campo.
     */
    protected function castForGet(string $key, $value)
    {
        if ($value === null) {
            return $value;
        }
        $cast = $this->getCast($key);
        if (!$cast) {
            return $value;
        }
        list($type, $parameters) = explode(':', $cast . ':');
        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return (isset($parameters) && $parameters != '')
                    ? (float) number_format($value, (int) $parameters)
                    : (float) $value
                ;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'object':
                return json_decode($value);
            case 'array':
            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;
            case 'collection':
                return collect(
                    is_string($value) ? json_decode($value, true) : $value
                );
            case 'date':
                return Carbon::parse($value)->startOfDay();
            case 'datetime':
                return Carbon::parse($value);
            case 'timestamp':
                return Carbon::createFromTimestamp($value);
            default:
                return $value;
        }
    }

    /**
     * Castea el valor de un campo al establecerlo.
     *
     * @param string $key Llave del campo (atributo o configuración).
     * @param mixed $value El valor del campo.
     * @return mixed El valor casteado del campo.
     */
    protected function castForSet(string $key, $value)
    {
        if ($value === null) {
            return $value;
        }
        $cast = $this->getCast($key);
        if (!$cast) {
            return $value;
        }
        list($type, $parameters) = explode(':', $cast . ':');
        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'object':
                return json_encode($value);
            case 'array':
            case 'json':
                return json_encode($value);
            case 'collection':
                return json_encode($value->toArray());
            case 'date':
                return Carbon::parse($value)->toDateString();
            case 'datetime':
                return Carbon::parse($value)->toDateTimeString();
            case 'timestamp':
                return Carbon::parse($value)->timestamp;
            default:
                return $value;
        }
    }

    /**
     * Determina si un campo específico usa encriptación.
     *
     * @param string $key Llave del campo (atributo o configuración).
     * @return bool `true` si el campo usa encriptación, `false` en caso
     * contrario.
     */
    protected function hasEncryption(string $key): bool
    {
        if ($this->isConfigurationField($key)) {
            $name = $this->getConfigurationName($key);
            return (bool)$this->meta['configurations.fields.' . $name . '.encrypt'];
        } else {
            return (bool)$this->meta['fields.' . $key . '.encrypt'];
        }
    }

    /**
     * Asigna el valor de un atributo del modelo usando formato de arreglo.
     *
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * Obtiene el valor de un atributo del modelo usando formato de arreglo.
     *
     * @param string $offset
     * @return void
     */
    public function offsetGet($offset)
    {
        return $this->getAttribute($offset);
    }

    /**
     * Determina si un atributo del modelo existe y tiene valor asignado.
     *
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * Elimina (o desasigna) el valor de un atributo del modelo.
     *
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        $this->setAttribute($offset, null);
    }

    /**
     * Asigna atributos al modelo, respetando las reglas de asignación masiva.
     *
     * @param array $attributes
     * @return $this
     * @throws Exception
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                if ($this->isConfigurationField($key)) {
                    $this->setConfiguration($key, $value);
                } else {
                    $this->setAttribute($key, $value);
                }
            } else {
                throw new \Exception(__(
                    'El atributo %s no es asignable masivamente.',
                    $key
                ), 422);
            }
        }
        return $this;
    }

    /**
     * Verifica si el atributo es asignable masivamente.
     *
     * @param string $key
     * @return bool
     */
    protected function isFillable(string $key): bool
    {
        // Determinar mediante configuración del campo en los metadatos.
        $isFillable = $this->meta['fields'][$key]['fillable'] ?? null;
        if ($isFillable !== null) {
            return $isFillable;
        }
        // Determinar mediante atributos del modelo $fillable y $guarded.
        $default = false;
        $fillable = $this->fillable ?? [];
        $guarded = $this->guarded ?? [];
        // Si el atributo está explícitamente permitido se puede asignar
        // masivamente.
        if (in_array($key, $fillable)) {
            return true;
        }
        // Si todos los atributos están protegidos no se puede asignar
        // masivamente.
        if (in_array('*', $guarded)) {
            return false;
        }
        // Si el atributo está explícitamente protegido no se puede asignar
        // masivamente.
        if (in_array($key, $guarded)) {
            return false;
        }
        // Si todos los atributos están permitidos se puede asignar
        // masivamente.
        if (in_array('*', $fillable)) {
            return true;
        }
        // No se pudo determinar con los atributos, se entrega el permiso por
        // defecto.
        return $default;
    }

    /**
     * Asigna atributos al modelo, forzando la asignación, o sea, sin respetar
     * las reglas de asignación masiva.
     *
     * @param array $attributes
     * @return $this
     */
    public function forceFill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isConfigurationField($key)) {
                $this->setConfiguration($key, $value);
            } else {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    /**
     * Entrega un campo traducido a su representación real.
     *
     * Se entrega un escalar o un objeto si es una relación.
     *
     * @param string $key
     * @return mixed|null
     */
    public function getField(string $key)
    {
        // Buscar configuración si existe.
        $config = $this->meta['fields.' . $key];
        if (!isset($config)) {
            return null;
        }
        // Obtener el atributo.
        $attribute = $this->getAttribute($key);
        if (!isset($attribute)) {
            return null;
        }
        // Si no es relación (llave foránea), se entrega el atributo.
        if (!isset($config['foreign_key'])) {
            return $attribute;
        }
        // Es una relación por lo que se obtiene el objeto asociado.
        try {
            return model()->instantiate(
                $config['foreign_key'],
                $this->attributes[$key] ?? null
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Entrega los atributos del modelo (campos de la tabla) como un arreglo.
     *
     * @param array $fields Campos que se requieren obtener como arreglo. Si no
     * se especifican se obtendrán todos los campos (atributos) del modelo.
     * @param array $values Valores que se deben usar para los campos. Si no se
     * especifica en $values alguno de los campos que están en $fields se
     * obtendrán los valores de los campos actuales en el modelo.
     * @param bool $resolveForeignKey Obtendrá los valores de llaves foráneas
     * con la instancia de los modelos asociados.
     * @return array Arreglo con los campos solicitados y sus valores.
     */
    public function toArray(
        array $fields = [],
        array $values = [],
        bool $resolveForeignKey = true
    ): array
    {
        if (!isset($fields[0])) {
            $fields = array_keys((array)$this->attributes);
        }
        $array = [];
        foreach ($fields as $i => $field) {
            if ($resolveForeignKey) {
                $value = $this->getField($field);
            } else {
                $value = $values[$field]
                    ?? $values[$i]
                    ?? $this->getAttribute($field)
                    ?? null
                ;
            }
            $array[$field] = $value;
        }
        return $array;
    }

    /**
     * Entrega los atributos del modelo (campos de la tabla) como una instancia
     * de stdClass.
     *
     * @return stdClass
     */
    public function toStdClass(): stdClass
    {
        return (object)$this->toArray();
    }

    /**
     * Entrega repositorio con los metadatos normalizados del modelo.
     *
     * @param array|null $meta Arreglo con los metadatos que se desean usar.
     * @return Repository Repositorio con los metadatos para fácil uso.
     */
    public function getMeta(?array $meta = []): Repository
    {
        if (!($this->meta instanceof Repository)) {
            $meta = $this->normalizeMeta($meta);
            $this->meta = new Repository($meta);
        }
        return $this->meta;
    }

    /**
     * Normaliza los metadatos.
     *
     * Este método estandariza el arreglo de metadatos para asegurar que todos
     * los valores sean consistentes y cumplan con las expectativas.
     *
     * @param array $meta Arreglo sin normalizar.
     * @return array Arreglo normalizado.
     */
    protected function normalizeMeta(array $meta): array
    {
        // Agregar índices base del modelo y los campos (atributos) del modelo.
        $meta = array_merge([
            'model' => [],
            'fields' => [],
        ], $meta);
        // Normalizar la configuración general del modelo.
        $meta['model'] = array_merge(
            $this->defaultModelConfig,
            $meta['model']
        );
        // Agregar configuración del modelo que no se definieron y son
        // obligatorias.
        if ($meta['model']['namespace'] === null) {
            $meta['model']['namespace'] = $this->getNamespace();
        }
        if ($meta['model']['singular'] === null) {
            $meta['model']['singular'] = $this->getReflector()->getName();
        }
        if ($meta['model']['plural'] === null) {
            $meta['model']['plural'] = app('inflector')->pluralize(
                $meta['model']['singular']
            );
        }
        if ($meta['model']['db_table'] === null) {
            list($aux, $singular) = explode('\Model_', $meta['model']['singular']);
            $meta['model']['db_table'] = Str::snake($singular);
        }
        if ($meta['model']['verbose_name'] === null) {
            if (!isset($singular)) {
                list($aux, $singular) = explode('\Model_', $meta['model']['singular']);
            }
            $meta['model']['verbose_name'] = $singular;
        }
        if ($meta['model']['verbose_name_plural'] === null) {
            if (!isset($singular)) {
                list($aux, $singular) = explode('\Model_', $meta['model']['singular']);
            }
            $meta['model']['verbose_name_plural'] = app('inflector')->pluralize(
                $singular
            );
        }
        if ($meta['model']['label'] === null) {
            $module = app('module')->findModuleByClass($meta['model']['singular']);
            $meta['model']['label'] = $module ?? $meta['model']['singular'];
            if (!isset($singular)) {
                list($aux, $singular) = explode('\Model_', $meta['model']['singular']);
            }
            $meta['model']['label'] .= ':' . $singular;
        }
        if ($meta['model']['label_lower'] === null) {
            $meta['model']['label_lower'] = str_replace(
                ':_',
                ':',
                str_replace('._', '.', Str::snake($meta['model']['label']))
            );
        }
        // Normalizar la configuración de cada campo del modelo.
        $pkDefined = !empty($meta['model']['primary_key']);
        foreach ($meta['fields'] as $name => &$config) {
            // Definir el tipo si no está definido.
            if (!isset($config['type'])) {
                $config['type'] = $this->defaultFieldConfig['type'];
            }
            // Definir largo mínimo y máximo si se especificó.
            if (array_key_exists('length', $config)) {
                $config['min_length'] = $config['length'];
                $config['max_length'] = $config['length'];
                unset($config['length']);
            }
            // Asignar valores por defecto.
            $defaultConfig = array_merge(
                $this->defaultFieldConfig,
                $this->defaultFieldConfigByType[$config['type']] ?? [],
            );
            $config = array_merge($defaultConfig, $config);
            // Si es llave primaria se corrigen atributos y se agrega al listado.
            if ($config['primary_key']) {
                if (!$pkDefined) {
                    $meta['model']['primary_key'][] = $name;
                }
                $config['unique'] = true;
                $config['null'] = false;
                $config['blank'] = false;
                $config['sanitize'] = array_merge(
                    $config['sanitize'] ?? [],
                    ['urlencode']
                );
            }
            // Revisión final de campos.
            $config = array_merge($config, [
                'name' => $config['name'] ?? $name,
                'label' => $config['label'] ?? ucfirst(Str::camel($name)),
                'db_column' => $config['db_column'] ?? $name,
                'cast' => $config['cast'] ?? $this->casts[$name] ?? null,
                'verbose_name' => $config['verbose_name'] ?? ucfirst(str_replace('_', ' ', $name)),
                'fillable' => $config['fillable'] ?? $this->isFillable($name),
                'required' => $config['required'] ?? !($config['null'] || $config['blank']),
            ]);
            // Agregar sanitización según configuración.
            if ($config['max_length']) {
                $config['sanitize'][] = 'substr:' . (int)$config['max_length'];
            }
            // Si es llave foránea se corrigen atributos.
            if ($config['foreign_key']) {
                $config['input_type'] = self::INPUT_SELECT;
            }
            // Corregir asignación de campos que pueden variar entre la
            // operación "create" y "edit".
            foreach ($this->variantFields as $field) {
                if (
                    isset($config[$field])
                    && (!is_array($config[$field]) || !isset($config[$field]['create']))
                ) {
                    $config[$field] = [
                        'create' => $config[$field],
                        'edit' => $config[$field],
                    ];
                }
            }
        }
        // Si no se determinó una llave primaria, se debe agregar un campo de
        // manera automática llamado "id".
        if (empty($meta['model']['primary_key'])) {
            $meta['model']['primary_key'] = ['id'];
            $meta['fields'] = array_merge([
                'id' => array_merge(
                    $this->defaultFieldConfig,
                    $this->defaultFieldConfigByType[self::TYPE_BIG_INCREMENTS],
                    [
                        'type' => self::TYPE_BIG_INCREMENTS,
                    ]
                )
            ], $meta['fields']);
        }
        // Definir forma de ordenar y buscar registros si no se ha definido.
        if (empty($meta['model']['ordering'])) {
            foreach ($meta['model']['primary_key'] as $pk) {
                $meta['model']['ordering'][] = '-' . $pk;
            }
        }
        if (empty($meta['model']['get_latest_by'])) {
            $meta['model']['get_latest_by'] = $meta['model']['ordering'];
        }
        // Definir cómo obtener los campos de las choices del modelo.
        if (
            !isset($meta['model']['choices']['id'])
            || !isset($meta['model']['choices']['name']))
        {
            $keys = array_keys($meta['fields']);
            $meta['model']['choices']['id'] =
                $meta['model']['choices']['id']
                ?? $keys[0]
                ?? null
            ;
            $meta['model']['choices']['name'] =
                $meta['model']['choices']['name']
                ?? $keys[1]
                ?? null
            ;
        }
        // Entregar metadatos normalizados.
        return $meta;
    }

    /**
     * Obtiene el espacio de nombres (namespace) asociado al modelo singular.
     *
     * @return string
     */
    public function getNamespace(): string
    {
        if (isset($this->meta['model']['namespace'])) {
            return $this->meta['model']['namespace'];
        }
        return $this->getReflector()->getNamespaceName();
    }

    /**
     * Entrega los campos que forman la PK del modelo.
     *
     * @return array Arreglo con las columnas que son la PK.
     */
    public function getPrimaryKey(): array
    {
        return $this->meta['model.primary_key'];
    }

    /**
     * Entrega un arreglo asociativo con la llave primaria del modelo.
     *
     * Se entregan en el arreglo los índices con los nombres de los campos
     * (atributos) y los valores del modelo.
     *
     * Si se pasa un $id se usará ese arreglo para armar los valores de la
     * llave primaria (y no los atributos del objeto).
     *
     * Se entregará un arreglo asociativo con:
     *   - Índice: nombre del campo de la PK.
     *   - Valor: valor de la PK para dicho campo.
     *
     * @param array $id Clave primaria del modelo.
     * @return array Arreglo con los campos y valores que forman la llave
     * primaria del modelo.
     * @throws \Exception Si no se logró definir todos los valores de la PK.
     */
    public function getPrimaryKeyValues(array $id = []): array
    {
        $fields = $this->getPrimaryKey();
        $values = $this->toArray($fields, $id);
        foreach ($values as $key => $value) {
            if ($value === null) {
                throw new \Exception(__(
                    'El campo %s debe tener un valor asignado para construir la llave primaria (PK).',
                    $key
                ));
            }
        }
        return $values;
    }

    /**
     * Entrega el arreglo con el ID del modelo.
     *
     * Es un arreglo porque los modelos pueden tener llaves compuestas, por lo
     * que el identificador del modelo puede estar compuesto de múltiples
     * valores.
     *
     * @return array
     */
    public function getId(): array
    {
        $id = $this->getIdAttribute();
        if ($id) {
            return [$id];
        }
        return array_values($this->getPrimaryKeyValues());
    }

    /**
     * Entrega los metadatos necesarios para los listados de los recursos en
     * la base de datos.
     *
     * @return array Arreglo con índices: meta, model, fields y relations.
     */
    public function getListData(): array
    {
        $data = $this->getSaveData('create');
        return $data;
    }

    /**
     * Entrega los metadatos necesarios para la creación de un nuevo recurso en
     * la base de datos.
     *
     * @return array Arreglo con índices: meta, model, fields y relations.
     */
    public function getSaveDataCreate(): array
    {
        $data = $this->getSaveData('create');
        return $data;
    }

    /**
     * Entrega los metadatos necesarios para la edición de un recurso
     * existente en la base de datos.
     *
     * @return array Arreglo con índices: meta, model, fields y relations.
     */
    public function getSaveDataEdit(): array
    {
        $data = $this->getSaveData('edit');
        foreach ($data['fields'] as $field => &$config) {
            $config['value'] = $this->getAttribute($field);
            if (!isset($config['initial_value'])) {
                $config['initial_value'] = $config['value']
                    ?? $config['default']
                ;
            }
        }
        return $data;
    }

    /**
     * Entrega los metadatos necesarios para el guardado de un recurso en la
     * base de datos según la acción que se esté realizando.
     *
     * @param string $action Acción que se está realizando: create o edit.
     * @return array Arreglo con índices: meta, model, fields y relations.
     */
    protected function getSaveData(string $action): array
    {
        $this->generateFieldsValidationRules();
        $data = $this->meta->all();
        foreach ($data['fields'] as $field => &$config) {
            foreach ($this->variantFields as $field) {
                $config[$field] = $config[$field][$action]
                    ?? $this->defaultFieldConfig[$field]
                ;
            }
        }
        $data['relations'] = $this->getRelations();
        return $data;
    }

    /**
     * Entrega las reglas de validación de campos para la creación de un nuevo
     * recurso en la base de datos.
     *
     * @return array
     */
    public function getValidationRulesCreate(string $prefix = ''): array
    {
        return $this->getValidationRules('create', $prefix);
    }

    /**
     * Entrega las reglas de validación de campos para la edición de un recurso
     * existente en la base de datos.
     *
     * @return array
     */
    public function getValidationRulesEdit(string $prefix = ''): array
    {
        return $this->getValidationRules('edit', $prefix);
    }

    /**
     * Entrega las reglas de validación de los campos de manera consolidada
     * para ser usadas al validar datos que se desean guardar en la base de
     * datos según la acción que se esté realizando.
     *
     * @param string $action Acción que se está realizando: create o edit.
     * @param string $prefix
     * @return array
     */
    protected function getValidationRules(string $action, string $prefix): array
    {
        $rules = [];
        $data = $action == 'create'
            ? $this->getSaveDataCreate()
            : $this->getSaveDataEdit()
        ;
        foreach ($data['fields'] as $field => $config) {
            $rules[$prefix . $field] = $config['validation'];
        }
        return $rules;
    }

    /**
     * Genera las reglas de validación de todos los campos del modelo.
     *
     * Las reglas solo se generan si no están ya definidas (no asignadas).
     * Si no se especificó una validación, se tratará de generar una regla en
     * base a la configuración del campo.
     *
     * @return void
     */
    protected function generateFieldsValidationRules(): void
    {
        foreach ($this->meta['fields'] as $field => $config) {
            if (!isset($config['validation'])) {
                $validation = $this->generateFieldValidationRules(
                    $config
                );
                $key = 'fields.' . $field . '.validation';
                $this->meta[$key] = $validation;
            }
        }
    }

    /**
     * Genera las reglas por defecto de un campo en base a su configuración.
     *
     * @param array $config Configuración del campo.
     * @return array Reglas de validación.
     */
    protected function generateFieldValidationRules(array $config): array
    {
        // Reglas para el tipo: string.
        if ($config['type'] == self::TYPE_STRING) {
            return $this->generateFieldValidationRulesString($config);
        }
        // Si no hay reglas que especificar se retorna vacío.
        return [];
    }

    /**
     * Genera las reglas por defecto de un campo de tipo string en base a su
     * configuración.
     *
     * @param array $config Configuración del campo.
     * @return array Reglas de validación.
     */
    protected function generateFieldValidationRulesString(array $config): array
    {
        $create = $edit = [];
        if ($config['required']['create']) {
            $create[] = 'required';
        }
        if ($config['required']['edit']) {
            $edit[] = 'required';
        }
        if ($config['min_length']) {
            $create[] = $edit[] =  'min:' . $config['min_length'];
        }
        if ($config['max_length']) {
            $create[] = $edit[] =  'max:' . $config['max_length'];
        }
        if ($config['unique']) {
            $primaryKey = $this->getPrimaryKey();
            // Regla unique cuando existe solo un campo que forma la llave
            // primaria del modelo.
            if (!isset($primaryKey[1])) {
                $unique = 'unique:'
                    . $this->meta['model.db_table']
                    . ',' . $config['db_column']
                ;
                $create[] = $unique;
                $edit[] = $unique
                    . ',' . $this->getAttribute($config['name'])
                    . ',' . $primaryKey[0]
                ;
            }
            // Regla unique cuando existen múltiples campos que forman la llave
            // primaria del modelo.
            else {
                $unique = [
                    $config['name'] => $this->getAttribute($config['name']),
                ];
                $ignore = [];
                foreach ($primaryKey as $field) {
                    $ignore[$field] = $this->getAttribute($field);
                }
                $create[] = new Data_Validation_UniqueComposite(
                    $this->meta['model.db_name'],
                    $this->meta['model.db_table'],
                    $unique
                );
                $edit[] = new Data_Validation_UniqueComposite(
                    $this->meta['model.db_name'],
                    $this->meta['model.db_table'],
                    $unique,
                    $ignore
                );
            }
        }
        if ($config['choices']) {
            $create[] = $edit[] = 'in:' . implode(',', $config['choices']);
        }
        return compact('create', 'edit');
    }

    /**
     * Método que obtiene las relaciones del modelo con otros para la
     * asignación de relaciones de llave foránea.
     *
     * @return array
     */
    protected function getRelations(): array
    {
        $relations = [];
        foreach ($this->meta['fields'] as $field => $config) {
            if (!isset($config['foreign_key'])) {
                continue;
            }
            $foreignKeyInstance = model()->instantiate($config['foreign_key']);
            if (!isset($foreignKeyInstance)) {
                continue;
            }
            $relations[$config['foreign_key']] = [
                'choices_fields' => $foreignKeyInstance->getMeta()['model.choices'],
                'choices' => $foreignKeyInstance
                    ->getPluralInstance()
                    ->choices(['filters' => $config['limit_choices_to']])
                ,
            ];
        }
        return $relations;
    }

    /**
     * Verifica si el modelo existe en la base de datos.
     *
     * @return bool Verdadero si el modelo existe, falso en caso contrario.
     */
    public function exists(): bool
    {
        if (!isset($this->exists)) {
            $primaryKeyValues = $this->getPrimaryKeyValues();
            $query = $this->getPluralInstance()->query();
            $this->exists = $query->where($primaryKeyValues)->exists();
        }
        return $this->exists;
    }

    /**
     * Determina si el modelo o alguno de sus atributos ha cambiado.
     *
     * @param string|array|null $attributes Atributo(s) específico(s) a
     * comprobar, o nulo para comprobar todos.
     * @return bool Verdadero si algún atributo ha cambiado, falso en caso
     * contrario.
     */
    public function isDirty($attributes = null): bool
    {
        // TODO: Implementar. Por ahora se asumen siempre modificados y se
        // actualizarán siempre en la base de datos cuando se necesite guardar
        // y el registro ya exista.
        return true;
    }

    /**
     * Guarda el modelo en la base de datos.
     *
     * @param array $options Opciones adicionales para guardar.
     * @return bool Verdadero si el modelo se guardó correctamente, falso en
     * caso contrario.
     */
    public function save(array $options = []): bool
    {
        // Por defecto se asume como no guardado el registro.
        $saved = false;
        // Determinar si el modelo ya existe en la base de datos.
        if ($this->exists()) {
            // Si el modelo existe, realiza una actualización.
            $saved = $this->isDirty() ? $this->update($options) : true;
        } else {
            // Si el modelo no existe, realiza una inserción.
            $saved = $this->insert($options);
        }
        // Si se guardó correctamente se establece que el modelo existe y, si
        // es necesario, se recupera el registro guardado en la base de datos
        // para actualizar los atributos de la instancia.
        if ($saved) {
            $this->exists = true;
            if ($this->meta['model.select_on_save']) {
                $this->retrieve($this->getId());
            }
        }
        // Entregar el resultado del guardado.
        return $saved;
    }

    /**
     * Inserta el modelo en la base de datos.
     *
     * @param array $options Opciones adicionales para insertar.
     * @return bool Verdadero si el modelo se insertó correctamente, falso en
     * caso contrario.
     */
    protected function insert(array $options): bool
    {
        $query = $this->getPluralInstance()->query();
        $inserted = $query->insert($this->attributes);
        if ($inserted) {
            $this->exists = true;
            return true;
        }
        return false;
    }

    /**
     * Actualiza el modelo en la base de datos.
     *
     * @param array $options Opciones adicionales para actualizar.
     * @return bool Verdadero si el modelo se actualizó correctamente, falso en
     * caso contrario.
     */
    protected function update(array $options): bool
    {
        $primaryKeyValues = $this->getPrimaryKeyValues();
        $query = $this->getPluralInstance()->query();
        $updated = $query->where($primaryKeyValues)->update($this->attributes);
        return $updated > 0;
    }

    /**
     * Elimina el modelo de la base de datos.
     *
     * @return bool Verdadero si el modelo se eliminó correctamente, false en
     * caso de error.
     */
    public function delete(): bool
    {
        $primaryKeyValues = $this->getPrimaryKeyValues();
        $query = $this->getPluralInstance()->query();
        $deleted = $query->where($primaryKeyValues)->delete();
        if ($deleted > 0) {
            $this->exists = false;
            return true;
        }
        return false;
    }

    /**
     * Define una relación "pertenece a" (belongs to) con otro modelo.
     *
     * @param string $class El nombre de la clase del modelo relacionado.
     * @return Model|null El modelo relacionado o null si no se encuentra.
     */
    protected function belongsTo(string $class): ?Model
    {
        // TODO: Implementar lógica para recuperar la relación "pertenece a"
        // (belongs to). Aquí se deberá buscar el modelo relacionado usando la
        // clave foránea almacenada en el modelo actual y devolver la instancia
        // del modelo relacionado.
        return null;
    }

    /**
     * Define una relación "tiene muchos" (has many) con otro modelo.
     *
     * @param string $class El nombre de la clase del modelo relacionado.
     * @return array Una lista de instancias del modelo relacionado.
     */
    protected function hasMany(string $class): array
    {
        // TODO: Implementar lógica para recuperar la relación "tiene muchos"
        // (has many). Aquí se deberá buscar todas las instancias del modelo
        // relacionado que tienen una clave foránea apuntando al modelo actual
        // y devolver una lista de instancias de esos modelos relacionados.
        return [];
    }

    /**
     * Define una relación "pertenece a muchos" (belongs to many) con otro modelo.
     *
     * @param string $class El nombre de la clase del modelo relacionado.
     * @return array Una lista de instancias del modelo relacionado.
     */
    protected function belongsToMany(string $class): array
    {
        // TODO: Implementar lógica para recuperar la relación "pertenece a
        // muchos" (belongs to many). Aquí se deberá buscar todas las
        // instancias del modelo relacionado que están asociadas al modelo
        // actual a través de una tabla intermedia y devolver una lista de
        // instancias de esos modelos relacionados.
        return [];
    }

    /**
     * Procesa las llamadas a métodos que no existen en la clase.
     *
     * Específicamente procesa las llamadas a los "accessors" que permiten
     * obtener relaciones. También entrega escalares, pero, principalmente, es
     * útil para las relaciones.
     *
     * NOTE: Debería ser reemplazado al implementar y usar correctamente:
     * belongsTo(), hasMany() y belongsToMany().
     */
    public function __call(string $method, $args)
    {
        // Si es un "accessor" getXyzField() se procesa.
        $pattern = '/^get([A-Z][a-zA-Z]*)Field$/';
        if (preg_match($pattern, $method, $matches)) {
            $field = Str::snake($matches[1]);
            if (isset($this->meta['fields.' . $field])) {
                return $this->getField($field);
            }
        }
        // Si es un "accessor" getXyz() se procesa como getXyzField().
        // NOTE: esto debería ser temporal ya que la opción sin "Field" está
        // obsoleta y podría ser removida en el futuro. Se recomienda usar
        // los "accessors" con el sufijo Field siempre.
        $pattern = '/^get([A-Z][a-zA-Z]*)$/';
        if (preg_match($pattern, $method, $matches)) {
            return $this->__call($method . 'Field', $args);
        }
        // Si el método no existe se genera una excepción.
        throw new \Exception(__(
            'Método %s::%s() no existe.',
            get_class($this),
            $method
        ));
    }

    /**
     * Entrega el atributo con el arreglo de configuraciones del modelo.
     *
     * Es un accessor para el atributo Model::$config.
     *
     * @return void
     */
    public function getConfigAttribute(): Repository
    {
        if (!isset($this->configurations)) {
            $configurations = $this->loadConfigurations();
            $this->configurations = new Repository();
            foreach ($configurations as $category => $keys) {
                foreach ($keys as $key => $value) {
                    $attribute = 'config_' . $category . '_' . $key;
                    $this->setConfiguration($attribute, $value);
                }
            }
        }
        return $this->configurations;
    }

    /**
     * Entrega los metadatos del modelo de configuraciones asociado al modelo.
     *
     * @return array Arreglo con los metadatos del modelo de configuraciones.
     */
    protected function getConfigurationsMeta(): array
    {
        $modelDbTable = $this->getMeta()['model.db_table'];
        $configModelDefaultMeta = [
            'db_table' => $modelDbTable . '_config',
            'primary_key' => [
                $modelDbTable => 'id',
            ],
            'fields' => [
                'category' => 'configuracion',
                'key' => 'variable',
                'value' => 'valor',
                'is_json' => 'json',
            ],
        ];
        $configModelMeta = array_merge(
            $configModelDefaultMeta,
            $this->getMeta()['configurations.model'] ?? []
        );
        return $configModelMeta;
    }

    /**
     * Obtiene las configuraciones desde la base de datos y las entrega en un
     * arreglo estandarizado.
     *
     * @return array
     */
    protected function loadConfigurations(): array
    {
        // Obtener metadatos del modelo de consiguración.
        $configModelMeta = $this->getConfigurationsMeta();
        $query = $this->getPluralInstance()
            ->getDatabaseConnection()
            ->table($configModelMeta['db_table'])
        ;
        // Asignar la llave primaria del modelo de configuración y determinar
        // si están los datos necesarios para ser consultado.
        $canBeQueried = true;
        foreach ($configModelMeta['primary_key'] as $configModelField => $modelField) {
            $modelValue = $this->getAttribute($modelField);
            if ($modelValue === null) {
                $canBeQueried = false;
                break;
            }
            $query->where($configModelField, '=', $modelValue);
        }
        if (!$canBeQueried) {
            return [];
        }
        // Realizar consulta para obtener las configuraciones dle modelo desde
        // la base de datos.
        $configCols = array_values($configModelMeta['fields']);
        $results = $query->get($configCols);
        foreach ($results as $row) {
            // Obtener valores desde la base de datos.
            $category = $row->{$configModelMeta['fields']['category']};
            $key = $row->{$configModelMeta['fields']['key']};
            $value = $row->{$configModelMeta['fields']['value']};
            $is_json = $row->{$configModelMeta['fields']['is_json']};
            // Desencriptar y decodificar JSON si es necesario.
            $attribute = 'config_' . $category . '_' . $key;
            if ($this->hasEncryption($attribute)) {
                $value = decrypt($value);
            }
            if ($is_json) {
                $value = json_decode($value);
            }
            // Asignar valor a la configuración.
            $configurations[$category][$key] = $value;
        }
        // Entregar las configuraciones obtenidas desde la base de datos.
        return $configurations;
    }

    protected function saveConfigurations(): bool
    {
        /*if ($this->config) {
            $app_pkey = config('app.key');
            foreach ($this->config as $configuracion => $datos) {
                foreach ($datos as $variable => $valor) {
                    $Config = new Model_UsuarioConfig(
                        $this->id,
                        $configuracion,
                        $variable
                    );
                    if (!is_array($valor) && !is_object($valor)) {
                        $Config->json = 0;
                    } else {
                        $valor = json_encode($valor);
                        $Config->json = 1;
                    }
                    $class = get_called_class();
                    if (
                        in_array($configuracion . '_' . $variable, $class::$config_encrypt)
                        && $valor !== null
                    ) {
                        if (!$app_pkey) {
                            throw new \Exception(
                                'No está definida la configuración app.pkey para encriptar configuración del usuario.'
                            );
                        }
                        $valor = encrypt($valor);
                    }
                    $Config->valor = $valor;
                    if ($valor !== null) {
                        $Config->save();
                    } else {
                        $Config->delete();
                    }
                }
            }
        }*/
        return true;
    }

    /**
     * Indica si la llave de un campo es un campo de configuración o no.
     *
     * @param string $key Llave del campo que se desea revisar.
     * @return boolean `true` si el campo es de configuración.
     */
    protected function isConfigurationField(string $key): bool
    {
        return strpos($key, 'config_') === 0;
    }

    /**
     * Entrega el nombre de la configuración a partir del nombre completo del
     * atributo del objeto.
     *
     * @param string $attribute Atributo del objeto que generará el nombre.
     * @return string El nombre de la configuración (atributo sin prefijo).
     */
    protected function getConfigurationName(string $attribute): string
    {
        if (!$this->isConfigurationField($attribute)) {
            throw new \Exception(__(
                'El atributo %s no es de configuración',
                $attribute
            ));
        }
        return substr($attribute, 7);
    }

    /**
     * Entrega la llave del atributo de configuración en el repositorio de
     * configuraciones.
     *
     * Se permiten 2 formatos:
     *
     *   - Nuevo: usa como separador de `category` y `key` dos guiones bajos
     *     "__". Esto permite que el nombre de la categoría tenga guiones
     *     bajos.
     *
     *   - Antiguo: usa como separador de `category` y `key` solo un guión bajo
     *     "_". Esto significa que el nombre de la categoría no puede tener
     *     guiones bajos.
     *
     * Ambos formatos, para un caso con `category` sin guiones bajos son 100%
     * compatibles. O sea, tanto `config_page_layout` como
     * `config_page__layout` hacen referencia a la misma configuración. Por lo
     * que se recomienda migrar las configuraciones al formato nuevo con "__" y
     * se debe considerar el formato con "_" obsoleto.
     *
     * @param string $attribute Atributo del objeto que generará la llave.
     * @return string Llave de la configuración en el repositorio.
     */
    protected function getConfigurationKey(string $attribute): string
    {
        $name = $this->getConfigurationName($attribute);
        if (strpos($name, '__') === false) {
            $count = 1;
            return str_replace('_', '.', $name, $count);
        }
        return str_replace('__', '.', $name);
    }

    /**
     * Entrega el label de un campo de configuración.
     *
     * @param string $attribute Atributo del objeto que generará el label.
     * @return string Label del atributo (desde configuración o generado).
     */
    protected function getConfigurationLabel(string $attribute): string
    {
        $name = $this->getConfigurationName($attribute);
        $label = $this->meta['configurations.fields.' . $name . '.label'];
        if (strpos($label, ':')) {
            list($module, $label) = explode(':', $label);
        }
        if ($label === null) {
            $label = ucfirst(Str::camel($name));
        }
        return $label;
    }

    /**
     * Obtiene el valor de una configuración.
     *
     * Prefijos soportados:
     *
     *   - `config_`.
     *
     * @param string $attribute Atributo del objeto que se desea obtener.
     * @return mixed|null El valor de la configuración o null si no existe.
     */
    protected function getConfiguration(string $attribute)
    {
        // Obtener con accessor (no se realiza cast automático).
        $label = $this->getConfigurationLabel($attribute);
        $accessor = 'get' . $label . 'Configuration';
        if ($label && method_exists($this, $accessor)) {
            return $this->$accessor();
        }
        // Determinar llave y obtener el valor de la configuración desde el
        // repositorio, si existe.
        $key = $this->getConfigurationKey($attribute);
        $value = $this->config[$key];
        // Desencriptar si corresponde.
        if ($this->hasEncryption($attribute)) {
            $value = decrypt($value);
        }
        // Realizar casteo si corresponde.
        if ($this->hasCast($attribute)) {
            $value = $this->castForGet($attribute, $value);
        }
        // Buscar valor por defecto si no existe valor encontrado.
        if ($value === null) {
            $name = $this->getConfigurationName($attribute);
            $value = $this->meta['configurations.fields.' . $name . '.default'] ?? null;
        }
        // Entregar valor de la configuración.
        return $value;
    }

    /**
     * Asigna el valor de una configuración.
     *
     * Prefijos soportados:
     *
     *   - `config_`.
     *
     * @param string $attribute Atributo del objeto que se desea obtener.
     * @param mixed $value El valor de la configuración.
     * @return self La misma instancia del objeto para encadenamiento.
     */
    protected function setConfiguration(string $attribute, $value): self
    {
        // Sanitizar el valor del atributo si es necesario.
        if ($this->hasSanitization($attribute)) {
            $value = $this->sanitizeForSet($attribute, $value);
        }
        // Asignar con mutador (no se realiza cast automático).
        $label = $this->getConfigurationLabel($attribute);
        $mutator = 'set' . $label . 'Configuration';
        if ($label && method_exists($this, $mutator)) {
            $this->$mutator($value);
        }
        // Asignar directamente haciendo cast si es necesario.
        else {
            if ($this->hasCast($attribute)) {
                $value = $this->castForSet($attribute, $value);
            } else {
                $value = $this->castConfigurationForSet($value);
            }
            // Encriptar si corresponde.
            if ($this->hasEncryption($attribute)) {
                $value = encrypt($value);
            }
            // Determinar llave y asignar el valor en la configuración mediante
            // el repositorio,
            $key = $this->getConfigurationKey($attribute);
            $this->configurations[$key] = $value;
        }
        // Entregar la misma instancia para encadenamiento.
        return $this;
    }

    /**
     * Entrega el casteo "automático" para asigar configuraciones.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function castConfigurationForSet($value)
    {
        $value = ($value === false || $value === 0)
            ? '0'
            : (
                (!is_array($value) && !is_object($value))
                    ? (string)$value
                    : (
                        (is_array($value) && empty($value))
                            ? null
                            : $value
                    )
            )
        ;
        return (!is_string($value) || isset($value[0])) ? $value : null;
    }

    /*
    |--------------------------------------------------------------------------
    | DESDE AQUÍ HACIA ABAJO ESTÁ OBSOLETO Y DEBE SER REFACTORIZADO Y ELIMINAR.
    |--------------------------------------------------------------------------
    */

    /**
     * Información de las columnas de la base de datos.
     *
     * @var array
     * @deprecated Se debe refactorizar el código que use este atributo para
     * usar Model::getMeta() que es el formato actual de metadatos del modelo.
     */
    public static $columnsInfo;

    /**
     * Entrega la información de las columnas de un modelo en el formato
     * antiguo.
     *
     * @return array Arreglo con la información de las columnas del modelo.
     * @deprecated Se debe refactorizar el código que use este método para
     * usar Model::getMeta() que es el formato actual de metadatos del modelo.
     */
    public function getColumnsInfo(): array
    {
        // Formato antiguo de información de columnas.
        if (isset(self::$columnsInfo)) {
            return self::$columnsInfo;
        }
        // Crear la información de las columnas a partir de los metadatos.
        $columnsInfo = [];
        foreach ($this->getMeta()['fields'] as $name => $config) {
            $columnsInfo[$name] = [
                'name'      =>  $config['verbose_name'],
                'comment'   =>  $config['help_text'],
                'type'      =>  $config['type'],
                'length'    =>  $config['max_length'],
                'null'      =>  $config['null'],
                'default'   =>  $config['default'],
                'auto'      =>  $config['auto'],
                'pk'        =>  $config['primary_key'],
                'fk'        =>  [
                                    'table' => $config['to_table'],
                                    'column'=> $config['to_field']
                                ]
                ,
            ];
        }
        return $columnsInfo;
    }

    /**
     * Asignación de los datos de un archivo para ser almacenado en la base de
     * datos.
     * @deprecated Guardar archivos en el storage o buscar otra solución.
     */
    public function setFile(string $name, array $file): void
    {
        if (!isset($file['data'])) {
            $file['data'] = fread(
                fopen($file['tmp_name'], 'rb'),
                filesize($file['tmp_name'])
            );
        }
        $this->{$name . '_name'} = $file['name'];
        $this->{$name . '_type'} = $file['type'];
        $this->{$name . '_size'} = $file['size'];
        $this->{$name . '_data'} = $file['data'];
    }

}
