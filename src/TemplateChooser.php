<?php

declare(strict_types=1);

namespace Bolt;

use Bolt\Configuration\Config;
use Bolt\Entity\Content;
use Tightenco\Collect\Support\Collection;

/**
 * A class for choosing whichever template should be used.
 */
class TemplateChooser
{
    /** @var Config */
    private $config;

    /**
     * Constructor.
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Choose a template for the homepage.
     */
    public function homepage(?Content $content = null): Collection
    {
        $templates = collect([]);

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
            $templates = $templates->merge($this->listing($first->getDefinition()));
        } else {
            // Fallback with single content: use record() to choose template
            $templates = $templates->merge($this->record($content));
        }

        return $templates->unique();
    }

    /**
     * Choose a template for a single record page, e.g.:
     * - '/page/about'
     * - '/entry/lorum-ipsum'.
     */
    public function record(Content $record, ?array $data = null): Collection
    {
        $templates = collect([]);
        $definition = $record->getDefinition();

        // First candidate: Content record has a templateselect field, and it's set.
        foreach ($definition->get('fields') as $name => $field) {
            if ($field['type'] === 'templateselect' && $record->has($name)) {
                $templates->push((string) $record->get($name));
            }
        }

        // Second candidate: defined specifically in the contenttype.
        if ($definition->has('record_template')) {
            $templates->push($definition->get('record_template'));
        }

        // Third candidate: a template with the same filename as the name of
        // the contenttype.
        $templates->push($definition->get('singular_slug') . '.html.twig');
        $templates->push($definition->get('singular_slug') . '.twig');

        // Fourth candidate: Theme-specific config.yml file.
        $templates->push($this->config->get('theme/record_template'));

        // Fifth candidate: global config.yml
        $templates->push($this->config->get('general/record_template'));

        // Sixth candidate: fallback to 'record.html.twig'
        $templates->push('record.html.twig');

        return $templates->unique()->filter();
    }

    /**
     * Select a template for listing pages.
     */
    public function listing(?Collection $contenttype = null): Collection
    {
        $templates = collect([]);

        // First candidate: defined specifically in the contenttype.
        if (! empty($contenttype['listing_template'])) {
            $templates->push($contenttype['listing_template']);
        }

        // Second candidate: a template with the same filename as the name of
        // the contenttype.
        $templates->push($contenttype['slug'] . '.html.twig');
        $templates->push($contenttype['slug'] . '.twig');

        // Third candidate: Theme-specific config.yml file.
        $templates->push($this->config->get('theme/listing_template'));

        // Fourth candidate: Global config.yml
        $templates->push($this->config->get('general/listing_template'));

        // Fifth candidate: fallback to 'listing.html.twig'
        $templates->push('listing.html.twig');

        return $templates->unique()->filter();
    }

    /**
     * Select a template for taxonomy.
     */
    public function taxonomy(string $taxonomyslug): Collection
    {
        $templates = collect([]);

        // First candidate: defined specifically in the taxonomy
        $templates->push($this->config->get('taxonomy/' . $taxonomyslug . '/listing_template'));

        // Second candidate: Theme-specific config.yml file.
        $templates->push($this->config->get('theme/listing_template'));

        // Third candidate: Global config.yml
        $templates->push($this->config->get('general/listing_template'));

        return $templates->unique()->filter();
    }

    /**
     * Select a search template.
     */
    public function search(): Collection
    {
        $templates = collect([]);

        // First candidate: specific search setting in global config.
        $templates->push($this->config->get('theme/search_results_template'));

        // Second candidate: specific search setting in global config.
        $templates->push($this->config->get('general/search_results_template'));

        // Third candidate: listing config setting.
        $templates->push($this->config->get('general/listing_template'));

        return $templates->unique()->filter();
    }

    /**
     * Select a template to use for the "maintenance" page.
     */
    public function maintenance(): Collection
    {
        $templates = collect([]);

        // First candidate: Theme-specific config.
        $templates->push($this->config->get('theme/maintenance_template'));

        // Second candidate: global config.
        $templates->push($this->config->get('general/maintenance_template'));

        return $templates->unique()->filter();
    }
}
