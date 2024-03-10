<?php
namespace Ispahbod\UrlManager;

final class UrlManager
{
    public static function isValidUrl(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }
        $parsed_url = parse_url($url);
        if (!$parsed_url) {
            return false;
        }
        $protocols = ['http', 'https', 'ftp', 'ftps'];
        if (!in_array($parsed_url['scheme'] ?? '', $protocols)) {
            return false;
        }

        $valid_domain = '/^(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}|localhost|\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(?::\d+)?$/i';
        if (!isset($parsed_url['host']) || !preg_match($valid_domain, $parsed_url['host'])) {
            return false;
        }
        return true;
    }
    
    public static function parseUrl($link): array
    {
        if (!self::isValidUrl($link)) {
            return [];
        }
        $parsedLink = parse_url($link);
        $details = array();
        $details['protocol'] = $parsedLink['scheme'] ?? '';
        $host = $parsedLink['host'] ?? '';
        $hostParts = explode('.', $host);
        $details['domain'] = $hostParts[count($hostParts) - 2];
        $details['domain_extension'] = isset($parsedLink['host']) ? pathinfo($parsedLink['host'], PATHINFO_EXTENSION) : '';
        if (count($hostParts) > 2) {
            $details['subdomain'] = implode('.', array_slice($hostParts, 0, -2));
        } else {
            $details['subdomain'] = '';
        }
        $details['server_name'] = $parsedLink['host'] ?? '';
        $details['port'] = $parsedLink['port'] ?? '';
        $details['protocol_version'] = isset($parsedLink['scheme']) ? str_replace('HTTP/', '', $parsedLink['scheme']) : '';
        $details['fragment'] = $parsedLink['fragment'] ?? '';
        $path = isset($parsedLink['path']) ? explode('/', trim($parsedLink['path'], '/')) : array();
        $details['path_components'] = $path;
        $queryString = $parsedLink['query'] ?? '';
        parse_str($queryString, $queryArray);
        $details['query_parameters'] = $queryArray;
        $details['link_length'] = strlen($link);
        return $details;
    }

    public static function isDomainInArray(string $url, array $domains): bool
    {
        $parsedLink = self::parseUrl($url);
        $domain = $parsedLink['domain'];
        return in_array($domain, $domains);
    }

    public static function isDomainEqualTo(string $url, string $domainToCompare): bool
    {
        $parsedLink = self::parseUrl($url);
        $domain = $parsedLink['domain'];
        return $domain === $domainToCompare;
    }

    public static function getPathComponent(string $url, int $index): ?string
    {
        $parsedLink = self::parseUrl($url);
        $pathComponents = $parsedLink['path_components'] ?? [];
        return $pathComponents[$index] ?? null;
    }

    public static function getQueryParameter(string $url, string $parameter): ?string
    {
        $parsedLink = self::parseUrl($url);
        $queryParameters = $parsedLink['query_parameters'] ?? [];
        return $queryParameters[$parameter] ?? null;
    }

    public static function hasQueryParameter(string $url, string $parameter): bool
    {
        $parsedLink = self::parseUrl($url);
        $queryParameters = $parsedLink['query_parameters'] ?? [];
        return isset($queryParameters[$parameter]);
    }

    public static function countQueryParameters(string $url): int
    {
        $parsedLink = self::parseUrl($url);
        $queryParameters = $parsedLink['query_parameters'] ?? [];
        return count($queryParameters);
    }

    public static function countPathComponents(string $url): int
    {
        $parsedLink = self::parseUrl($url);
        $pathComponents = $parsedLink['path_components'] ?? [];
        return count($pathComponents);
    }

    public static function getPath(string $url): ?string
    {
        $parsedLink = self::parseUrl($url);
        return $parsedLink['path'] ?? null;
    }

    public static function searchInPath(string $url, string $searchString): bool
    {
        $path = self::getPath($url);
        return str_contains($path, $searchString);
    }

    public static function applyRegexToUrl(string $url, string $regex): string
    {
        $parsedLink = self::parseUrl($url);
        $path = $parsedLink['path'] ?? '';
        $modifiedPath = preg_replace($regex, '', $path);
        $modifiedUrl = $parsedLink['scheme'] . '://' . $parsedLink['host'] . $modifiedPath;
        if (isset($parsedLink['query'])) {
            $modifiedUrl .= '?' . $parsedLink['query'];
        }
        if (isset($parsedLink['fragment'])) {
            $modifiedUrl .= '#' . $parsedLink['fragment'];
        }
        return $modifiedUrl;
    }

    public static function getTopLevelDomain(string $url): ?string
    {
        $parsedLink = self::parseUrl($url);
        $host = $parsedLink['server_name'] ?? '';
        $hostParts = explode('.', $host);
        return end($hostParts);
    }

    public static function isSecureProtocol(string $url): bool
    {
        $parsedLink = self::parseUrl($url);
        return $parsedLink['protocol'] === 'https';
    }

    public static function getFullDomain(string $url): ?string
    {
        $parsedLink = self::parseUrl($url);
        return $parsedLink['subdomain'] ? $parsedLink['subdomain'] . '.' . $parsedLink['domain'] . '.' . $parsedLink['domain_extension'] : $parsedLink['domain'] . '.' . $parsedLink['domain_extension'];
    }

    public static function getUrlWithoutQueryParameters(string $url): string
    {
        $parsedLink = self::parseUrl($url);
        $urlWithoutQuery = $parsedLink['scheme'] . '://' . $parsedLink['host'];
        if (isset($parsedLink['path']) && $parsedLink['path'] !== '') {
            $urlWithoutQuery .= '/' . trim($parsedLink['path'], '/');
        }
        return $urlWithoutQuery;
    }

    public static function getUrlWithModifiedScheme(string $url, string $newScheme): string
    {
        $parsedLink = self::parseUrl($url);
        $modifiedUrl = $newScheme . '://' . $parsedLink['host'];
        if (isset($parsedLink['path']) && $parsedLink['path'] !== '') {
            $modifiedUrl .= '/' . trim($parsedLink['path'], '/');
        }
        if (isset($parsedLink['query'])) {
            $modifiedUrl .= '?' . $parsedLink['query'];
        }
        if (isset($parsedLink['fragment'])) {
            $modifiedUrl .= '#' . $parsedLink['fragment'];
        }
        return $modifiedUrl;
    }

    public static function extractEmailsFromUrl(string $url): array
    {
        $content = file_get_contents($url);
        preg_match_all("/[a-z0-9_\.-]+@[a-z0-9_\.-]+\.[a-z\.]{2,6}/", $content, $emails);
        return $emails[0] ?? [];
    }

    public static function getBaseUrl(string $url): string
    {
        $parsedLink = self::parseUrl($url);
        return $parsedLink['scheme'] . '://' . $parsedLink['host'];
    }
}
