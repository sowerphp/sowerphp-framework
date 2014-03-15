SowerPHP
========

SowerPHP es un framework, o ambiente de desarrollo, para PHP.

El framework es minimalista, entregando las herramientas básicas para el
desarrollo, como por ejemplo MVC u ORM. Sin embargo, existen extensiones
oficiales que entregan nuevas funcionalidades. Adicionalmente cualquier usuario
puede desarrollar sus propias extensiones.

El objetivo principal del framework es entregar una estructura estándar y mínima
que cualquier aplicación en PHP debería considerar utilizando los patrones y
buenas prácticas que el equipo de SowerPHP considera.

Todo el código generado por el equipo de SowerPHP se encuentra liberado
utilizando la licencia GPL v3 o superior.

Directorios
-----------

*	**standard**: directorio con archivos estándares del framework. Proveen
	las funcionalidades básicas (mínimas).
	
*	**extensions**: directorio para las extensiones desarrolladas
	específicamente para el framework ya sean oficiales o desarrolladas por
	la comunidad. Estas extensiones serán compartidas entre todos los que
	usen el framework.

	Este directorio lo crea *composer* de forma automática.

*	**project**: directorio con el sitio o aplicación web que se está
	desarrollando. Este directorio contiene los directorios:

	*	**website**: sitio web o aplicación propiamente tal.

	*	**extensions**: extensiones desarrolladas específicamente para
		el framework, pero que están visibles solo para el proyecto.

		Este directorio lo crea *composer* de forma automática.

Enlaces de interés
------------------

* Página del proyecto: <http://sowerphp.org>
* Proyecto en GitHUB: <https://github.com/SowerPHP/sowerphp>
