<?php

//

declare(strict_types=1);

//

namespace simpledb;

//

class SimpleDb implements SimpleDbInterface
{
  // save

  public function save(\PDO $pdo): void
  {
    $column = (defined(get_class($this) . '::COLUMN')) ? $this::COLUMN : [];
    $table = (defined(get_class($this) . '::TABLE')) ? $this::TABLE : null;
    $table_seq = (defined(get_class($this) . '::TABLE_SEQ')) ? $this::TABLE_SEQ : null;
    $column_value['allowed'] = get_object_vars($this);
    $column_key = &self::column($column, $column_value, $table);

    //

    if ($table !== null && isset($column_key['allowed_keys']) && isset($column_key['allowed_values']))
    {
      $statement = $pdo->prepare('INSERT INTO ' . $table . ' (' . $column_key['allowed_keys'] . ') VALUES (' . $column_key['allowed_values'] . ')');
      $statement->execute($column_key['value']);

      // get last inserted id for auto increment

      if ($table_seq !== null)
      {
        $this->setId((int) $pdo->lastInsertId(($pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'pgsql') ? $table_seq : null));
      }
    }
  }

  // edit

  public function edit(\PDO $pdo): void
  {
    $column = (defined(get_class($this) . '::COLUMN')) ? $this::COLUMN : [];
    $table = (defined(get_class($this) . '::TABLE')) ? $this::TABLE : null;
    $column_value['key'] = $column_value['allowed'] = get_object_vars($this);
    $column_key = &self::column($column, $column_value, $table);

    //

    if ($table !== null && isset($column_key['key']) && isset($column_key['allowed']))
    {
      $statement = $pdo->prepare('UPDATE ' . $table . ' SET ' . $column_key['allowed'] . ' WHERE ' . $column_key['key']);
      $statement->execute($column_key['value']);
    }
  }

  // remove

  public function remove(\PDO $pdo): void
  {
    $column = (defined(get_class($this) . '::COLUMN')) ? $this::COLUMN : [];
    $table = (defined(get_class($this) . '::TABLE')) ? $this::TABLE : null;
    $column_value['key'] = get_object_vars($this);
    $column_key = &self::column($column, $column_value, $table);

    //

    if ($table !== null && isset($column_key['key']))
    {
      $statement = $pdo->prepare('DELETE FROM ' . $table . ' WHERE ' . $column_key['key']);
      $statement->execute($column_key['value']);
    }
  }

  // get object

  public static function getObject(\PDO $pdo, array $option): ?object
  {
    $column = (defined(static::class . '::COLUMN')) ? (static::class)::COLUMN : [];
    $table = (defined(static::class . '::TABLE')) ? (static::class)::TABLE : null;
    $table_key = (defined(static::class . '::TABLE_KEY')) ? (static::class)::TABLE_KEY : null;
    $column_value = $column_key = [];

    //

    if (isset($option['index']))
    {
      $column_value['index'] = $option['index'];
    }

    //

    if (isset($option['join']))
    {
      $column_value['join'] = $option['join'];
    }

    //

    if ($column_value !== [])
    {
      $column_key = &self::column($column, $column_value, $table, $table_key);
    }

    //

    $join = (isset($column_key['join'])) ? ' ' . $column_key['join'] : '';
    $statement = null;

    //

    if ($table !== null && isset($column_key['index']))
    {
      $statement = $pdo->prepare('SELECT ' . $table . '.* FROM ' . $table . $join . ' WHERE ' . $column_key['index'] . ' LIMIT 1');
      $statement->execute($column_key['value']);
      $return_object = $statement->fetchObject(static::class);

      //

      return (($return_object !== null && $return_object !== false) ? $return_object : null);
    }
    else
    {
      return null;
    }
  }

  // get list of objects

