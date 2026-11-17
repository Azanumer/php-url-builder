<?php

declare(strict_types=1);

namespace Azanumer\UrlBuilder;

use InvalidArgumentException;

/**
 * Dependency-free URL builder: parse, mutate and (optionally) sign URLs.
 *
 * Query parameters keep insertion order. Values are encoded RFC 3986
 * (rawurlencode) on output; array values use the `key[]=value` form.
 *
 *     $url = UrlBuilder::from('https://example.com/blog?page=2')
 *         ->withQueryParam('utm_source', 'newsletter')
 *         ->withoutQueryParam('page')
 *         ->withFragment('comments');
 *     echo $url; // https://example.com/blog?utm_source=newsletter#comments
 */
class UrlBuilder
{
    /** @var string */
    private $scheme = '';

    /** @var string */
    private $host = '';

    /** @var int|null */
    private $port;

    /** @var string */
    private $user = '';

    /** @var string */
    private $pass = '';

    /** @var string */
    private $path = '';

    /** @var array<string, mixed> Ordered query parameters. */
    private $query = array();

    /** @var string */
    private $fragment = '';

    private function __construct()
    {
    }

    /**
     * Parse a URL string. Accepts absolute and relative URLs.
     *
     * @throws InvalidArgumentException on an unparseable URL.
     */
    public static function from(string $url): self
    {
        $parts = parse_url($url);
        if (false === $parts) {
            throw new InvalidArgumentException('Unparseable URL: ' . $url);
        }

        $self = new self();
        $self->scheme   = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
        $self->host     = isset($parts['host']) ? strtolower($parts['host']) : '';
        $self->port     = isset($parts['port']) ? (int) $parts['port'] : null;
        $self->user     = $parts['user'] ?? '';
        $self->pass     = $parts['pass'] ?? '';
        $self->path     = $parts['path'] ?? '';
        $self->fragment = $parts['fragment'] ?? '';

        if (isset($parts['query']) && '' !== $parts['query']) {
            parse_str($parts['query'], $self->query);
        }

        return $self;
    }

    /**
     * Build from parts. Any part may be omitted.
     */
    public static function fromParts(array $parts): self
    {
        $self = new self();
        $self->scheme   = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : '';
        $self->host     = isset($parts['host']) ? strtolower((string) $parts['host']) : '';
        $self->port     = isset($parts['port']) ? (int) $parts['port'] : null;
        $self->user     = isset($parts['user']) ? (string) $parts['user'] : '';
        $self->pass     = isset($parts['pass']) ? (string) $parts['pass'] : '';
        $self->path     = isset($parts['path']) ? (string) $parts['path'] : '';
        $self->fragment = isset($parts['fragment']) ? (string) $parts['fragment'] : '';
        if (isset($parts['query'])) {
            $self->query = is_array($parts['query']) ? $parts['query'] : self::parseQueryString((string) $parts['query']);
        }
        return $self;
    }

    private static function parseQueryString(string $query): array
    {
        $out = array();
        if ('' !== $query) {
            parse_str($query, $out);
        }
        return $out;
    }

    public function withScheme(string $scheme): self
    {
        $clone = clone $this;
        $clone->scheme = strtolower($scheme);
        return $clone;
    }

    public function withHost(string $host): self
    {
        $clone = clone $this;
        $clone->host = strtolower($host);
        return $clone;
    }

    public function withPort(?int $port): self
    {
        $clone = clone $this;
        $clone->port = $port;
        return $clone;
    }

    public function withUserInfo(string $user, string $pass = ''): self
    {
        $clone = clone $this;
        $clone->user = $user;
        $clone->pass = $pass;
        return $clone;
    }

    public function withPath(string $path): self
    {
        $clone = clone $this;
        $clone->path = $path;
        return $clone;
    }

    /**
     * Append a path segment, handling slashes on both sides.
     */
    public function appendPath(string $segment): self
    {
        $clone = clone $this;
        $clone->path = rtrim($clone->path, '/') . '/' . ltrim($segment, '/');
        return $clone;
    }

    /**
     * Set (or replace) one query parameter. Null removes it.
     *
     * @param string|array $value
     */
    public function withQueryParam(string $key, $value): self
    {
        $clone = clone $this;
        if (null === $value) {
            unset($clone->query[$key]);
        } else {
            $clone->query[$key] = $value;
        }
        return $clone;
    }

    /**
     * Set several query parameters at once (keeps existing ones).
     */
    public function withQueryParams(array $params): self
    {
        $clone = clone $this;
        foreach ($params as $key => $value) {
            if (null === $value) {
                unset($clone->query[$key]);
            } else {
                $clone->query[$key] = $value;
            }
        }
        return $clone;
    }

    public function withoutQueryParam(string $key): self
    {
        $clone = clone $this;
        unset($clone->query[$key]);
        return $clone;
    }

    public function withoutQuery(): self
    {
        $clone = clone $this;
        $clone->query = array();
        return $clone;
    }

    public function withFragment(string $fragment): self
    {
        $clone = clone $this;
        $clone->fragment = $fragment;
        return $clone;
    }

