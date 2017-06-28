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

    public function camelCaseStringCallback($value)
    {
        return trim(preg_replace_callback('/((?:^|[A-Z])[a-z]+)/',
            array($this, 'splitCamelCaseByDashes'), $value[1]), '-') . '/';
    }

    public function camelCaseStringCallbackModule($value)
    {
        return $this->composerPath[self::MODULE] . $this->camelCaseStringCallback($value);
    }

    public function camelCaseStringCallbackAdminhtmlDesign($value)
    {
        return $this->composerPath[self::ADMINHTML_DESIGN] . $this->camelCaseStringCallback($value);
    }

    public function camelCaseStringCallbackFrontendDesign($value)
    {
        return $this->composerPath[self::FRONTEND_DESIGN] . $this->camelCaseStringCallback($value);
    }

    public function camelCaseStringCallbackLibrary($value)
    {
        return $this->composerPath[self::LIBRARY] . $this->camelCaseStringCallback($value);
    }

    public function splitCamelCaseByDashes($value)
    {
        return '-' . strtolower($value[0]);
    }

    protected function convertGitToComposer(&$content)
    {
        foreach ($this->nonComposerPath as $type => $path) {
            $content = preg_replace_callback('/' . addcslashes($path, '/') . '([A-z0-9\-]+)?\//',
                array($this, 'camelCaseStringCallback' . $type), $content);
        }

        return $content;
    }

    protected function convertDashedStringToCamelCase($string)
    {
        return str_replace('-', '', ucwords($string, '-'));
    }

    protected function dashedStringCallbackModule($matches)
    {
        return $this->nonComposerPath[self::MODULE] . $this->convertDashedStringToCamelCase($matches[1]);
    }

    protected function dashedStringCallbackAdminhtmlDesign($matches)
    {
        return $this->nonComposerPath[self::ADMINHTML_DESIGN] . $matches[1];
    }

    protected function dashedStringCallbackFrontendDesign($matches)
    {
        return $this->nonComposerPath[self::FRONTEND_DESIGN] . $matches[1];
    }

    protected function dashedStringCallbackLibrary($matches)
    {
        return $this->nonComposerPath[self::LIBRARY] . $this->convertDashedStringToCamelCase($matches[1]);
    }

    protected function convertComposerToGit(&$content)
    {
        foreach ($this->composerPath as $type => $path) {
            $content = preg_replace_callback('~' . $path . '([-\w]+)~',
                array($this, 'dashedStringCallback' . $type), $content);
        }

        return $content;
    }

    protected function convert($filename)
    {
        $content = file_get_contents($filename);

        if (!isset($this->options['r'])) {
            echo $this->convertGitToComposer($content);
        } else {
            echo $this->convertComposerToGit($content);
        }

        exit(0);
    }
}

new Converter($argv);