  public static function &getList(\PDO $pdo, array $option, bool $assoc = false): ?array
  {
    $column = (defined(static::class . '::COLUMN')) ? (static::class)::COLUMN : [];
    $table = (defined(static::class . '::TABLE')) ? (static::class)::TABLE : null;
    $table_key = (defined(static::class . '::TABLE_KEY')) ? (static::class)::TABLE_KEY : null;
    $limit = (isset($option['limit'])) ? (int) $option['limit'] : 100;
    $limit = ($limit > 10000) ? 10000 : $limit;
    $offset = (isset($option['offset'])) ? (int) $option['offset'] : 0;
    $distinct = (isset($option['distinct'])) ? (bool) $option['distinct'] : false;
    $column_value = $column_key = [];

    //

    if (isset($option['index']))
    {
      $column_value['index'] = $option['index'];
    }

    //

    if (isset($option['index_not']))
    {
      $column_value['index_not'] = $option['index_not'];
    }

    //

    if (isset($option['index_gt']))
    {
      $column_value['index_gt'] = $option['index_gt'];
    }

    //

    if (isset($option['index_lt']))
    {
      $column_value['index_lt'] = $option['index_lt'];
    }

    //

    if (isset($option['search']))
    {
      $column_value['search'] = $option['search'];
    }

    //

    if (isset($option['filter']))
    {
      $column_value['filter'] = $option['filter'];
    }

    //

    if (isset($option['join']))
    {
      $column_value['join'] = $option['join'];
    }

    //

    if (isset($option['order_by']))
    {
      $column_value['order_by'] = $option['order_by'];
    }

    //

    if ($column_value !== [])
    {
      $column_key = &self::column($column, $column_value, $table, $table_key);
    }

    //

    $index = (isset($column_key['index'])) ? ' AND ' . $column_key['index'] : '';
    $search = (isset($column_key['search'])) ? ' AND (' . $column_key['search'] . ')' : '';
    $filter = (isset($column_key['filter'])) ? ' AND ' . $column_key['filter'] : '';
    $join = (isset($column_key['join'])) ? ' ' . $column_key['join'] : '';
    $order_by = (isset($column_key['order_by'])) ? ' ORDER BY ' . $column_key['order_by'] : '';
    $statement = $return_object = null;

    //

    if ($table !== null)
    {
      if ($index !== '' || $search !== '' || $filter !== '')
      {
        if ($distinct === true)
        {
          $statement = $pdo->prepare('SELECT ' . $table . '.* FROM ' . $table . ' WHERE ' . $table . '.' . $table_key . ' IN (SELECT ' . $table . '.' . $table_key . ' FROM ' . $table . $join . ' WHERE TRUE' . $index . $search . $filter . ' GROUP BY ' . $table . '.' . $table_key . ')' . $order_by . ' LIMIT ' . $limit . ' OFFSET ' . $offset);
        }
        else
        {
          $statement = $pdo->prepare('SELECT ' . $table . '.* FROM ' . $table . $join . ' WHERE TRUE' . $index . $search . $filter . $order_by . ' LIMIT ' . $limit . ' OFFSET ' . $offset);
        }

        //

        $statement->execute($column_key['value']);
      }
      else
      {
        $statement = $pdo->query('SELECT ' . $table . '.* FROM ' . $table . $join . $order_by . ' LIMIT ' . $limit . ' OFFSET ' . $offset);
      }

      //

      if ($assoc === false)
      {
        $return_object = $statement->fetchAll(\PDO::FETCH_CLASS, static::class);
      }
      else
      {
        $return_object = $statement->fetchAll(\PDO::FETCH_ASSOC);
      }

      //

      $return_object = (($return_object !== null && $return_object !== false && $return_object !== []) ? $return_object : null);

      //

      return $return_object;
    }
    else
    {
      return null;
    }
  }

  // check if item exists

