Instalación
===========

Este documento describe los requerimientos, instalación y puesta en marcha del
framework.

Requerimientos
--------------

El framework asume el siguiente software instalado:

*	Servidor web:
	*	[Apache 2.x](http://httpd.apache.org)
	*	[PHP 5.5](http://www.php.net/downloads.php)

*	Herramientas para repositorios:
	*	[Composer](https://getcomposer.org/download)
	*	[Git](http://git-scm.com/download)
	*	[Mercurial](http://mercurial.selenic.com/wiki/Download)

*	Bases de datos (opcionales):
	*	[PostgreSQL](http://www.postgresql.org/download)
		(versión recomendada: >=9.1)
	*	[MariaDB](https://downloads.mariadb.org)

### Instalación requerimientos

1.      Instalación de paquetes mínimos:

                # apt-get install git apache2-mpm-prefork php5 php-pear php5-gd mercurial curl php5-curl php5-imap

        En estricto rigor php-pear y php5-gd también son opcionales, solo son
        requeridos para la instalación de otras librerías (en el caso de
        php-pear para instalar Net_SMTP y php5-gd para generar los gráficos
        mediante libchart).

2.      Instalación de soporte para PostgreSQL (opcional):

                # apt-get install php5-pgsql postgresql

### Configuración de Apache

Habilitación de módulos

        # a2enmod rewrite
        # a2enmod ssl
        # a2enmod php5
        # service apache2 restart

#### Easy Virtual Hosts (EasyVHosts)

Se hace uso de esta herramienta para una configuración más simple y rápida
del servidor web. Descargar desde: <https://github.com/sascocl/easyvhosts>

Configurar archivo etc/easyvhosts/easyvhosts.conf de acuerdo a las necesidades.

Ejecutar con:

        # bin/easyvhosts

### Configuración de PHP

Instalar bibliotecas para envío de correo electrónico:

        # pear install Mail Mail_mime Net_SMTP

### Configuración de PostgreSQL

Crear usuario para la BD:

        # su - postgres
        $ createuser --createdb --no-createrole --no-superuser --password <usuario>

En caso que se quiera cambiar la clave de un usuario:

        $ psql -d template1 -U postgres
        $ alter user <usuario> with password '<clave>';

**Nota**: si la asignación de la clave al momento de crear el usuario no
funciona, probar cambiando la clave luego de haberlo creado.

Crear base de datos (con el usuario asociado al que se creo):

        $ createdb <base de datos>

Probar conexión (con el usuario asociado al que se creo):

        $ psql -h 127.0.0.1 -U <usuario> -W <base de datos>

Instalación del framework
-------------------------

Se recomienda realizar instalación del framework utilizando
[SowerPKG](https://github.com/SowerPHP/sowerpkg)

Configuración del framework
---------------------------

1. Definir parámetros de configuración por defecto en el archivo *Config/core.php*.

2. Revisar archivos de configuración en directorio *standard* (o extensiones que
se hayan instalado) para ver que opciones de configuración existen y sus
valores por defecto. Si no se define ninguno se utilizarán dichos valores.

3. Notar que si se cambia el Layout, este es asignado mediante la sesión por lo
cual la sesión debe ser destruída para que el cambio surta efecto (por ejemplo
borrando las cookies para el sitio en el navegador). La otra alternativa es
cambiar el layout ingresando a la url:

	example.com/session/config/page.layout/NuevoLayout

Crear Hola Mundo
----------------

1.      Crear directorio View/Pages dentro de *project/website*.

                $ mkdir -p View/Pages

2.      Crear archivo View/Pages/inicio.php con el siguiente contenido:

                <h1>Hola mundo</h1>
                <p>Ejemplo de Hola Mundo</p>

        También se puede haber creado un archivo View/Pages/inicio.md y utilizar
        la sintaxis de Markdown para el contenido:

                Hola mundo
                ==========

                Ejemplo de Hola Mundo

        Por defecto se procesan archivos .php y .md como vistas.

3.      Abrir página http://example.com o http://example.com/inicio
