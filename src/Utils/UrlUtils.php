<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Utils;

class UrlUtils
{
    public static function resolveRelativeUrl(string $base, string $rel) : string
    {
        if (!$rel) {
            return $base;
        } elseif (
        /* return if already absolute URL */
            parse_url($rel, PHP_URL_SCHEME) !== null ||
            substr($rel, 0, 2) === '//'
        ) {
            return $rel;
        } elseif (
        /* queries and anchors */
            in_array(
                $rel[0],
                [
                    '#',
                    '?',
                ]
            )
        ) {
            return $base.$rel;
        }

        return static::resolveRelativeUrlAfterEarlyChecks($base, $rel);
    }

    /**
     * @param string $base
     * @param string $rel
     *
     * @return string
     */
    protected static function resolveRelativeUrlAfterEarlyChecks(string $base, string $rel) : string
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

        return static::resolveRelativeUrlToAbsoluteUrl(
            $rel,
            (
                $rel[0] === '/'
                    ? ''  // destroy path if relative url points to root
                    : ( // remove non-directory element from path
                        isset($parts['path'])
                            ? preg_replace(
                                '#/[^/]*$#',
                                '',
                                (string) $parts['path']
                            )
                            : ''
                    )
            ),
            $parts
        );
    }

    protected static function resolveRelativeUrlToAbsoluteUrl(
        string $rel,
        string $path,
        array $parts
    ) : string {
        /* Build absolute URL */
        $abs = '';

        if (isset($parts['host'])) {
            $abs .= (string) $parts['host'];
        }

        if (isset($parts['port'])) {
            $abs .= ':'.(string) $parts['port'];
        }

        $abs .= $path.'/'.$rel;
        $abs = static::replaceSuperfluousSlashes($abs);

        if (isset($parts['scheme'])) {
            $abs = (string) $parts['scheme'].'://'.$abs;
        }

        return $abs;
    }

    /**
     * replace superfluous slashes with a single slash.
     * covers:
     * //
     * /./
     * /foo/../.
     */
    protected static function replaceSuperfluousSlashes(string $abs) : string
    {
        $n = 1;
        do {
            $abs = preg_replace(
                '#(?:(?:/\.?/)|(?!\.\.)[^/]+/\.\./)#',
                '/',
                $abs,
                -1,
                $n
            );
        } while ($n > 0);

        return $abs;
    }
}
