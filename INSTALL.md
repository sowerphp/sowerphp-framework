Instalación
===========

Este documento describe los requerimientos, instalación y puesta en marcha del
framework.

El framework está desarrollado y probado en GNU/Linux :-)

Requerimientos
--------------

El framework asume el siguiente software instalado:

*	Servidor web:
	* [Debian GNU/Linux](https://www.debian.org)
	* [Apache 2.x](http://httpd.apache.org)
	* [PHP 5.5](http://www.php.net/downloads.php) (se recomienda PHP 7)

*	Herramientas para repositorios:
	* [Composer](https://getcomposer.org/download)
	* [Git](http://git-scm.com/download)
	* [Mercurial](http://mercurial.selenic.com/wiki/Download)

*	Bases de datos (opcionales):
	* [PostgreSQL](http://www.postgresql.org/download) (versión recomendada: >=9.1)
	* [MariaDB](https://downloads.mariadb.org)

### Instalación requerimientos

1.	Instalación de paquetes mínimos:

		# apt-get install git apache2-mpm-prefork php5 php-pear php5-gd mercurial curl php5-curl php5-imap

	En estricto rigor php-pear y php5-gd también son opcionales, solo son
	requeridos para la instalación de otras librerías (en el caso de
	php-pear para instalar Net_SMTP y php5-gd para generar los gráficos
	mediante libchart).

2.	Instalación de soporte para PostgreSQL (opcional):

		# apt-get install php5-pgsql postgresql

### Configuración de Apache

Habilitación de módulos:

	# a2enmod rewrite ssl php5
	# service apache2 restart

Activar "AllowOverride All" para el dominio que se esté usando.

#### Easy Virtual Hosts (EasyVHosts)

Se hace uso de esta herramienta para una configuración más simple y rápida
del servidor web. Descargar desde: <https://github.com/sascocl/easyvhosts>

Configurar archivo etc/easyvhosts/easyvhosts.conf de acuerdo a las necesidades.

Ejecutar con:

	# bin/easyvhosts

No es obligatorio usarlo, pero es recomendable cuando se tienen varios dominios
virtuales en la misma máquina.

### Configuración de PHP

Instalar bibliotecas para envío de correo electrónico:

	# pear install Mail Mail_mime Net_SMTP

### Configuración de PostgreSQL

Se creará un usuario para la base de datos con el mismo nombre del usuario real
del sistema operativo, sin embargo eso no es obligatorio.

Para crear el usuario para la base de datos y asignar su contraseña:

	# su - postgres
	$ createuser --createdb --no-createrole --no-superuser <usuario>
	$ psql -d template1 -U postgres <<EOF
		ALTER USER <usuario> WITH PASSWORD '<contraseña>';
	EOF

Conectarse con el usuario del sistema operativo y crear la base de datos:

	$ createdb <base de datos>

Probar conexión, desde la cuenta del usuario del sistema operativo:

	$ psql <base de datos>

Desde cualquier cuenta:

	$ psql -h 127.0.0.1 -U <usuario> -W <base de datos>

Instalación del framework usando SowerPKG
-----------------------------------------

Se recomienda realizar instalación del framework utilizando
[SowerPKG](https://github.com/SowerPHP/sowerpkg).

Es posible instalar todo manualmente (framework, extensiones y dependencias de
composer) pero son varios pasos y SowerPKG los ejecuta todos automáticamente.

Configuración del framework
---------------------------

1.	Definir parámetros de configuración por defecto en el archivo *Config/core.php*.

2.	Revisar archivos de configuración en directorio *project* (o extensiones que
se hayan instalado) para ver que opciones de configuración existentes y sus
valores por defecto. Si no se define ninguno se utilizarán dichos valores.

3.	Notar que si se cambia el Layout, este es asignado mediante la sesión
	por lo cual la sesión debe ser destruída para que el cambio surta efecto
	(por ejemplo borrando las cookies para el sitio en el navegador).

Crear Hola Mundo
----------------

1.	Crear directorio View/Pages dentro de *project/website*.

		$ mkdir -p View/Pages

2.	Crear archivo View/Pages/inicio.php con el siguiente contenido:

		<h1>Hola mundo</h1>
		<p>Ejemplo de Hola Mundo</p>

	También se puede haber creado un archivo View/Pages/inicio.md y utilizar
	la sintaxis de Markdown para el contenido:

		Hola mundo
		==========

		Ejemplo de Hola Mundo

	Por defecto se procesan archivos .php y .md como vistas.

3.	Abrir página http://example.com o http://example.com/inicio
