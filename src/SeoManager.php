<?php

/*
 * This file is part of the SeoOverride project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Joli\SeoOverride;

/**
 * This manager is the main class you should use to manually configure the SEO
 * of the current action.
 */
class SeoManager implements SeoManagerInterface
{
    /** @var Fetcher[] */
    private $fetchers;

    /** @var string[] */
    private $domains;

    /** @var Seo */
    private $seo;

    /**
     * @param Fetcher[] $fetchers
     * @param string[]  $domains
     * @param Seo       $seo
     */
    public function __construct(array $fetchers, array $domains, Seo $seo = null)
    {
        $this->fetchers = $fetchers;
        $this->domains = $domains;
        $this->seo = $seo ?: new Seo();
    }

    /**
     * {@inheritdoc}
     */
    public function getSeo(): Seo
    {
        return $this->seo;
    }

    /**
     * {@inheritdoc}
     */
    public function updateAndOverride(string $html, string $path, string $domain): string
    {
        $this->updateSeo($path, $domain);

        return $this->overrideHtml($html);
    }

    /**
     * {@inheritdoc}
     */
    public function updateSeo(string $path, string $domain): Seo
    {
        $domainAlias = $this->findDomainAlias($domain);

        foreach ($this->fetchers as $fetcher) {
            $seo = $this->fetch($fetcher, $path, $domainAlias);

            if ($seo) {
                $this->mergeSeo($seo);
                break;
            }
        }

        return $this->seo;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(Fetcher $fetcher, string $path, $domainAlias)
    {
        // Try for the requested domain if it's known
        if ($domainAlias && $seo = $fetcher->fetch($path, $domainAlias)) {
            return $seo;
        }

        // Try for the catch all domain
        if ($seo = $fetcher->fetch($path, null)) {
            return $seo;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function overrideHtml(string $html): string
    {
        $seo = $this->getSeo();

        return preg_replace(
            [
                '@<!--SEO_TITLE-->(.+)<!--/SEO_TITLE-->@im',
                '@<!--SEO_DESCRIPTION-->(.*?)<!--/SEO_DESCRIPTION-->@im',
                '@<!--SEO_KEYWORDS-->(.*?)<!--/SEO_KEYWORDS-->@im',
                '@<!--SEO_ROBOTS-->(.*?)<!--/SEO_ROBOTS-->@im',
                '@<!--SEO_CANONICAL-->(.*?)<!--/SEO_CANONICAL-->@im',
                '@<!--SEO_OG_TITLE-->(.*?)<!--/SEO_OG_TITLE-->@im',
                '@<!--SEO_OG_DESCRIPTION-->(.*?)<!--/SEO_OG_DESCRIPTION-->@im',
            ],
            [
                $seo->getTitle() ? '<title>'.htmlspecialchars($seo->getTitle()).'</title>' : '$1',
                $seo->getDescription() ? '<meta name="description" content="'.htmlspecialchars($seo->getDescription()).'" />' : '$1',
                $seo->getKeywords() ? '<meta name="keywords" content="'.htmlspecialchars($seo->getKeywords()).'" />' : '$1',
                $seo->getRobots() ? '<meta name="robots" content="'.htmlspecialchars($seo->getRobots()).'" />' : '$1',
                $seo->getCanonical() ? '<link rel="canonical" href="'.htmlspecialchars($seo->getCanonical()).'" />' : '$1',
                $seo->getOgTitle() ? '<meta property="og:title" content="'.htmlspecialchars($seo->getOgTitle()).'" />' : '$1',
                $seo->getOgDescription() ? '<meta property="og:description" content="'.htmlspecialchars($seo->getOgDescription()).'" />' : '$1',
            ],
            $html
        );
    }

    /**
     * Update and override the Seo of the HTML for the given domain and path.
     *
     * @return string|null
     */
    protected function findDomainAlias(string $domain)
    {
        foreach ($this->domains as $domainAlias => $pattern) {
            if (preg_match('#'.$pattern.'#i', $domain)) {
                return $domainAlias;
            }
        }

        return null;
    }

    /**
     * Merge the given Seo data inside the current one.
     */
    private function mergeSeo(Seo $seo)
    {
        if ($seo->getTitle()) {
            $this->seo->setTitle($seo->getTitle());
        }
        if ($seo->getDescription()) {
            $this->seo->setDescription($seo->getDescription());
        }
        if ($seo->getKeywords()) {
            $this->seo->setKeywords($seo->getKeywords());
        }
        if ($seo->getRobots()) {
            $this->seo->setRobots($seo->getRobots());
        }
        if ($seo->getCanonical()) {
            $this->seo->setCanonical($seo->getCanonical());
        }
        if ($seo->getOgTitle()) {
            $this->seo->setOgTitle($seo->getOgTitle());
        }
        if ($seo->getOgDescription()) {
            $this->seo->setOgDescription($seo->getOgDescription());
        }
    }
}
