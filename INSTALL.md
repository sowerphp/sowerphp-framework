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

Puede consultar la información sobre la
[configuración del servidor web](http://sowerphp.org/doc/general/servidor_web)
para más detalles.

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

Revisar documentación para creación de
[Hola Mundo](http://sowerphp.org/doc/paso_a_paso/hola_mundo)
