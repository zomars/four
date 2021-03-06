<?php

declare(strict_types=1);

namespace Bolt;

use Bolt\Configuration\Config;
use Bolt\Entity\Content;
use Tightenco\Collect\Support\Collection;

class TemplateChooser
{
    /** @var Config */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function forHomepage(?Content $content = null): array
    {
        $templates = new Collection();

        // First candidate: Theme-specific config.yml file.
        $templates->push($this->config->get('theme/homepage_template'));

        // Second candidate: Global config.yml file.
        $templates->push($this->config->get('general/homepage_template'));

        if (empty($content)) {
            // Fallback if no content: index.twig
            $templates->push('index.html.twig')->push('index.twig');
        } elseif (is_array($content)) {
            // Fallback with multiple content: use listing() to choose template
            /** @var Content $first */
            $first = reset($content);
            $templates = $templates->merge($this->forListing($first->getDefinition()));
        } else {
            // Fallback with single content: use record() to choose template
            $templates = $templates->merge($this->forRecord($content));
        }

        return $templates->unique()->toArray();
    }

    /**
     * Choose a template for a single record page, e.g.:
     * - '/page/about'
     * - '/entry/lorum-ipsum'.
     */
    public function forRecord(Content $record): array
    {
        $templates = new Collection();
        $definition = $record->getDefinition();

        // First candidate: Content record has a templateselect field, and it's set.
        foreach ($definition->get('fields') as $name => $field) {
            if ($field['type'] === 'templateselect' && $record->hasField($name)) {
                $templates->push((string) $record->getField($name));
            }
        }

        // Second candidate: defined specifically in the content type.
        if ($definition->has('record_template')) {
            $templates->push($definition->get('record_template'));
        }

        // Third candidate: a template with the same filename as the name of
        // the content type.
        $templates->push($definition->get('singular_slug') . '.html.twig');
        $templates->push($definition->get('singular_slug') . '.twig');

        // Fourth candidate: Theme-specific config.yml file.
        $templates->push($this->config->get('theme/record_template'));

        // Fifth candidate: global config.yml
        $templates->push($this->config->get('general/record_template'));

        // Sixth candidate: fallback to 'record.html.twig'
        $templates->push('record.html.twig');

        return $templates->unique()->filter()->toArray();
    }

    public function forListing(?Collection $contentType = null): array
    {
        $templates = new Collection();

        // First candidate: defined specifically in the content type.
        if (! empty($contentType['listing_template'])) {
            $templates->push($contentType['listing_template']);
        }

        // Second candidate: a template with the same filename as the name of
        // the content type.
        $templates->push($contentType['slug'] . '.html.twig');
        $templates->push($contentType['slug'] . '.twig');

        // Third candidate: Theme-specific config.yml file.
        $templates->push($this->config->get('theme/listing_template'));

        // Fourth candidate: Global config.yml
        $templates->push($this->config->get('general/listing_template'));

        // Fifth candidate: fallback to 'listing.html.twig'
        $templates->push('listing.html.twig');

        return $templates->unique()->filter()->toArray();
    }

    public function forTaxonomy(string $taxonomyslug): array
    {
        $templates = new Collection();

        // First candidate: defined specifically in the taxonomy
        $templates->push($this->config->get('taxonomy/' . $taxonomyslug . '/listing_template'));

        // Second candidate: Theme-specific config.yml file.
        $templates->push($this->config->get('theme/listing_template'));

        // Third candidate: Global config.yml
        $templates->push($this->config->get('general/listing_template'));

        return $templates->unique()->filter()->toArray();
    }

    public function forSearch(): array
    {
        $templates = new Collection();

        // First candidate: specific search setting in global config.
        $templates->push($this->config->get('theme/search_results_template'));

        // Second candidate: specific search setting in global config.
        $templates->push($this->config->get('general/search_results_template'));

        // Third candidate: listing config setting.
        $templates->push($this->config->get('general/listing_template'));

        return $templates->unique()->filter()->toArray();
    }

    public function forMaintenance(): array
    {
        $templates = new Collection();

        // First candidate: Theme-specific config.
        $templates->push($this->config->get('theme/maintenance_template'));

        // Second candidate: global config.
        $templates->push($this->config->get('general/maintenance_template'));

        return $templates->unique()->filter()->toArray();
    }
}
