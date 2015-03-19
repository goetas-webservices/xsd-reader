<?php
use Sami\Sami;
use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in('src')
;


return new Sami($iterator, array(
    'theme'                => 'enhanced',
    'title'                => 'GoetasWebservices XSD Reader',
    'build_dir'            => __DIR__.'/apidoc',
    'include_parent_data'  => true,
    'default_opened_level' => 4,
));