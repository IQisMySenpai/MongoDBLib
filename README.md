# MongoDBLib
## Table of Contents
1. [About The Project](#about-the-project)
    - [Built With](#built-with)
2. [Getting Started](#getting-started)

## About The Project
A PHP Library to handle the connection to the MongoDB database. It also contains functions to perform common database operations, which were simplified to make them easier to use.

### Built With
* PHP 8.0

## Getting Started
I defined my config data in its own php file. The contents of the `config.php` file are as follows:
```php
<?php
return [
    'db_username'=> 'myUserName',
    'db_password'=> 'mySecretPassword',
    'db_domain'=> 'myDomain',
    'db_name'=> 'databaseIWantToAccess'
];
```

I can then use the library as follows:
```php
<?php
require_once __DIR__ . '/includes/mongodb_lib.php';
$config = require_once __DIR__ . '/includes/config.php';

$mongo = new MongoDBLib($config);

$result = $mongo->find('products');
print_r($result);
```