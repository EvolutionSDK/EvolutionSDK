YAML Bundle (Port of [SPYC](http://code.google.com/p/spyc/))
==========================
Spyc is a YAML loader/dumper written in pure PHP. Given a YAML document, Spyc will return an array which you can use however you see fit. Given an array, Spyc will return a string which contains a YAML document built from your data.

YAML is an amazingly human friendly and strikingly versatile data serialization language which can be used for log files, config files, custom protocols, the works. For more information, see http://www.yaml.org.

Evolution Port Details
======================
The Evolution port of SPYC Has all the basic support of SPYC plus caching of the YAML to base64 serialized files. Using the Evolution Caching Bundle, as well as last modified data.

Usage
=====
There are a number of functions available for use in the YAML Bundle. Notice: If you are farmilear with the SPYC Library most of the functions you are farmilear with have been wrapped for use in Evolution as well as have new functions that were not available before.

## Parsing a File

This will return a array of the YAML contents, or the filename on failure.

```php
<?php

e::$yaml->file($file);
```

## Parsing a String

This will return an array of the YAML contents, or the string on failure.

```php
<?php

e::$yaml->string($string);
```

## Saving a YAML File

Use this function to save an array to a yaml file, throws `Exception` on failure.

```php
<?php

e::$yaml->save($file, $array);
```

## Render as YAML

Use this function to turn an array into a dumpable YAML string

```php
<?php

e::$yaml->dump($array);
```

## File Information Functions

To determine if a file has changed since its last cache was saved.

```php
<?php

e::$yaml->is_changed($yaml_file);
```

To determine the last modified date of the YAML file or Cache file

```php
<?php

e::$yaml->last_modified($yaml_file);
```
