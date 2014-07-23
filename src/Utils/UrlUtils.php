<?php
namespace Goetas\XML\XSDReader\Utils;

class UrlUtils
{

    public static function resolveRelativeUrl($base, $rel)
    {
        /* return if already absolute URL */
        if (parse_url($rel, PHP_URL_SCHEME) != '' || substr($rel, 0, 2) == '//') {
            return $rel;
        }

        /* queries and anchors */
        if ($rel[0] == '#' || $rel[0] == '?') {
            return $base . $rel;
        }

        /*
         * parse base URL and convert to local variables:
         * $scheme, $host, $path
         */
        $parts = parse_url($base);

        /* remove non-directory element from path */
        $path = preg_replace('#/[^/]*$#', '', $parts["path"]);

        /* destroy path if relative url points to root */
        if ($rel[0] == '/') {
            $path = '';
        }

        /* dirty absolute URL */
        $abs = $parts["host"].(isset($parts["port"])?(":".$parts["port"]):"").$path."/".$rel;

        /* replace '//' or '/./' or '/foo/../' with '/' */
        $re = array(
            '#(/\.?/)#',
            '#/(?!\.\.)[^/]+/\.\./#'
        );
        $n = 1;
        do {
            $abs = preg_replace($re, '/', $abs, - 1, $n);
        } while ($n > 0);

        /* absolute URL is ready! */
        return $parts["scheme"] . '://' . $abs;
    }

}
