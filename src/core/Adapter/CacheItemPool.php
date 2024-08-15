<?php

/**
 * SowerPHP: Framework PHP hecho en Chile.
 * Copyright (C) SowerPHP <https://www.sowerphp.org>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero
 * de GNU publicada por la Fundación para el Software Libre, ya sea la
 * versión 3 de la Licencia, o (a su elección) cualquier versión
 * posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU
 * para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General
 * Affero de GNU junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace sowerphp\core;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Adaptador para CacheItemPool que utiliza un repositorio de caché de
 * Illuminate.
 */
class Adapter_CacheItemPool implements CacheItemPoolInterface
{

    /**
     * El repositorio de caché de Illuminate.
     *
     * @var CacheRepository
     */
    protected $cache;

    /**
     * Constructor.
     *
     * @param CacheRepository $cache El repositorio de caché de Illuminate.
     */
    public function __construct(CacheRepository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Obtiene un ítem de la caché.
     *
     * @param string $key La clave del ítem.
     * @return CacheItemInterface El ítem de la caché.
     */
    public function getItem($key): CacheItemInterface
    {
        $value = $this->cache->get($key);
        return new CacheItem($key, $value, $value !== null);
    }

    /**
     * Obtiene múltiples ítems de la caché.
     *
     * @param array $keys Las claves de los ítems.
     * @return iterable Los ítems de la caché.
     */
    public function getItems(array $keys = array()): iterable
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }
        return $items;
    }

    /**
     * Verifica si un ítem existe en la caché.
     *
     * @param string $key La clave del ítem.
     * @return bool `true` si el ítem existe, `false` en caso contrario.
     */
    public function hasItem($key): bool
    {
        return $this->cache->has($key);
    }

    /**
     * Limpia toda la caché.
     *
     * @return bool `true` si la caché fue limpiada con éxito, `false` en caso
     * contrario.
     */
    public function clear(): bool
    {
        return $this->cache->clear();
    }

    /**
     * Elimina un ítem de la caché.
     *
     * @param string $key La clave del ítem.
     * @return bool `true` si el ítem fue eliminado con éxito, `false` en caso
     * contrario.
     */
    public function deleteItem($key): bool
    {
        return $this->cache->forget($key);
    }

    /**
     * Elimina múltiples ítems de la caché.
     *
     * @param array $keys Las claves de los ítems.
     * @return bool `true` si los ítems fueron eliminados con éxito, `false` en
     * caso contrario.
     */
    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->deleteItem($key);
        }
        return true;
    }

    /**
     * Guarda un ítem en la caché.
     *
     * @param CacheItemInterface $item El ítem a guardar.
     * @return bool `true` si el ítem fue guardado con éxito, `false` en caso
     * contrario.
     */
    public function save(CacheItemInterface $item): bool
    {
        $expiration = $item->getExpiresAt()
            ? $item->getExpiresAt()->getTimestamp() - time()
            : null
        ;
        return $this->cache->put($item->getKey(), $item->get(), $expiration);
    }

    /**
     * Guarda un ítem en la caché de forma diferida.
     *
     * @param CacheItemInterface $item El ítem a guardar.
     * @return bool `true` si el ítem fue guardado con éxito, `false` en caso
     * contrario.
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        // Implementación de guardado diferido si es necesario.
        return $this->save($item);
    }

    /**
     * Confirma las operaciones diferidas.
     *
     * @return bool `true` si las operaciones fueron confirmadas con éxito,
     * `false` en caso contrario.
     */
    public function commit(): bool
    {
        // Implementación de commit si es necesario.
        return true;
    }

}

/**
 * Representa un ítem de la caché.
 */
class CacheItem implements CacheItemInterface
{

    /**
     * La clave del ítem de la caché.
     *
     * @var string
     */
    protected $key;

    /**
     * El valor del ítem de la caché.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Indica si el ítem fue encontrado en la caché.
     *
     * @var bool
     */
    protected $hit;

    /**
     * La fecha y hora de expiración del ítem.
     *
     * @var \DateTimeInterface|null
     */
    protected $expiresAt;

    /**
     * Constructor.
     *
     * @param string $key La clave del ítem.
     * @param mixed $value El valor del ítem.
     * @param bool $hit Indica si el ítem fue encontrado en la caché.
     */
    public function __construct($key, $value = null, $hit = false)
    {
        $this->key = $key;
        $this->value = $value;
        $this->hit = $hit;
    }

    /**
     * Obtiene la clave del ítem de la caché.
     *
     * @return string La clave del ítem.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Obtiene el valor del ítem de la caché.
     *
     * @return mixed El valor del ítem.
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * Indica si el ítem fue encontrado en la caché.
     *
     * @return bool `true` si el ítem fue encontrado, `false` en caso
     * contrario.
     */
    public function isHit(): bool
    {
        return $this->hit;
    }

    /**
     * Establece el valor del ítem de la caché.
     *
     * @param mixed $value El nuevo valor del ítem.
     * @return CacheItemInterface
     */
    public function set($value): CacheItemInterface
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Establece la fecha y hora de expiración del ítem de la caché.
     *
     * @param \DateTimeInterface|null $expiration La fecha y hora de
     * expiración.
     * @return CacheItemInterface
     */
    public function expiresAt($expiration): CacheItemInterface
    {
        $this->expiresAt = $expiration;
        return $this;
    }

    /**
     * Establece el tiempo de expiración del ítem de la caché.
     *
     * @param int|\DateInterval|null $time El tiempo de expiración en segundos
     * o como un objeto DateInterval.
     * @return CacheItemInterface
     */
    public function expiresAfter($time): CacheItemInterface
    {
        if (is_int($time)) {
            $this->expiresAt = (new \DateTime())->add(
                new \DateInterval('PT' . $time . 'S')
            );
        } else {
            $this->expiresAt = $time;
        }
        return $this;
    }

    /**
     * Obtiene la fecha y hora de expiración del ítem de la caché.
     *
     * @return \DateTimeInterface|null La fecha y hora de expiración, o `null`
     * si no está establecido.
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

}