    public function withoutFragment(): self
    {
        $clone = clone $this;
        $clone->fragment = '';
        return $clone;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /** @return array<string, mixed> */
    public function getQueryParams(): array
    {
        return $this->query;
    }

    public function getQueryParam(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function isAbsolute(): bool
    {
        return '' !== $this->scheme && '' !== $this->host;
    }

    /**
     * Resolve this (possibly relative) URL against a base URL.
     */
    public function toAbsolute(string $base): self
    {
        if ($this->isAbsolute()) {
            return clone $this;
        }

        $baseUrl = self::from($base);
        if (!$baseUrl->isAbsolute()) {
            throw new InvalidArgumentException('Base URL must be absolute: ' . $base);
        }

        $clone = clone $this;
        $clone->scheme = $baseUrl->scheme;
        $clone->host   = $baseUrl->host;
        $clone->port   = $baseUrl->port;
        $clone->user   = $baseUrl->user;
        $clone->pass   = $baseUrl->pass;

        if ('' === $clone->path) {
            $clone->path = $baseUrl->path;
            if (empty($clone->query)) {
                $clone->query = $baseUrl->query;
            }
        } elseif ('/' !== $clone->path[0]) {
            $clone->path = self::mergePaths($baseUrl->path, $clone->path);
        }

        $clone->path = self::removeDotSegments($clone->path);
        return $clone;
    }

    /**
     * Sign the URL with HMAC so a tampered link can be detected.
     * The signature covers scheme, host, path and query (not the fragment,
     * which browsers never send). Default param name: "sig".
     */
    public function sign(string $secret, string $param = 'sig', string $algo = 'sha256'): self
    {
        if ('' === $secret) {
            throw new InvalidArgumentException('Signing secret must not be empty.');
        }
        $clone = clone $this;
        unset($clone->query[$param]);
        $clone->query[$param] = hash_hmac($algo, $clone->signingString(), $secret);
        return $clone;
    }

    /**
     * Verify a signature added by sign(). Uses hash_equals (timing-safe).
     */
    public function verifySigned(string $secret, string $param = 'sig', string $algo = 'sha256'): bool
    {
        if (!isset($this->query[$param]) || !is_string($this->query[$param])) {
            return false;
        }
        $given = $this->query[$param];
        $clone = clone $this;
        unset($clone->query[$param]);
        return hash_equals(hash_hmac($algo, $clone->signingString(), $secret), $given);
    }

    /**
     * Canonical string used for signing: scheme://host[:port]path?query
     * with query parameters sorted by key.
     */
    private function signingString(): string
    {
        $query = $this->query;
        ksort($query);
        $out = '';
        if ('' !== $this->scheme) {
            $out .= $this->scheme . '://';
        }
        $out .= $this->host;
        if (null !== $this->port && !$this->isDefaultPort()) {
            $out .= ':' . $this->port;
        }
        $out .= $this->path;
        $encoded = self::buildQuery($query);
        if ('' !== $encoded) {
            $out .= '?' . $encoded;
        }
        return $out;
    }

    private function isDefaultPort(): bool
    {
        return ('http' === $this->scheme && 80 === $this->port)
            || ('https' === $this->scheme && 443 === $this->port);
    }

    /**
     * RFC 3986 query encoding. Arrays become key[]=value pairs.
     */
    private static function buildQuery(array $params): string
    {
        $pairs = array();
        foreach ($params as $key => $value) {
            $key = rawurlencode((string) $key);
            if (is_array($value)) {
                foreach ($value as $item) {
                    $pairs[] = $key . '%5B%5D=' . rawurlencode((string) $item);
                }
            } elseif (null === $value) {
                $pairs[] = $key;
            } else {
                $pairs[] = $key . '=' . rawurlencode((string) $value);
            }
        }
        return implode('&', $pairs);
    }

    private static function mergePaths(string $basePath, string $relPath): string
    {
        $pos = strrpos($basePath, '/');
        if (false === $pos) {
            return '/' . $relPath;
        }
        return substr($basePath, 0, $pos + 1) . $relPath;
    }

    /**
     * RFC 3986 section 5.2.4 dot-segment removal.
     */
    private static function removeDotSegments(string $path): string
    {
        $segments = explode('/', $path);
        $out = array();
        foreach ($segments as $segment) {
            if ('.' === $segment) {
                continue;
            }
            if ('..' === $segment) {
                // Never pop the leading "" that marks an absolute path,
                // so "/../x" resolves to "/x" instead of losing its slash.
                if (!empty($out) && '' !== end($out)) {
                    array_pop($out);
                }
                continue;
            }
            $out[] = $segment;
        }
        $result = implode('/', $out);
        // Preserve a trailing slash only when the input ENDS in "/." or
        // "/.." (a mid-path "/./" must not add one).
        if (preg_match('#/\.$|/\.\.$#', $path) && '/' !== substr($result, -1)) {
            $result .= '/';
        }
        return $result;
    }

    public function toString(): string
    {
        $url = '';
        if ('' !== $this->scheme) {
            $url .= $this->scheme . ':';
        }
        if ('' !== $this->host) {
            $url .= '//';
            if ('' !== $this->user) {
                $url .= rawurlencode($this->user);
                if ('' !== $this->pass) {
                    $url .= ':' . rawurlencode($this->pass);
                }
                $url .= '@';
            }
            $url .= $this->host;
            if (null !== $this->port && !$this->isDefaultPort()) {
                $url .= ':' . $this->port;
            }
        }
        $url .= $this->path;
        $query = self::buildQuery($this->query);
        if ('' !== $query) {
            $url .= '?' . $query;
        }
        if ('' !== $this->fragment) {
            $url .= '#' . rawurlencode($this->fragment);
        }
        return $url;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
