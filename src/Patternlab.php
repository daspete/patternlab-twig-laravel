<?php

namespace Daspete\Laravel;

use Twig_Extension;
use Twig_Extension_InitRuntimeInterface;
use Twig_Environment;
use Twig_Loader_Filesystem;
use Twig_Loader_Chain;
use Twig_SimpleFunction;
use Twig_SimpleTest;
use Labcoat\Twig\Loader as LabcoatLoader;
use Labcoat\PatternLab as LabcoatPatternlab;

use Config;

class Patternlab extends Twig_Extension implements Twig_Extension_InitRuntimeInterface {

    public function initRuntime(Twig_Environment $environment){
        parent::initRuntime($environment);

        $config = (object)Config::get('patternlab');

        $loader = $environment->getLoader();

        if(!($loader instanceof Twig_Loader_Chain)){
            $origLoader = $loader;

            $loader = new Twig_Loader_Chain([$loader]);
            $loader->addLoader($origLoader);
        }

        $loader->addLoader(new Twig_Loader_Filesystem(base_path() . '/' . $config->twig_ext_path));
        $loader->addLoader(new Twig_Loader_Filesystem(base_path() . '/' . $config->layout_path));
        $loader->addLoader(new Twig_Loader_Filesystem(base_path() . '/' . $config->views_path));

        $labcoatConfig = Styleguide::getConfig();
        $patternlab = new LabcoatPatternlab($labcoatConfig);
        $labcoatLoader = new LabcoatLoader($patternlab);

        $loader->addLoader($labcoatLoader);

        $environment->setLoader($loader);

        $globalData = $patternlab->getGlobalData();

        foreach($globalData as $key => $value){
            $environment->addGlobal($key, $value);
        }
    }

    public function getFunctions(){
        $config = (object)Config::get('patternlab');
        return $this->loopDir(base_path() . '/' . $config->twig_ext_functions_path . '/', 'Twig_SimpleFunction');
    }

    public function getTests(){
        $config = (object)Config::get('patternlab');
        return $this->loopDir(base_path() . '/' . $config->twig_ext_tests_path . '/', 'Twig_SimpleTest');
    }

    public function getFilters(){
        $config = (object)Config::get('patternlab');
        return $this->loopDir(base_path() . '/' . $config->twig_ext_filters_path . '/', 'Twig_SimpleFilter');
    }

    public function getName(){
        return 'Daspete_Laravel_Patternlab';
    }

    protected function loopDir($path, $class){
        $files = [];
        $filePath = $path;
        $fileDir = dir($filePath);

        while(($file = $fileDir->read()) !== false){
            if(!is_file($filePath . $file)){
                continue;
            }

            $function = include($filePath . $file);

            if($class == 'Twig_SimpleFunction'){
                $files[] = new $class(basename($file, '.php'), $function, ['is_safe' => ['html']]);
            }else{
                $files[] = new $class(basename($file, '.php'), $function);
            }
        }

        return $files;
    }

}