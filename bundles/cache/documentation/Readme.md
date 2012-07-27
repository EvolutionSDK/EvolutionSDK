Caching Bundle
==============
The caching bundle is used for caching files of all types. In E3 we use it primarily for caching LHTML Stacks, and YAML files for use later. Especially since our caching bundle can cache and store whole class stacks!

Usage
=====
Check to see if there is anything cached for the requested value.

```php
<?php

e::$cache->check($library, $key);
```

Get the last modified time of the file in unix timestamp format. Returns false if the variable does not exist.

```php
<?php

e::$cache->timestamp($libary, $key);
```

Return the value of a cached variable. Returns NULL if the variable does not exist.

```php
<?php

e::$cache->get($library, $key);
```

Store a value to the cache.

```php
<?php

e::$cache->store($library, $key, $value);
```

Delete a cache file

```php
<?php

e::$cache->delete($library, $key);
```

Advanced Use
============
There are more functions available for use but are intended for the innerworkings of the bundle. If you feel so inclined to attempt to use them please reference the source.