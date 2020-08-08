<?php

//

namespace simpledb;

//

interface SimpleDbInterface
{
  public function save(\PDO $pdo);
  public function edit(\PDO $pdo);
  public function remove(\PDO $pdo);
}
