<?php
namespace GoetasWebservices\XML\XSDReader\Utils;

class UrlUtils
{

    public static function resolveRelativeUrl($base, $rel)
    {
        $re = array(
            '#(/\.?/)#',
            '#/(?!\.\.)[^/]+/\.\./#'
        );


        if (!$rel) {
            return $base;
        }

        /* return if already absolute URL */
        if (parse_url($rel, PHP_URL_SCHEME) !== null || substr($rel, 0, 2) === '//') {
            return $rel;
        }

        /* queries and anchors */
        if ($rel[0] === '#' || $rel[0] === '?') {
            return $base.$rel;
        }

        /*
         * parse base URL and convert to local variables:
         * $scheme, $host, $path
         */
        $parts = parse_url($base);

        /* remove non-directory element from path */
        $path = isset($parts['path']) ? preg_replace('#/[^/]*$#', '', $parts["path"]) : '';

        /* destroy path if relative url points to root */
        if ($rel[0] === '/') {
            $path = '';
        }

        /* Build absolute URL */
        $abs = '';

        if (isset($parts["host"])) {
            $abs .= $parts['host'];
        }

        if (isset($parts["port"])) {
            $abs .= ":".$parts["port"];
        }

        $abs .= $path."/".$rel;

        /* replace '//' or '/./' or '/foo/../' with '/' */
        $n = 1;
        do {
            $abs = preg_replace($re, '/', $abs, -1, $n);
        } while ($n > 0);

        if (isset($parts["scheme"])) {
            $abs = $parts["scheme"].'://'.$abs;
        }

        return $abs;
    }

}
