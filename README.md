SowerPHP
========

SowerPHP es un framework, o ambiente de desarrollo, para PHP desarrollado en
Chile.

Este framework es el resultado de la evolución de diversos proyectos
[del autor](https://github.com/estebandelaf) que dieron finalmente forma a este
proyecto. Algunos de esos proyectos, hoy obsoletos, fueron
[MiPaGiNa](https://github.com/estebandelaf/mipagina) y MiFrAmEwOrK.

El framework entrega las herramientas básicas para el desarrollo. Además existen
extensiones oficiales que entregan nuevas funcionalidades y cualquier usuario
puede desarrollar sus propias extensiones.

El objetivo principal del framework es entregar una estructura estándar y mínima
para la creación de una aplicación en PHP. Sin embargo, no considera todos los
casos, ni mucho menos pretende competir con otros framworks de PHP que cubren
más aspectos que SowerPHP.

El principal proyecto desarrollado con este framework es
[la Aplicación Web de LibreDTE](https://github.com/LibreDTE/libredte-webapp).
Gracias a dicho proyecto es que este framework ha sido mejorado con los años.
Siempre, teniendo como principal uso LibreDTE.

Todo el código fuente de SowerPHP se encuentra liberado utilizando la
[licencia AGPL v3 o superior](https://github.com/SowerPHP/sowerphp/blob/master/COPYING).

Más información en el [Wiki de SowerPHP](http://wiki.sowerphp.org)

Extensiones
-----------

El framework incluye por defecto 2 extensiones que serán útiles en la
construcción de una aplicación web.

### Extensión sowerphp/general

Extensión de propósito general, ya que el framework base (directorio
*lib/core*) sólo provee un conjunto muy pequeño de funcionalidades. Por lo
general será interesante y útil para el programador incluir esta extensión. A
menos claro que desee implementar sus propias funcionalidades o no quiera
depender de bibliotecas que aquí existan.

Puedes ver la documentación de la extensión en el
[Wiki de SowerPHP](http://wiki.sowerphp.org/doku.php/extensions/general)

### Extensión sowerphp/app

Extensión con funcionalidades básicas para una aplicación web. Implementa
código general que ayudará a crear el proyecto.

La extensión requiere que previamente se haya cargado la extensión general, ya
que es utilizada por esta. Por lo cual verificar que el archivo
*webroot/index.php* al menos contenga en la definición de extensiones:

```
$_EXTENSIONS = ['sowerphp/app', 'sowerphp/general'];
```

Puedes ver la documentación de la extensión en el
[Wiki de SowerPHP](http://wiki.sowerphp.org/doku.php/extensions/app).

Instalación
-----------

### Requerimientos

*	Servidor web:
	* [Debian GNU/Linux](https://www.debian.org)
	* [Apache 2](http://httpd.apache.org)
	* [PHP 7.3](http://www.php.net/downloads.php)

*	Herramientas para repositorios:
	* [Composer](https://getcomposer.org/download)
	* [Git](http://git-scm.com/download)
	* [Mercurial](http://mercurial.selenic.com/wiki/Download)

*	Bases de datos (opcionales):
	* [PostgreSQL](http://www.postgresql.org/download)
	* [MariaDB](https://downloads.mariadb.org)

### Clonado framework e instalación de dependencias

```shell
$ sudo mkdir /usr/share/sowerphp
$ sudo chown $(whoami): /usr/share/sowerphp
$ git clone https://github.com/SowerPHP/sowerphp.git /usr/share/sowerphp
$ cd /usr/share/sowerphp
$ composer install
```

Actualización
-------------

```shell
$ cd /usr/share/sowerphp
$ git pull
$ composer install
```