  public static function exists(\PDO $pdo, array $option): bool
  {
    $column = (defined(static::class . '::COLUMN')) ? (static::class)::COLUMN : [];
    $table = (defined(static::class . '::TABLE')) ? (static::class)::TABLE : null;
    $table_key = (defined(static::class . '::TABLE_KEY')) ? (static::class)::TABLE_KEY : null;
    $column_value = $column_key = [];

    //

    if (isset($option['index']))
    {
      $column_value['index'] = $option['index'];
    }

    //

    if (isset($option['join']))
    {
      $column_value['join'] = $option['join'];
    }

    //

    if ($column_value !== [])
    {
      $column_key = &self::column($column, $column_value, $table, $table_key);
    }

    //

    $join = (isset($column_key['join'])) ? ' ' . $column_key['join'] : '';
    $statement = $return_value = null;

    //

    if ($table !== null && isset($column_key['index']))
    {
      $statement = $pdo->prepare('SELECT 1 FROM ' . $table . $join . ' WHERE ' . $column_key['index'] . ' LIMIT 1');
      $statement->execute($column_key['value']);
      $return_value = $statement->fetchColumn();

      //

      return (($return_value !== null && $return_value !== false) ? (bool) $return_value : false);
    }
    else
    {
      return false;
    }
  }

  // total count

  public static function total(\PDO $pdo, array $option): int
  {
    $column = (defined(static::class . '::COLUMN')) ? (static::class)::COLUMN : [];
    $table = (defined(static::class . '::TABLE')) ? (static::class)::TABLE : null;
    $table_key = (defined(static::class . '::TABLE_KEY')) ? (static::class)::TABLE_KEY : null;
    $distinct = (isset($option['distinct'])) ? (bool) $option['distinct'] : false;
    $column_value = $column_key = [];

    //

    if (isset($option['index']))
    {
      $column_value['index'] = $option['index'];
    }

    //

    if (isset($option['index_not']))
    {
      $column_value['index_not'] = $option['index_not'];
    }

    //

    if (isset($option['index_gt']))
    {
      $column_value['index_gt'] = $option['index_gt'];
    }

    //

    if (isset($option['index_lt']))
    {
      $column_value['index_lt'] = $option['index_lt'];
    }

    //

    if (isset($option['search']))
    {
      $column_value['search'] = $option['search'];
    }

    //

    if (isset($option['filter']))
    {
      $column_value['filter'] = $option['filter'];
    }

    //

    if (isset($option['join']))
    {
      $column_value['join'] = $option['join'];
    }

    //

    if ($column_value !== [])
    {
      $column_key = &self::column($column, $column_value, $table, $table_key);
    }

    //

    $index = (isset($column_key['index'])) ? ' AND ' . $column_key['index'] : '';
    $search = (isset($column_key['search'])) ? ' AND (' . $column_key['search'] . ')' : '';
    $filter = (isset($column_key['filter'])) ? ' AND ' . $column_key['filter'] : '';
    $join = (isset($column_key['join'])) ? ' ' . $column_key['join'] : '';
    $statement = $return_value = null;

    //

    if ($table !== null)
    {
      if ($index !== '' || $search !== '' || $filter !== '')
      {
        if ($distinct === true)
        {
          $statement = $pdo->prepare('SELECT COUNT(*) AS total FROM ' . $table . ' WHERE ' . $table . '.' . $table_key . ' IN (SELECT ' . $table . '.' . $table_key . ' FROM ' . $table . $join . ' WHERE TRUE' . $index . $search . $filter . ' GROUP BY ' . $table . '.' . $table_key . ')');
        }
        else
        {
          $statement = $pdo->prepare('SELECT COUNT(*) AS total FROM ' . $table . $join . ' WHERE TRUE' . $index . $search . $filter);
        }

        //

        $statement->execute($column_key['value']);
      }
      else
      {
        $statement = $pdo->query('SELECT COUNT(*) AS total FROM ' . $table . $join);
      }

      //

      $return_value = $statement->fetchColumn();

      //

      return (($return_value !== null && $return_value !== false) ? (int) $return_value : 0);
    }
    else
    {
      return 0;
    }
  }

  // import csv

