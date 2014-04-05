    /**
     * Recupera un objeto de tipo {fk_class} asociado al objeto {class}
     * @return {fk_class} Objeto de tipo {fk_class} con datos seteados o null en caso de que no existe la asociación
     * @author {author}
     * @version {version}
     */
    public function get{fk_name} ()
    {
        $class = \sowerphp\core\App::findClass('Model_{class}', '{module}');
        $fkClass = $class::$fkNamespace['Model_{fk_class}'].'\Model_{fk_class}';
        ${fk_class} = new $fkClass({pk});
        if (${fk_class}->exists()) {
            return ${fk_class};
        }
        return null;
    }
