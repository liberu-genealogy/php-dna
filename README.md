# php-dna

## Running MatchKits from the Command Line

To run the MatchKits script from the command line, navigate to the root directory of the php-dna project.

Ensure you have PHP installed on your system. You can check this by running `php -v` in your command line. If PHP is not installed, please install it from the official PHP website.

Execute the script by running the following command: `php src/MatchKits.php`.

The script will prompt you to enter the file paths for Kit 1 and Kit 2. Please enter the full path for each file when prompted.

After entering the file paths, the script will process the data and generate a matched data visualization. The output file named 'matched_data.png' will be saved in the root directory.

## Requirements

* php-dna 1.0+ requires PHP 8.3 (or later).

## Installation

There are two ways of installing php-dna.

### Composer

To install php-dna in your project using composer, simply add the following require line to your project's `composer.json` file:

    {
        "require": {
            "laravel-liberu/php-dna": "1.0.*"
        }
    }

### Download and __autoload

If you are not using composer, you can download an archive of the source from GitHub and extract it into your project. You'll need to setup an autoloader for the files, unless you go through the painstaking process if requiring all the needed files one-by-one. Something like the following should suffice:

```php
spl_autoload_register(function ($class) {
    $pathToDna = __DIR__ . '/library/'; // TODO FIXME

    if (!substr(ltrim($class, '\\'), 0, 7) == 'Dna\\') {
        return;
    }

    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($pathToDna . $class)) {
        require_once($pathToDna . $class);
    }
});
```
