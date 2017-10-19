<?php
namespace GoetasWebservices\XML\XSDReader\Utils;

class UrlUtils
{
    /**
    * @param string $base
    * @param string $rel
    *
    * @return string
    */
    public static function resolveRelativeUrl($base, $rel)
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
                    '?'
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
    protected static function resolveRelativeUrlAfterEarlyChecks($base, $rel)
    {
        /* fix url file for Windows */
        $base = preg_replace('#^file:\/\/([^/])#', 'file:///\1', $base);

        /*
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
                            ? preg_replace('#/[^/]*$#', '', $parts["path"])
                            : ''
                    )
            ),
            $parts
        );
    }

    /**
    * @param string $rel
    * @param string $path
    *
    * @return string
    */
    protected static function resolveRelativeUrlToAbsoluteUrl(
        $rel,
        $path,
        array $parts
    ) {
        /* Build absolute URL */
        $abs = '';

        if (isset($parts["host"])) {
            $abs .= $parts['host'];
        }

        if (isset($parts["port"])) {
            $abs .= ":".$parts["port"];
        }

        $abs .= $path."/".$rel;
        $abs = static::replaceSuperfluousSlashes($abs);

        if (isset($parts["scheme"])) {
            $abs = $parts["scheme"].'://'.$abs;
        }

        return $abs;
    }

    /**
        * replace superfluous slashes with a single slash.
        * covers:
        * //
        * /./
        * /foo/../
        *
        * @param string $abs
        *
        * @return string
        */
    protected static function replaceSuperfluousSlashes($abs) {
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
