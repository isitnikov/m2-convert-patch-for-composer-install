#!/usr/bin/env php
<?php
class Converter 
{
    const MODULE            = 'Module';
    const ADMINHTML_DESIGN  = 'AdminhtmlDesign';
    const FRONTEND_DESIGN   = 'FrontendDesign';
    const LIBRARY           = 'Library';

    protected $nonComposerPath = array(
        self::MODULE                => 'app/code/Magento/',
        self::ADMINHTML_DESIGN      => 'app/design/adminhtml/Magento/',
        self::FRONTEND_DESIGN       => 'app/design/frontend/Magento/',
        self::LIBRARY               => 'lib/internal/Magento/'
    );
    protected $composerPath    = array(
        self::MODULE                => 'vendor/magento/module-',
        self::ADMINHTML_DESIGN      => 'vendor/magento/theme-adminhtml-',
        self::FRONTEND_DESIGN       => 'vendor/magento/theme-frontend-',
        self::LIBRARY               => 'vendor/magento/'
    );

    protected $options;

    public function __construct($params = array())
    {
        $filename = $this->parseArgs($params);
        $this->validateFile($filename);
        $this->convert($filename);
    }

    protected function showHelp()
    {
        echo <<<HELP_TEXT
Usage: php -f converter-for-composer.php [options] file [> new-file]
    converter-for-composer.php [options] file [> new-file]

    file        path to source PATCH file

[options]
    -h, --help  Show help
    -r          Reverse mode. Convert composer format back to git

HELP_TEXT;
        exit(0);
    }

    protected function parseArgs($params)
    {
        if (count($params) < 2) {
            $this->showHelp();
        }

        array_shift($params);
        $this->options = getopt('rh', array('help'));
        if (isset($this->options['h']) || isset($this->options['help'])) {
            $this->showHelp();
        }

        $filename = array_pop($params);

        return $filename;
    }

    protected function validateFile($filename)
    {
        if (!file_exists($filename) || is_dir($filename)) {
            printf("Error! File %s does not exist.\n", $filename);
            exit(1);
        }

        if (!is_readable($filename)) {
            printf("Error! Can not read file %s.\n", $filename);
            exit(2);
        }
    }

    public function camelCaseToDashedString($value)
    {
        return trim(preg_replace_callback('/((?:^|[A-Z])[a-z]+)/',
            array($this, 'splitCamelCaseByDashes'), $value), '-');
    }

    public function splitCamelCaseByDashes($value)
    {
        return '-' . strtolower($value[0]);
    }

    public function g2c(&$content)
    {
        foreach ($this->nonComposerPath as $type => $path) {
            $escapedPath = addcslashes($path, '/');

            // (     1     )                 (    2   )(         3          )                 (    4   )(        5        )
            // diff --git a/app/code/Magento/SomeModule/Some/Path/File.ext b/app/code/Magento/SomeModule/Some/Path/File.ext
            $content = preg_replace_callback(
                '~(^diff --git\s+(?:a\/)?)' . $escapedPath . '([-\w]+)(\/[^\s]+\s+(?:b\/)?)' . $escapedPath . '([-\w]+)(\/[^\s]+)$~m',
                function ($matches) use ($type) {
                    return $matches[1] . $this->composerPath[$type]
                        . $this->camelCaseToDashedString($matches[2])
                        . $matches[3] . $this->composerPath[$type]
                        . $this->camelCaseToDashedString($matches[4])
                        . $matches[5];
                },
                $content
            );

            // (  1 )                 (    2   )
            // +++ b/app/code/Magento/SomeModule...
            $content = preg_replace_callback(
                '~(^(?:---|\+\+\+|Index:)\s+(?:a\/|b\/)?)' . $escapedPath . '([-\w]+)~m',
                function ($matches) use ($type) {
                    return $matches[1] . $this->composerPath[$type] . $this->camelCaseToDashedString($matches[2]);
                },
                $content
            );
        }
    }

    public function dashedStringToCamelCase($string)
    {
        return str_replace('-', '', ucwords($string, '-'));
    }

    public function c2g(&$content)
    {
        foreach ($this->composerPath as $type => $path) {
            $escapedPath = addcslashes($path, '/');
            $needProcess = $type == self::MODULE || $type == self::LIBRARY;

            // (     1     )               (        2       )(         3          )               (        4       )(        5        )
            // diff --git a/vendor/magento/module-some-module/Some/Path/File.ext b/vendor/magento/module-some-module/Some/Path/File.ext
            $content = preg_replace_callback(
                '~(^diff --git\s+(?:a\/)?)' . $escapedPath . '([-\w]+)(\/[^\s]+\s+(?:b\/)?)' . $escapedPath . '([-\w]+)(\/[^\s]+)$~m',
                function ($matches) use ($type, $needProcess) {
                    return $matches[1] . $this->nonComposerPath[$type]
                    . $needProcess ? $this->dashedStringToCamelCase($matches[2]) : $matches[2]
                    . $matches[3] . $this->nonComposerPath[$type]
                    . $needProcess ? $this->dashedStringToCamelCase($matches[4]) : $matches[4]
                    . $matches[5];
                },
                $content
            );

            // (  1 )               (        2       )
            // +++ b/vendor/magento/module-some-module...
            $content = preg_replace_callback(
                '~(^(?:---|\+\+\+|Index:)\s+(?:a\/|b\/)?)' . $escapedPath . '([-\w]+)~m',
                function ($matches) use ($type, $needProcess) {
                    return $matches[1] . $this->nonComposerPath[$type]
                        . $needProcess ? $this->dashedStringToCamelCase($matches[2]) : $matches[2];
                },
                $content
            );
        }
    }

    protected function convert($filename)
    {
        $content = file_get_contents($filename);

        if (!isset($this->options['r'])) {
            $this->g2c($content);
        } else {
            $this->c2g($content);
        }

        echo $content;
        exit(0);
    }
}

new Converter($argv);
