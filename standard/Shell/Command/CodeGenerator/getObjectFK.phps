	/**
	 * Recupera un objeto de tipo {fk_class} asociado al objeto {class}
	 * @return {fk_class} Objeto de tipo {fk_class} con datos seteados o null en caso de que no existe la asociación
	 * @author {author}
	 * @version {version}
	 */
	public function get{fk_name} () {
		App::uses('{fk_class}', {class}::$fkModule['{fk_class}'].'Model');
		${fk_class} = new {fk_class}({pk});
		if(${fk_class}->exists()) {
			return ${fk_class};
		}
		return null;
	}
