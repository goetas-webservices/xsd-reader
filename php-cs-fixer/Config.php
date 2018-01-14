<?php

declare(strict_types=1);
/**
 * @author SignpostMarv
 */

namespace GoetasWebservices\CS;

use PhpCsFixer\Config as BaseConfig;
use PhpCsFixer\Finder as DefaultFinder;

class Config extends BaseConfig
{
    public const DEFAULT_RULES = [
        '@Symfony' => true,
        '@PHP71Migration' => true,
        'declare_strict_types' => true,
        'yoda_style' => false,
        'phpdoc_to_comment' => false, // required for type hinting
        'phpdoc_var_without_name' => false, // required for type hinting
    ];

    public function __construct(array $inPaths)
    {
        parent::__construct(
            str_replace(
                '\\',
                ' - ',
                preg_replace(
                    '/([a-z0-9])([A-Z])/',
                    '$1 $2',
                    static::class
                )
            )
        );

        $this->setUsingCache(true);
        $this->setRules(static::DEFAULT_RULES);

        /**
         * @var DefaultFinder $finder
         */
        $finder = $this->getFinder();
        $this->setFinder(array_reduce(
            $inPaths,
            function (DefaultFinder $finder, $directory) {
                if (is_file($directory) === true) {
                    return $finder->append([$directory]);
                }

                return $finder->in($directory);
            },
            $finder->ignoreUnreadableDirs()
        ));
    }

    /**
     * Resolve rules at runtime.
     */
    protected static function RuntimeResolveRules()
    {
        return static::DEFAULT_RULES;
    }
}
