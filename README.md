SowerPHP
========

SowerPHP es un framework, o ambiente de desarrollo, para PHP desarrollado en Chile.

El principal proyecto desarrollado con este framework es la
[Aplicación Web de LibreDTE](https://github.com/LibreDTE/libredte-webapp).
Gracias a dicho proyecto es que este framework ha sido mejorado con los años,
siempre, teniendo como principal uso LibreDTE.

Todo el código fuente de SowerPHP se encuentra liberado utilizando la
[licencia AGPL v3 o superior](https://github.com/sascocl/sowerphp/blob/master/COPYING).

Ambiente DEV o QA (no producción)
---------------------------------

### Instalación

```shell
mkdir -p $HOME/dev/www
cd $HOME/dev/www
git clone git@github.com:sascocl/sowerphp.git sowerphp-framework
cd sowerphp-framework
composer install
```

### Ejecución de pruebas

```shell
XDEBUG_MODE=coverage ./vendor/bin/phpunit
```
