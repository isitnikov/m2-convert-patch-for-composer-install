#!/usr/bin/env php
<?php
class Converter {
    protected $nonComposerPath = 'app/code/Magento';
    protected $composerPath    = 'vendor/magento/module-';

    public function __construct($params = array())
    {
        if (!isset($params[1])) {
            print("Error! Input file is not specified. Type 'help' for support.\n") ;
            exit(1);
        }

        $filename = $params[1];

        if ($filename == 'help') {
            echo <<<HELP_TEXT
Usage: php -f converter-for-composer.php [file ...|help] [> new-file]
    converter-for-composer.php [file ...|help] [> new-file]

    file        path to PATCH file which contains pathes like app/code/Magento,
                that is in case when Magento 2 was installed without help of composer
    help        this help

HELP_TEXT;
            exit(0);
        }

        if (!file_exists($filename)) {
            printf("Error! File %s does not exist.\n", $filename);
            exit(1);
        }

        $content = file_get_contents($filename);
        echo $this->replaceContent($content);
        exit(0);
    }

    public function camelCaseStringCallback($value)
    {
        return $this->composerPath . trim(preg_replace_callback('/((?:^|[A-Z])[a-z]+)/',
            array($this, 'splitCamelCaseByDashes'), $value[1]), '-') . '/';
    }

    public function splitCamelCaseByDashes($value)
    {
        return '-' . strtolower($value[0]);
    }

    protected function replaceContent(&$fileContent)
    {
        return preg_replace_callback('/' . addcslashes($this->nonComposerPath, '/') . '\/([A-z0-9\-]+)?\//',
            array($this, 'camelCaseStringCallback'), $fileContent);
    }
}

new Converter($argv);