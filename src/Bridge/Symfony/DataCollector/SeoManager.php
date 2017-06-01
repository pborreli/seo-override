<?php

namespace Joli\SeoOverride\Bridge\Symfony\DataCollector;

use Joli\SeoOverride\Fetcher;
use Joli\SeoOverride\Seo;
use Joli\SeoOverride\SeoManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\Yaml\Yaml;

class SeoManager extends \Joli\SeoOverride\SeoManager
{
    protected $data = [];

    public function __construct(array $fetchers, array $domains, Seo $seo = null)
    {
        parent::__construct($fetchers, $domains, $seo);

        if ($seo) {
            $this->data['seo_versions'][] = [
                'seo' => clone $seo,
                'origin' => 'initial'
            ];
        }

        $this->data['fetchers'] = [];
        $this->data['domains'] = array_keys($domains);
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function updateSeo(string $path, string $domain): Seo
    {
        $this->data['path'] = $path;
        $this->data['domain'] = $domain;
        $this->data['status'] = SeoOverrideDataCollector::STATUS_DEFAULT;

        $this->data['seo_versions'][] = [
            'seo' => clone $this->getSeo(),
            'origin' => 'before update',
        ];

        return parent::updateSeo($path, $domain);
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(Fetcher $fetcher, string $path, $domainAlias)
    {
        $callback = function(string $path, string $domainAlias = null, Seo $seo = null) use ($fetcher) {
            $this->data['fetchers'][] = [
                'name' => get_class($fetcher),
                'matched' => $seo instanceof Seo,
                'domain_alias' => $domainAlias,
            ];
        };
        $callback->bindTo($this);

        $collectorFetcher = new CallbackFetcher($fetcher, $callback);
        $seo = parent::fetch($collectorFetcher, $path, $domainAlias);

        if ($seo) {
            $this->data['status'] = SeoOverrideDataCollector::STATUS_MATCHED;
            $this->data['seo_versions'][] = [
                'seo' => clone $seo,
                'fetcher' => get_class($fetcher),
                'origin' => 'from fetcher',
            ];
        }

        return $seo;
    }

    /**
     * {@inheritdoc}
     */
    protected function findDomainAlias(string $domain)
    {
        $domainAlias = parent::findDomainAlias($domain);
        $this->data['domain_alias'] = $domainAlias;

        return $domainAlias;
    }
}
