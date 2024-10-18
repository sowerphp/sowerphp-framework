<?php

declare(strict_types=1);

/**
 * SowerPHP: Simple and Open Web Ecosystem Reimagined for PHP.
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

use League\CommonMark\MarkdownConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\DescriptionList\DescriptionListExtension;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\SmartPunct\SmartPunctExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\Mention\MentionExtension;
use League\CommonMark\Extension\Embed\EmbedExtension;
use League\CommonMark\Extension\Embed\Bridge\OscaroteroEmbedAdapter;
use Embed\Embed;

/**
 * Motor de renderizado de plantilla HTML utilizando Markdown.
 *
 *   - Se usa markdown para el contenido de la vista.
 *   - Se usa el primer layout encontrado para incluir el contenido.
 *
 * @link https://commonmark.thephpleague.com/
 */
class View_Engine_Markdown extends View_Engine
{
    /**
     * Instancia del convertidor de markdown.
     *
     * @var MarkdownConverter
     */
    protected $markdown;

    /**
     * Configuración del ambiente de markdown.
     *
     * @var array
     */
    protected $config = [
        'extensions' => [
            CommonMarkCoreExtension::class,
            GithubFlavoredMarkdownExtension::class,
            TableOfContentsExtension::class,
            HeadingPermalinkExtension::class,
            FootnoteExtension::class,
            DescriptionListExtension::class,
            AttributesExtension::class,
            SmartPunctExtension::class,
            ExternalLinkExtension::class,
            FrontMatterExtension::class,
            MentionExtension::class,
            EmbedExtension::class,
        ],
        'environment' => [
            'table_of_contents' => [
                'min_heading_level' => 2,
                'max_heading_level' => 3,
                'normalize' => 'relative',
                'position' => 'placeholder',
                'placeholder' => '[TOC]',
            ],
            'heading_permalink' => [
                'html_class' => 'text-decoration-none small text-muted',
                'id_prefix' => 'content',
                'fragment_prefix' => 'content',
                'insert' => 'before',
                'title' => 'Permalink',
                'symbol' => '<i class="fa-solid fa-link"></i> ',
            ],
            'external_link' => [
                //'internal_hosts' => null, // Solo el Dominio (sin esquema HTTP).
                'open_in_new_window' => true,
                'html_class' => 'external-link',
                'nofollow' => 'external',
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
            'mentions' => [
                '@' => [
                    'prefix' => '@',
                    'pattern' => '[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}(?!\w)',
                    'generator' => 'https://github.com/%s',
                ],
                '#' => [
                    'prefix' => '#',
                    'pattern' => '\d+',
                    'generator' => "https://github.com/sowerphp/sowerphp-framework/issues/%d",
                ],
            ],
            'embed' => [
                //'adapter' => null, // new OscaroteroEmbedAdapter()
                'allowed_domains' => ['youtube.com'],
                'fallback' => 'link',
                'library' => [
                    'oembed:query_parameters' => [
                        'maxwidth' => 400,
                        'maxheight' => 300,
                    ],
                ],
            ],
        ],
    ];

    /**
     * Inicialización de Markdown.
     *
     * @return void
     */
    protected function boot(): void
    {
        // Cargar configuración del motor de renderizado.
        $this->loadConfigurations();

        // Crear ambiente (entorno).
        $environment = new Environment($this->config['environment']);

        // Agregar extensiones.
        foreach ($this->config['extensions'] as $extension) {
            $environment->addExtension(new $extension());
        }

        // Crear instancia del convertidor de markdown.
        $this->markdown = new MarkdownConverter($environment);
    }

    /**
     * Genera la configuración para el ambiente de conversión de markdown.
     *
     * @return array
     */
    protected function loadConfigurations(): void
    {
        // Configuración de 'external_link'.
        // Se hace antes del merge de abajo por si se desea sobrescribir
        // mediante la configuración de la aplicación (no debería).
        $this->config['environment']['external_link']['internal_hosts'] =
            parse_url(url())['host']
        ;

        // Armar configuración usando la por defecto y la de la aplicación
        $appConfig = (array)config('app.ui.view_engine_config.markdown');
        $config = Utility_Array::mergeRecursiveDistinct(
            $this->config,
            $appConfig
        );

        // Configuración de 'embed'.
        $embedLibrary = new Embed();
        $embedLibrary->setSettings($config['environment']['embed']['library']);
        $config['environment']['embed']['adapter'] =
            new OscaroteroEmbedAdapter($embedLibrary)
        ;
        unset($config['environment']['embed']['library']);

        // Asignar la configuración.
        $this->config = $config;
    }

    /**
     * Renderiza una plantilla markdown y devolver el resultado como una cadena.
     *
     * Además, si se ha solicitado, se entregará el contenido dentro de un
     * layout que se renderizará con PHP.
     *
     * @param string $filepath Ruta a la plantilla markdown que se va a
     * renderizar.
     * @param array $data Datos que se pasarán a la plantilla markdown para su
     * uso dentro de la vista.
     * @return string El contenido HTML generado por la plantilla markdown.
     */
    public function render(string $filepath, array $data): string
    {
        // Renderizar contenido del archivo Markdown.
        $content = file_get_contents($filepath);
        foreach ($data as $key => $value) {
            if (
                is_scalar($value)
                || (is_object($value) && method_exists($value, '__toString'))
            ) {
                $content = preg_replace(
                    '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/',
                    $value,
                    $content
                );
            }
        }

        // Renderizar HTML a partir del contenido markdown.
        $result = $this->markdown->convert($content);
        $content = $result->getContent();

        // Reemplazos por diseño.
        $content = '<div class="markdown-body">' . $content . '</div>';
        $content = str_replace(
            [
                htmlspecialchars(
                    $this->config['environment']['heading_permalink']['symbol']
                ),
            ],
            [
                $this->config['environment']['heading_permalink']['symbol'],
            ],
            $content
        );

        // Si no se pidió un layout se entrega solo lo renderizado.
        if (empty($data['__view_layout'])) {
            return $content;
        }
        $data['_content'] = $content;

        // Acceder a los metadatos del Front Matter.
        if (method_exists($result, 'getFrontMatter')) {
            $frontMatter = $result->getFrontMatter();
            $data = array_merge($data, $frontMatter);
        }

        // Renderizar el layout solicitado con el contenido previamente
        // determinado ya incluído en los datos del layout.
        $layout = $this->viewService->resolveLayout($data['__view_layout']);

        // Entregar el HTML renderizado.
        return $this->viewService->renderLayout($layout, $data);
    }
}