  public static function import(\PDO $pdo, string $file, string $type = 'save', string $delimiter = ',', string $enclosure = '"'): void
  {
    $row = 1;
    $header = [];

    //

    if (!file_exists($file))
    {
      throw new \InvalidArgumentException('File does not exist: ' . $file);
    }
    elseif ($type !== 'save' && $type !== 'edit' && $type !== 'remove')
    {
      throw new \InvalidArgumentException('Type must be save, edit, or remove: ' . $type);
    }
    elseif (strlen($delimiter) !== 1)
    {
      throw new \InvalidArgumentException('Delimiter must be a single character: ' . $delimiter);
    }
    elseif (strlen($enclosure) !== 1)
    {
      throw new \InvalidArgumentException('Enclosure must be a single character: ' . $enclosure);
    }
    else
    {
      if (($handle = fopen($file, 'r')) !== false)
      {
        while (($data = fgetcsv($handle, 1000, $delimiter, $enclosure)) !== false)
        {
          if ($row === 1)
          {
            $header = $data;
          }
          else
          {
            if ($data = array_combine($header, $data))
            {
              $class_object = static::class;

              //

              if ($type === 'save')
              {
                (new $class_object($data))->save($pdo);
              }
              elseif ($type === 'edit')
              {
                (new $class_object($data))->edit($pdo);
              }
              elseif ($type === 'remove')
              {
                (new $class_object($data))->remove($pdo);
              }
            }
          }

          //

          $row++;
        }

        //

        fclose($handle);
      }
      else
      {
        throw new \InvalidArgumentException('File cannot be read: ' . $file);
      }
    }
  }

  //

  private static function setBoolean(bool $boolean): int
  {
    return (int) $boolean;
  }

  //

  private static function arrayToInt(array &$arr): string
  {
    $str = '';
    $key_count = 0;

    //

    foreach ($arr as $key => $value)
    {
      $int_val = (int) $value;

      //

      if ($int_val > 0)
      {
        $str .= (($key_count === 0) ? '' : ', ') . $int_val;
        $key_count++;
      }
    }

    //

    return $str;
  }

  //

  private static function escapeSearch(string $str): string
  {
    return strtr($str, ['_' => '\_', '%' => '\%', '\\' => '\\\\']);
  }

  //

  private static function replaceKey(string $str): string
  {
    return strtr($str, ['__' => '.']);
  }

  //

  private static function getKey(string $str, string $delimiter, bool $before = true): string
  {
    $key = strstr($str, $delimiter, $before);

    //

    return (($key === false) ? $str : $key);
  }

  //

