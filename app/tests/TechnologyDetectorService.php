<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TechnologyDetectorService
{
    public function __construct(
        private HttpClientInterface $client,
    ) {
    }

    public function analyze(string $url): array
    {
        $response = $this->client->request('GET', $url, [
            'timeout' => 10,
            'max_redirects' => 5,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; TechnologyDetector/1.0)',
            ],
        ]);

        $html = $response->getContent();
        $headers = $response->getHeaders(false);

        return $this->detect($html, $headers);
    }

    private function detect(string $html, array $headers): array
    {
        $technologies = [];

        $htmlLower = strtolower($html);

        // WordPress
        if (
            str_contains($htmlLower, 'wp-content/') ||
            str_contains($htmlLower, 'wp-includes/')
        ) {
            $technologies[] = 'WordPress';
        }

        // WooCommerce
        if (
            str_contains($htmlLower, 'woocommerce') ||
            str_contains($htmlLower, 'wc-')
        ) {
            $technologies[] = 'WooCommerce';
        }

        // Shopify
        if (
            str_contains($htmlLower, 'cdn.shopify.com') ||
            str_contains($htmlLower, 'shopify')
        ) {
            $technologies[] = 'Shopify';
        }

        // React
        if (
            str_contains($htmlLower, 'react') ||
            str_contains($html, '__NEXT_DATA__')
        ) {
            $technologies[] = 'React';
        }

        // Vue.js
        if (
            str_contains($htmlLower, 'vue.js') ||
            str_contains($htmlLower, 'vue.min.js')
        ) {
            $technologies[] = 'Vue.js';
        }

        // Angular
        if (
            str_contains($htmlLower, 'ng-version') ||
            str_contains($htmlLower, 'angular')
        ) {
            $technologies[] = 'Angular';
        }

        // Bootstrap
        if (
            str_contains($htmlLower, 'bootstrap.min.css') ||
            str_contains($htmlLower, 'bootstrap.css')
        ) {
            $technologies[] = 'Bootstrap';
        }

        // Tailwind
        if (
            str_contains($htmlLower, 'tailwindcss') ||
            str_contains($htmlLower, 'tailwind.min.css')
        ) {
            $technologies[] = 'Tailwind CSS';
        }

        // Google Analytics
        if (
            str_contains($htmlLower, 'google-analytics.com') ||
            str_contains($htmlLower, 'gtag(') ||
            str_contains($htmlLower, 'googletagmanager.com')
        ) {
            $technologies[] = 'Google Analytics';
        }

        // Google Tag Manager
        if (str_contains($htmlLower, 'googletagmanager.com')) {
            $technologies[] = 'Google Tag Manager';
        }

        // Cloudflare
        if (
            isset($headers['server']) &&
            $this->headerContains($headers['server'], 'cloudflare')
        ) {
            $technologies[] = 'Cloudflare';
        }

        // Nginx
        if (
            isset($headers['server']) &&
            $this->headerContains($headers['server'], 'nginx')
        ) {
            $technologies[] = 'Nginx';
        }

        // Apache
        if (
            isset($headers['server']) &&
            $this->headerContains($headers['server'], 'apache')
        ) {
            $technologies[] = 'Apache';
        }

        // PHP
        if (
            isset($headers['x-powered-by']) &&
            $this->headerContains($headers['x-powered-by'], 'php')
        ) {
            $technologies[] = 'PHP';
        }

        return array_values(array_unique($technologies));
    }

    private function headerContains(array $values, string $needle): bool
    {
        foreach ($values as $value) {
            if (str_contains(strtolower($value), strtolower($needle))) {
                return true;
            }
        }

        return false;
    }
}

