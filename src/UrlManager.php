<?php
namespace Ispahbod\UrlManager;

final class UrlManager
{
    public static function IsValidUrl(?string $url): bool
    {
        $protocols = ['http', 'https', 'ftp', 'ftps'];
        $valid_domain = '/^(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}|localhost|\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(?::\d+)?$/i';
        if (empty($url)) {
            return false;
        }
        $parsed_url = parse_url($url);
        if (!isset($parsed_url['scheme']) || !in_array($parsed_url['scheme'], $protocols)) {
            return false;
        }
        if (!preg_match($valid_domain, $parsed_url['host'])) {
            return false;
        }
        return true;
    }

    public static function ParseUrl($link): array
    {
        if (!self::IsValidUrl($link)) {
            return [];
        }
        // Parse the URL
        $parsedLink = parse_url($link);

        // Initialize an array to store link details
        $details = array();

        // Extract protocol
        $details['protocol'] = isset($parsedLink['scheme']) ? $parsedLink['scheme'] : '';

        // Extract domain without extension
        $host = isset($parsedLink['host']) ? $parsedLink['host'] : '';
        $hostParts = explode('.', $host);
        $details['domain'] = $hostParts[count($hostParts) - 2];

        // Extract domain extension
        $details['domain_extension'] = isset($parsedLink['host']) ? pathinfo($parsedLink['host'], PATHINFO_EXTENSION) : '';

        // Extract subdomain
        if (count($hostParts) > 2) {
            $details['subdomain'] = implode('.', array_slice($hostParts, 0, -2));
        } else {
            $details['subdomain'] = '';
        }

        // Extract server name
        $details['server_name'] = isset($parsedLink['host']) ? $parsedLink['host'] : '';

        // Extract port
        $details['port'] = isset($parsedLink['port']) ? $parsedLink['port'] : '';

        // Extract protocol version
        $details['protocol_version'] = isset($parsedLink['scheme']) ? str_replace('HTTP/', '', $parsedLink['scheme']) : '';

        // Extract fragment (if available)
        $details['fragment'] = isset($parsedLink['fragment']) ? $parsedLink['fragment'] : '';

        // Extract path components
        $path = isset($parsedLink['path']) ? explode('/', trim($parsedLink['path'], '/')) : array();
        $details['path_components'] = $path;

        // Extract query string parameters
        $queryString = isset($parsedLink['query']) ? $parsedLink['query'] : '';
        parse_str($queryString, $queryArray);
        $details['query_parameters'] = $queryArray;

        // Calculate link length
        $details['link_length'] = strlen($link);

        return $details;
    }

    public static function IsDomainInArray(string $url, array $domains): bool
    {
        // Parse the URL
        $parsedLink = self::ParseUrl($url);

        // Extract domain
        $domain = $parsedLink['domain'];

        // Check if domain exists in the array
        return in_array($domain, $domains);
    }

    public static function IsDomainEqualTo(string $url, string $domainToCompare): bool
    {
        // Parse the URL
        $parsedLink = self::ParseUrl($url);

        // Extract domain
        $domain = $parsedLink['domain'];

        // Check if domain is equal to the provided domain
        return $domain === $domainToCompare;
    }

    public static function GetPathComponent(string $url, int $index): string
    {
        // Parse the URL
        $parsedLink = self::ParseUrl($url);

        // Get path components
        $pathComponents = $parsedLink['path_components'] ?? [];

        // Return the component at the given index, or an empty string if not found
        return $pathComponents[$index] ?? '';
    }

    public static function GetQueryParameter(string $url, string $parameter): string
    {
        // Parse the URL
        $parsedLink = self::ParseUrl($url);

        // Get query parameters
        $queryParameters = $parsedLink['query_parameters'] ?? [];

        // Return the value of the specified parameter, or an empty string if not found
        return $queryParameters[$parameter] ?? '';
    }

    public static function HasQueryParameter(string $url, string $parameter): bool
    {
        // Parse the URL
        $parsedLink = self::ParseUrl($url);

        // Get query parameters
        $queryParameters = $parsedLink['query_parameters'] ?? [];

        // Check if the parameter exists
        return isset($queryParameters[$parameter]);
    }

    public static function CountQueryParameters(string $url): int
    {
        // Parse the URL
        $parsedLink = self::ParseUrl($url);

        // Get query parameters
        $queryParameters = $parsedLink['query_parameters'] ?? [];

        // Return the number of query parameters
        return count($queryParameters);
    }

    public static function CountPathComponents(string $url): int
    {
        // Parse the URL
        $parsedLink = self::ParseUrl($url);

        // Get path components
        $pathComponents = $parsedLink['path_components'] ?? [];

        // Return the number of path components
        return count($pathComponents);
    }

    public static function GetPath(string $url): string
    {
        // Parse the URL
        $parsedLink = self::ParseUrl($url);

        // Get path
        $path = $parsedLink['path'] ?? '';

        return $path;
    }

    public static function SearchInPath(string $url, string $searchString): bool
    {
        // Get the path
        $path = self::GetPath($url);

        // Perform search in path
        return strpos($path, $searchString) !== false;
    }

    public static function ApplyRegexToUrl(string $url, string $regex): string
    {
        // Parse the URL
        $parsedLink = self::ParseUrl($url);

        // Apply regex to path
        $path = $parsedLink['path'] ?? '';
        $modifiedPath = preg_replace($regex, '', $path);

        // Reconstruct the modified URL
        $modifiedUrl = $parsedLink['scheme'] . '://' . $parsedLink['host'] . $modifiedPath;

        // Add query string if exists
        if (isset($parsedLink['query'])) {
            $modifiedUrl .= '?' . $parsedLink['query'];
        }

        // Add fragment if exists
        if (isset($parsedLink['fragment'])) {
            $modifiedUrl .= '#' . $parsedLink['fragment'];
        }

        return $modifiedUrl;
    }
}