  private static function &column(array &$column, array &$column_value, ?string $table = null, ?string $table_key = null): array
  {
    $arr = [];
    $index_value = '';
    $key_count = $index_count = $allowed_insert_count = $allowed_edit_count = $search_count = $filter_count = $join_count = $order_by_count = 0;

    //

    foreach ($column_value as $type_value => &$column_type)
    {
      if (is_array($column_type))
      {
        foreach ($column_type as $key => &$value)
        {
          if (!isset($column[$key]))
          {
            throw new \InvalidArgumentException('Column key not found: ' . $key);
          }
          elseif (!isset($column[$key][$type_value]))
          {
            continue;
          }
          elseif ($column[$key][$type_value] !== true)
          {
            continue;
          }
          else
          {
            if ($type_value === 'key')
            {
              if (is_string($value) || is_int($value) || is_float($value) || is_bool($value))
              {
                if (!isset($arr[$type_value])) $arr[$type_value] = null;
                $arr[$type_value] .= (($key_count === 0) ? '' : ' AND ') . $key . ' = :' . $key;
                $arr['value'][$key] = (is_bool($value)) ? self::setBoolean($value) : $value;
                $key_count++;
              }
              else
              {
                continue;
              }
            }
            elseif ($type_value === 'index' || $type_value === 'index_not' || $type_value === 'index_gt' || $type_value === 'index_lt')
            {
              if (is_string($value) || is_int($value) || is_float($value) || is_bool($value))
              {
                if ($type_value === 'index') $index_value = '';
                if ($type_value === 'index_not') $index_value = '!';
                if ($type_value === 'index_gt') $index_value = '>';
                if ($type_value === 'index_lt') $index_value = '<';
                if (!isset($arr['index'])) $arr['index'] = null;
                $arr['index'] .= (($index_count === 0) ? '' : ' AND ') . self::replaceKey($key) . ' ' . $index_value . '= :' . $key;
                $arr['value'][$key] = (is_bool($value)) ? self::setBoolean($value) : $value;
                $index_count++;
              }
              else
              {
                continue;
              }
            }
            elseif ($type_value === 'allowed')
            {
              if (is_string($value) || is_int($value) || is_float($value) || is_bool($value) || is_null($value))
              {
                if (is_string($value) && (trim($value) === '' || trim($value) === '0'))
                {
                  $value = null;
                }

                //

                if ((is_int($value) || is_float($value)) && !($value >= 0))
                {
                  $value = null;
                }

                //

                if (!isset($column[$key]['key']) || $column[$key]['key'] !== true)
                {
                  if (!isset($arr[$type_value])) $arr[$type_value] = null;
                  $arr[$type_value] .= (($allowed_edit_count === 0) ? '' : ', ') . $key . ' = :' . $key;
                  $allowed_edit_count++;
                }

                //

                if (!isset($arr['allowed_keys'])) $arr['allowed_keys'] = null;
                if (!isset($arr['allowed_values'])) $arr['allowed_values'] = null;
                $arr['allowed_keys'] .= (($allowed_insert_count === 0) ? '' : ', ') . $key;
                $arr['allowed_values'] .= (($allowed_insert_count === 0) ? '' : ', ') . ':' . $key;
                $arr['value'][$key] = (is_bool($value)) ? self::setBoolean($value) : $value;
                $allowed_insert_count++;
              }
              else
              {
                continue;
              }
            }
            elseif ($type_value === 'search')
            {
              if (is_string($value) && trim($value) !== '')
              {
                $value = self::escapeSearch($value) . '%';
                if (!isset($arr[$type_value])) $arr[$type_value] = null;
                $arr[$type_value] .= (($search_count === 0) ? '' : ' OR ') . self::replaceKey($key) . ' LIKE = :' . $key;
                $arr['value'][$key] = (is_bool($value)) ? self::setBoolean($value) : $value;
                $search_count++;
              }
              else
              {
                continue;
              }
            }
            elseif ($type_value === 'filter')
            {
              if (is_array($value))
              {
                $in_val = self::arrayToInt($value);
                if (!isset($arr[$type_value])) $arr[$type_value] = null;
                $arr[$type_value] .= (($filter_count === 0) ? '' : ' AND ') . self::replaceKey($key) . ' IN (' . (($in_val !== '') ? $in_val : '0') . ')';
                $filter_count++;
              }
              else
              {
                continue;
              }
            }
            elseif ($type_value === 'join')
            {
              if (is_string($value) && trim($value) !== '' && (strtoupper($value) === 'INNER' || strtoupper($value) === 'LEFT') && $table !== null && $table_key !== null)
              {
                if (!isset($arr[$type_value])) $arr[$type_value] = null;
                $arr[$type_value] .= (($join_count === 0) ? '' : ' ') . $value . ' JOIN ' . self::getKey($key, '__') . ' ON ' . $table . '.' . $table_key . ' = ' . self::replaceKey($key);
                $join_count++;
              }
              else
              {
                continue;
              }
            }
            elseif ($type_value === 'order_by')
            {
              if (is_string($value) && (strtoupper($value) === 'ASC' || strtoupper($value) === 'DESC'))
              {
                if (!isset($arr[$type_value])) $arr[$type_value] = null;
                $arr[$type_value] .= (($order_by_count === 0) ? '' : ', ') . self::replaceKey($key) . ' IS NULL, ' . self::replaceKey($key) . ' ' . strtoupper($value);
                $order_by_count++;
              }
              else
              {
                continue;
              }
            }
            else
            {
              continue;
            }
          }
        }
      }
    }

    //

    return $arr;
  }
}
