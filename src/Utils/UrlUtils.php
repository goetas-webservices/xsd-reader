<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Utils;

class UrlUtils
{
    public static function resolveRelativeUrl(string $base, string $rel): string
    {
        if ('' === trim($rel)) {
            return $base;
        }

        if (null !== parse_url($rel, PHP_URL_SCHEME) || str_starts_with($rel, '//')) {
            return $rel;
        }

        if (in_array($rel[0], ['#', '?'], true)) {
            return $base . $rel;
        }

        return static::resolveRelativeUrlAfterEarlyChecks($base, $rel);
    }

    protected static function resolveRelativeUrlAfterEarlyChecks(string $base, string $rel): string
    {
        /* fix url file for Windows */
        $base = preg_replace('#^file:\/\/([^/])#', 'file:///\1', $base);

        /**
         * @var mixed[]
         *
         * parse base URL and convert to local variables:
         * $scheme, $host, $path
         */
        $parts = parse_url($base);

        $path = '/' === $rel[0]
            ? ''  // destroy path if relative url points to root
            : ( // remove non-directory element from path
                isset($parts['path'])
                    ? preg_replace(
                        '#/[^/]*$#',
                        '',
                        (string) $parts['path']
                    )
                    : ''
            );

        return static::resolveRelativeUrlToAbsoluteUrl($rel, $path, $parts);
    }

    /**
     * @param array<string, string> $parts
     */
    protected static function resolveRelativeUrlToAbsoluteUrl(string $rel, string $path, array $parts): string
    {
        /* Build absolute URL */
        $abs = '';

        if (isset($parts['host'])) {
            $abs .= $parts['host'];
        }

        if (isset($parts['port'])) {
            $abs .= ':' . $parts['port'];
        }

        $abs .= $path . '/' . $rel;
        $abs = static::replaceSuperfluousSlashes($abs);

        if (isset($parts['scheme'])) {
            $abs = $parts['scheme'] . '://' . $abs;
        }

        return $abs;
    }

    /**
     * replace superfluous slashes with a single slash.
     * covers:.
     * //
     * /./
     * /foo/../.
     */
    protected static function replaceSuperfluousSlashes(string $abs): string
    {
        /* Use realpath to deal with multiple levels if the path exists */
        $rp = realpath($abs);
        if ($rp) {
            return $rp;
        }

        $n = 1;
        do {
            $abs = preg_replace(
                '#(?:(?:/\.?/)|(?!\.\.)[^/]+/\.\./)#',
                '/',
                $abs,
                -1,
                $n
            );
        } while (0 < $n);

        return $abs;
    }
}
