# Magento 2: Patch converter for code which was installed from composer 

## Description
Generates patches that can be applied for Magento 2 which was installed from the composer.

## Requirements
* PHP-CLI 5.3+

## Installation
* Clone this repository into your own folder
```
git clone https://github.com/isitnikov/m2-convert-patch-for-composer-install.git
```
* (Optional; for *nix systems) create symlink for converter into the bin folder
```
ln -s /path/to/m2-convert-patch-for-composer-install/convert-for-composer.php ~/bin/
chmod +x ~/bin/convert-for-composer.php
```

## Usage
```
Usage: php -f converter-for-composer.php [file ...|help] [> new-file]
    converter-for-composer.php [file ...|help] [> new-file]

    file        path to PATCH file which contains pathes like app/code/Magento,
                that is in case when Magento 2 was installed without help of composer
    help        this help
```
