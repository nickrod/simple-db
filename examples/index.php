<?php

//

use simpledb\SimpleDb;

//

require __DIR__ . '/../vendor/autoload.php';

//

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//

try
{
  $site_config = new Config(['db_settings' => '/usr/local/']);

  // settings

  //

  $site_config->getPdo()->beginTransaction();

  // settings

  //

  $site_config->getPdo()->commit();
}
catch (PDOException $e)
{
  $site_config->getPdo()->rollback();
  echo 'Caught PDO exception: ' . $e->getMessage() . PHP_EOL;
}
catch (OpenOrderException $e)
{
  echo 'Caught OpenOrder exception: ' . $e->getMessage() . PHP_EOL;
}
