#!/usr/bin/env php
<?php

/**
 * Compatibility confirmed for Magento <= 2.2.4
 *
 * Class Converter
 */
class Converter
{
    const MODULE                = 'Module';
    const ADMINHTML_DESIGN      = 'AdminhtmlDesign';
    const FRONTEND_DESIGN       = 'FrontendDesign';
    const LIBRARY_AMPQ          = 'LibraryAmpq';
    const LIBRARY_BULK          = 'LibraryBulk';
    const LIBRARY_FOREIGN_KEY   = 'LibraryForeignKey';
    const LIBRARY_MESSAGE_QUEUE = 'LibraryMessageQueue';
    const LIBRARY               = 'Library';

    protected $nonComposerPath  = [
        self::MODULE                => 'app/code/Magento/',
        self::ADMINHTML_DESIGN      => 'app/design/adminhtml/Magento/',
        self::FRONTEND_DESIGN       => 'app/design/frontend/Magento/',
        self::LIBRARY_AMPQ          => 'lib/internal/Magento/Framework/Amqp/',
        self::LIBRARY_BULK          => 'lib/internal/Magento/Framework/Bulk/',
        self::LIBRARY_FOREIGN_KEY   => 'lib/internal/Magento/Framework/ForeignKey/',
        self::LIBRARY_MESSAGE_QUEUE => 'lib/internal/Magento/Framework/MessageQueue/',
        self::LIBRARY               => 'lib/internal/Magento/Framework/'
    ];
    protected $composerPath     = [
        self::MODULE                => 'vendor/magento/module-',
        self::ADMINHTML_DESIGN      => 'vendor/magento/theme-adminhtml-',
        self::FRONTEND_DESIGN       => 'vendor/magento/theme-frontend-',
        self::LIBRARY_AMPQ          => 'vendor/magento/framework-ampq/',
        self::LIBRARY_BULK          => 'vendor/magento/framework-bulk/',
        self::LIBRARY_FOREIGN_KEY   => 'vendor/magento/framework-foreign-key/',
        self::LIBRARY_MESSAGE_QUEUE => 'vendor/magento/framework-message-queue/',
        self::LIBRARY               => 'vendor/magento/framework/'
    ];

    protected $options;

    public function __construct($params = [])
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
        $this->options = getopt('rh', ['help']);
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
        return trim(preg_replace_callback('/((?:^|[A-Z])[a-z]+)/', [$this, 'splitCamelCaseByDashes'], $value), '-');
    }

    public function splitCamelCaseByDashes($value)
    {
        return '-' . strtolower($value[0]);
    }

    public function g2c(&$content)
    {
        foreach ($this->nonComposerPath as $type => $path) {
            $escapedPath = addcslashes($path, '/');
            $needProcess = in_array($type, [self::MODULE, self::ADMINHTML_DESIGN, self::FRONTEND_DESIGN]);

            /**
             * Example:
             * (     1     )                 (    2    )(        3          )                 (    4    )(       5        )
             * diff --git a/app/code/Magento/SomeModule/Some/Path/File.ext b/app/code/Magento/SomeModule/Some/Path/File.ext
             *
             * (     1     )                                          ()(     3     )                                          ()(    5   )
             * diff --git a/lib/internal/Magento/Framework/MessageQueue/Config.php b/lib/internal/Magento/Framework/MessageQueue/Config.php
             */
            $content = preg_replace_callback(
                '~(^diff --git\s+(?:a\/)?)' . $escapedPath . '([-\w]+\/)?([^\s]+\s+(?:b\/)?)' . $escapedPath . '([-\w]+\/)?([^\s]+)$~m',
                function ($matches) use ($type, $needProcess) {
                    return $matches[1] . $this->composerPath[$type]
                        . ($needProcess ? $this->camelCaseToDashedString($matches[2]) : $matches[2])
                        . $matches[3] . $this->composerPath[$type]
                        . ($needProcess ? $this->camelCaseToDashedString($matches[4]) : $matches[4])
                        . $matches[5];
                },
                $content
            );

            // (  1 )                 (    2   )
            // +++ b/app/code/Magento/SomeModule...
            $content = preg_replace_callback(
                '~(^(?:---|\+\+\+|Index:)\s+(?:a\/|b\/)?)' . $escapedPath . '([-\w]+)~m',
                function ($matches) use ($type, $needProcess) {
                    return $matches[1] . $this->composerPath[$type]
                        . ($needProcess ? $this->camelCaseToDashedString($matches[2]) : $matches[2]);
                },
                $content
            );

            // (     1     )                (    2   )
            // rename from app/code/Magento/SomeModule...
            $content = preg_replace_callback (
                '~(^rename\s+(?:from|to)\s+)' . $escapedPath . '([-\w]+)~m',
                function ($matches) use ($type, $needProcess) {
                    return $matches[1] . $this->composerPath[$type]
                        . ($needProcess ? $this->camelCaseToDashedString($matches[2]) : $matches[2]);
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
            $needProcess = $type != self::FRONTEND_DESIGN && $type != self::ADMINHTML_DESIGN;

            /**
             * Example:
             * (     1     )               (        2        )(         3         )               (        4        )(       5        )
             * diff --git a/vendor/magento/module-some-module/Some/Path/File.ext b/vendor/magento/module-some-module/Some/Path/File.ext
             *
             * (     1     )                                     ()(     3     )                                     ()(    5   )
             * diff --git a/vendor/magento/framework-message-queue/Config.php b/vendor/magento/framework-message-queue/Config.php
             */
            $content = preg_replace_callback(
                '~(^diff --git\s+(?:a\/)?)' . $escapedPath . '([-\w]+\/)?([^\s]+\s+(?:b\/)?)' . $escapedPath . '([-\w]+\/)?([^\s]+)$~m',
                function ($matches) use ($type, $needProcess) {
                    return $matches[1] . $this->nonComposerPath[$type]
                        . ($needProcess ? $this->dashedStringToCamelCase($matches[2]) : $matches[2])
                        . $matches[3] . $this->nonComposerPath[$type]
                        . ($needProcess ? $this->dashedStringToCamelCase($matches[4]) : $matches[4])
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
                        . ($needProcess ? $this->dashedStringToCamelCase($matches[2]) : $matches[2]);
                },
                $content
            );

            // (     1     )                (        2       )
            // rename from vendor/magento/module-some-module...
            $content = preg_replace_callback (
                '~(^rename\s+(?:from|to)\s+)' . $escapedPath . '([-\w]+)~m',
                function ($matches) use ($type, $needProcess) {
                    return $matches[1] . $this->nonComposerPath[$type]
                        . ($needProcess ? $this->dashedStringToCamelCase($matches[2]) : $matches[2]);
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
