# Magento 2: Patch converter for code which was installed from composer 

## Description
Generates patches that can be applied for Magento 2 which was installed from the composer.

## Requirements
* PHP-CLI 5.4+

## Installation
* Clone this repository into your own folder
```
git clone https://github.com/isitnikov/m2-convert-patch-for-composer-install.git
```
* (Optional; for *nix systems) create symlink for converter into the bin folder
```
curl -o m2-convert-for-composer https://raw.githubusercontent.com/isitnikov/m2-convert-patch-for-composer-install/master/convert-for-composer.php
ln -s `pwd`/m2-convert-for-composer ~/bin/
chmod +x ~/bin/m2-convert-for-composer
```

## Usage
```
Usage: php -f converter-for-composer.php [options] file [> new-file]
    converter-for-composer.php [options] file [> new-file]

    file        path to source PATCH file

[options]
    -h, --help  Show help
    -r          Reverse mode. Convert composer format back to git
```
