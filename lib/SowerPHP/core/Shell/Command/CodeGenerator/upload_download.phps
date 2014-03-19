	/**
	 * Método para subir todos los archivos desde un formulario
	 * @author {author}
	 * @version {version}
	 */
	protected function u ({pk_parameter}) {
		App::uses('File', 'Utility');
		${class} = new {class}({pk_parameter});
		$files = array({files});
		foreach($files as &$file) {
			if(isset($_FILES[$file]) && !$_FILES[$file]['error']) {
				$archivo = File::upload($_FILES[$file]);
				if(is_array($archivo)) {
					${class}->saveFile($file, $archivo);
				}
			}
		}
	}

	/**
	 * Método para descargar un archivo desde la base de datos
	 * @author {author}
	 * @version {version}
	 */
	public function d ($campo, {pk_parameter}) {
		${class} = new {class}({pk_parameter});
		$this->response->sendFile(array(
			'name' => ${class}->{$campo.'_name'},
			'type' => ${class}->{$campo.'_type'},
			'size' => ${class}->{$campo.'_size'},
			'data' => pg_unescape_bytea(${class}->{$campo.'_data'}),
		));
	}
