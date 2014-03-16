Instalación
===========

Este documento describe los requerimientos, instalación y puesta en marcha del
framework.

Requerimientos
--------------

El framework asume el siguiente software instalado:

*	Servidor web:
	*	[Apache 2.x](http://httpd.apache.org)
	*	[PHP 5.3](http://www.php.net/downloads.php) o superior
		(recomendado PHP 5.5 para usar OPCache)

*	Herramientas para repositorios:
	*	[Composer](https://getcomposer.org/download)
	*	[Git](http://git-scm.com/download)
	*	[Mercurial](http://mercurial.selenic.com/wiki/Download)

*	Bases de datos (opcionales):
	*	[PostgreSQL](http://www.postgresql.org/download)
		(versión recomendada: >=9.1)
	*	[MariaDB](https://downloads.mariadb.org)

Instalación del sistema estándar
--------------------------------

Instalar framework utilizando composer:

	$ composer create-project sowerphp/sowerphp sower --stability="dev"

*	*sower* (después del nombre del paquete) es el directorio de
	instalación. Para una instalación compartida podría ser:

		/usr/share/sowerphp

	o

		C:\sowerphp

*	Actualmente solo está disponible la versión de desarrollo, por lo cual
	utilizar el argumento *--stability* es obligatorio.

**Nota**: si trabajas en un ambiente compartido, debes copiar el directorio
*project* desde el directorio de SowerPHP a un directorio no compartido. Luego
se debe editar el archivo *website/webroot/index.php* indicando la ruta de
instalación del framework en la constante DIR_FRAMEWORK.

Una vez instalado el framework, se debe verificar que pueda ser accedido
mediante un navegador, por ejemplo si se instaló en el directorio raíz de
Apache estará disponible en la dirección

	example.com/sowerphp

**Nota**: *example.com* es el dominio de ejemplo, se debe usar el propio o bien
*localhost*.

Páginas en formato Markdown (.md)
---------------------------------

El sistema base permite el uso de páginas en formato Markdown, si se desea
utilizar se debe instalar el soporte usando composer. Ingresar al directorio
de SowerPHP y ejecutar:

	$ composer install

Envío de corres electrónicos
----------------------------

Para enviar correo electrónico (con la clase Email del sistema estándar) se
requieren ciertos paquetes de PEAR, se deben instalar con:

	# pear install Mail Mail_mime Net_SMTP

Instalación de extensiones
--------------------------

Se debe ir al directorio donde se quieran instalar las extensiones:

*	Para un instalación global se debe ir al directorio de SowerPHP.

*	Para una instalación por proyecto se debe ir al directorio del
	proyecto.

Una vez en el directorio se instalarán las extensiones utilizando composer.
Por ejemplo para instalar la extensión *sower/layouts* utilizar:

	$ composer require sowerphp/layouts dev-master

*	Actualmente solo están disponibles (en el caso de extensiones
	oficiales) las versiones de desarrollo, por lo cual utilizar la versión
	*dev-master* es obligatorio. De todas formas se recomienda revisar cada
	paquete que se desee instalar buscando la última versión antes de
	proceder con la instalación.

Una vez instalada la extensión se debe agregar en el archivo
*website/webroot/index.php* para que sea cargada:

	$_EXTENSIONS = array('layouts');

Lo anterior es para extensiones oficiales, en caso de ser extensiones de
terceros se debe indicar el *vendor*, ejemplo:

	$_EXTENSIONS = array('vendor/extension');

**Importante**: en caso de existir extensiones que dependan de otras
bibliotecas, como la extensión *general*, no podrán ser instaladas usando
composer. En este caso de recomienda clonar o descargar directamente la
extensión y dejarla en el directorio de extensiones, debería quedar así:

	extensios/sowerphp/general

Una vez se copió a la ubicación correcta la extensión, se deberán instalar,
usando composer, las otras bibliotecas externas.

Configuración básica
--------------------

Definir parámetros de configuración por defecto en el archivo *Config/core.php*.

Revisar archivos de configuración en directorio *standard* (o extensiones que
se hayan instalado) para ver que opciones de configuración existen y sus
valores por defecto. Si no se define ninguno se utilizarán dichos valores.

Notar que si se cambia el Layout, este es asignado mediante la sesión por lo
cual la sesión debe ser destruída para que el cambio surta efecto (por ejemplo
borrando las cookies para el sitio en el navegador). La otra alternativa es
cambiar el layout ingresando a la url:

	example.com/session/config/page.layout/NuevoLayout

Enlaces de interés
------------------

*	Packagist: <https://packagist.org/packages/sowerphp>
