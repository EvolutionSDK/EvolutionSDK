Environment Bundle
==================
This bundle stores all environment configuration. This is used primaraly for various configuration settings for bundles. Such as SQL Connection Strings.

Usage
=====
Require that a configuration item exists, and then retrieve it. Where variable is a string with the name of the variable (We recomend using bundle.var.subvar). Regex is the required format of the string that will be stored. Why is a general description to the show the person setting up your application to explain why the variable is required.

```php
<?php

e::$environment->requireVar($variable, $regex, $why);
```

Get a stored environment variable. Where variable is a string with the name of the variable (We recomend using bundle.var.subvar). This function returns null if the variable is not present rather then requesting it be added. This is better for production systems, however it will break code if a variable is required for operation.

```php
<?php

e::$environment->getVar($variable, $regex, $why);
```
