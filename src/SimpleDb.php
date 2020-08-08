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
    $column = (isset($this::COLUMN)) ? $this::COLUMN : [];
    $table = (isset($this::TABLE)) ? $this::TABLE : null;
    $table_key = (isset($this::TABLE_KEY)) ? $this::TABLE_KEY : null;
    $column_value = get_object_vars($this);
    $column_type = ['allowed'];
    $column_key = self::column($column_type, $column, $column_value, $table, $table_key);

    //

    if (isset($table) && isset($column_key['allowed_keys']) && isset($column_key['allowed_values']))
    {
      $statement = $pdo->prepare('INSERT INTO ' . $table . ' (' . $column_key['allowed_keys'] . ') VALUES (' . $column_key['allowed_values'] . ')');
      $statement->execute($column_key['value']);

      // get last inserted id for auto increment

      if (isset($table_key))
      {
        $this->setId((int) $pdo->lastInsertId(($pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'pgsql') ? $table . '_' . $table_key . '_seq' : null));
      }
    }
  }

  // edit

  public function edit(\PDO $pdo): void
  {
    $column = (isset($this::COLUMN)) ? $this::COLUMN : [];
    $table = (isset($this::TABLE)) ? $this::TABLE : null;
    $column_value = get_object_vars($this);
    $column_type = ['key', 'allowed'];
    $column_key = self::column($column_type, $column, $column_value, $table);

    //

    if (isset($table) && isset($column_key['key']) && isset($column_key['allowed']))
    {
      $statement = $pdo->prepare('UPDATE ' . $table . ' SET ' . $column_key['allowed'] . ' WHERE ' . $column_key['key']);
      $statement->execute($column_key['value']);
    }
  }

  // remove

  public function remove(\PDO $pdo): void
  {
    $column = (isset($this::COLUMN)) ? $this::COLUMN : [];
    $table = (isset($this::TABLE)) ? $this::TABLE : null;
    $column_value = get_object_vars($this);
    $column_type = ['key'];
    $column_key = self::column($column_type, $column, $column_value, $table);

    //

    if (isset($table) && isset($column_key['key']))
    {
      $statement = $pdo->prepare('DELETE FROM ' . $table . ' WHERE ' . $column_key['key']);
      $statement->execute($column_key['value']);
    }
  }

  // get object

  public static function getObject(\PDO $pdo, array $option): ?object
  {
    $column = (isset((static::class)::COLUMN)) ? (static::class)::COLUMN : [];
    $table = (isset((static::class)::TABLE)) ? (static::class)::TABLE : null;
    $table_key = (isset((static::class)::TABLE_KEY)) ? (static::class)::TABLE_KEY : null;
    $column_type = $column_value = $column_key = [];

    //

    if (isset($option['index']))
    {
      $column_type[] = 'index';
      $column_value += $option['index'];
    }

    //

    if (isset($option['join']))
    {
      $column_type[] = 'join';
      $column_value += $option['join'];
    }

    //

    if ($column_value !== [])
    {
      $column_key = self::column($column_type, $column, $column_value, $table, $table_key);
    }

    //

    $join = (isset($column_key['join'])) ? ' ' . $column_key['join'] : '';
    $statement = null;

    //

    if (isset($table) && isset($column_key['index']))
    {
      $statement = $pdo->prepare('SELECT ' . $table . '.* FROM ' . $table . $join . ' WHERE ' . $column_key['index'] . ' LIMIT 1');
      $statement->execute($column_key['value']);

      //

      if ($retObject = $statement->fetchObject(static::class))
      {
        return $retObject;
      }
      else
      {
        return null;
      }
    }
    else
    {
      return null;
    }
  }

  // get list of objects

  public static function getList(\PDO $pdo, array $option): ?array
  {
    $column = (isset((static::class)::COLUMN)) ? (static::class)::COLUMN : [];
    $table = (isset((static::class)::TABLE)) ? (static::class)::TABLE : null;
    $table_key = (isset((static::class)::TABLE_KEY)) ? (static::class)::TABLE_KEY : null;
    $limit = (isset($option['limit'])) ? (int) $option['limit'] : 100;
    $offset = (isset($option['offset'])) ? (int) $option['offset'] : 0;
    $distinct = (isset($option['distinct'])) ? (bool) $option['distinct'] : false;
    $column_type = $column_value = $column_key = [];

    //

    if (isset($option['index']))
    {
      $column_type[] = 'index';
      $column_value += $option['index'];
    }

    //

    if (isset($option['index_not']))
    {
      $column_type[] = 'index_not';
      $column_value += $option['index_not'];
    }

    //

    if (isset($option['index_gt']))
    {
      $column_type[] = 'index_gt';
      $column_value += $option['index_gt'];
    }

    //

    if (isset($option['index_lt']))
    {
      $column_type[] = 'index_lt';
      $column_value += $option['index_lt'];
    }

    //

    if (isset($option['search']))
    {
      $column_type[] = 'search';
      $column_value += $option['search'];
    }

    //

    if (isset($option['filter']))
    {
      $column_type[] = 'filter';
      $column_value += $option['filter'];
    }

    //

    if (isset($option['join']))
    {
      $column_type[] = 'join';
      $column_value += $option['join'];
    }

    //

    if (isset($option['order_by']))
    {
      $column_type[] = 'order_by';
      $column_value += $option['order_by'];
    }

    //

    if ($column_value !== [])
    {
      $column_key = self::column($column_type, $column, $column_value, $table, $table_key);
    }

    //

    $index = (isset($column_key['index'])) ? ' AND ' . $column_key['index'] : '';
    $search = (isset($column_key['search'])) ? ' AND (' . $column_key['search'] . ')' : '';
    $filter = (isset($column_key['filter'])) ? ' AND ' . $column_key['filter'] : '';
    $join = (isset($column_key['join'])) ? ' ' . $column_key['join'] : '';
    $join = ($distinct === true && isset($table) && isset($table_key)) ? ' INNER JOIN (SELECT ' . $table . '.' . $table_key . ' FROM' . $join . ' GROUP BY ' . $table . '.' . $table_key . ') self_join_' . $table . ' ON ' . $table . '.' . $table_key . ' = self_join_' . $table . '.' . $table_key : $join;
    $order_by = (isset($column_key['order_by'])) ? ' ORDER BY ' . $column_key['order_by'] : '';
    $statement = null;

    //

    if (isset($table))
    {
      if ($index !== '' || $search !== '' || $filter !== '')
      {
        $statement = $pdo->prepare('SELECT ' . $table . '.* FROM ' . $table . $join . ' WHERE TRUE' . $index . $search . $filter . $order_by . ' LIMIT ' . $limit . ' OFFSET ' . $offset);
        $statement->execute($column_key['value']);
      }
      else
      {
        $statement = $pdo->query('SELECT ' . $table . '.* FROM ' . $table . $join . $order_by . ' LIMIT ' . $limit . ' OFFSET ' . $offset);
      }
    }

    //

    if (isset($statement) && $retObject = $statement->fetchAll(\PDO::FETCH_CLASS, static::class))
    {
      return $retObject;
    }
    else
    {
      return null;
    }
  }

  // check if item exists

  public static function exists(\PDO $pdo, array $option): bool
  {
    $column = (isset((static::class)::COLUMN)) ? (static::class)::COLUMN : [];
    $table = (isset((static::class)::TABLE)) ? (static::class)::TABLE : null;
    $table_key = (isset((static::class)::TABLE_KEY)) ? (static::class)::TABLE_KEY : null;
    $column_type = $column_value = $column_key = [];

    //

    if (isset($option['index']))
    {
      $column_type[] = 'index';
      $column_value += $option['index'];
    }

    //

    if (isset($option['join']))
    {
      $column_type[] = 'join';
      $column_value += $option['join'];
    }

    //

    if ($column_value !== [])
    {
      $column_key = self::column($column_type, $column, $column_value, $table, $table_key);
    }

    //

    $join = (isset($column_key['join'])) ? ' ' . $column_key['join'] : '';
    $statement = null;

    //

    if (isset($table) && isset($column_key['index']))
    {
      $statement = $pdo->prepare('SELECT 1 FROM ' . $table . $join . ' WHERE ' . $column_key['index'] . ' LIMIT 1');
      $statement->execute($column_key['value']);

      //

      return (bool) $statement->fetchColumn();
    }
    else
    {
      return false;
    }
  }

  // total count

  public static function total(\PDO $pdo, array $option): int
  {
    $column = (isset((static::class)::COLUMN)) ? (static::class)::COLUMN : [];
    $table = (isset((static::class)::TABLE)) ? (static::class)::TABLE : null;
    $table_key = (isset((static::class)::TABLE_KEY)) ? (static::class)::TABLE_KEY : null;
    $distinct = (isset($option['distinct'])) ? (bool) $option['distinct'] : false;
    $column_type = $column_value = $column_key = [];

    //

    if (isset($option['index']))
    {
      $column_type[] = 'index';
      $column_value += $option['index'];
    }

    //

    if (isset($option['index_not']))
    {
      $column_type[] = 'index_not';
      $column_value += $option['index_not'];
    }

    //

    if (isset($option['index_gt']))
    {
      $column_type[] = 'index_gt';
      $column_value += $option['index_gt'];
    }

    //

    if (isset($option['index_lt']))
    {
      $column_type[] = 'index_lt';
      $column_value += $option['index_lt'];
    }

    //

    if (isset($option['search']))
    {
      $column_type[] = 'search';
      $column_value += $option['search'];
    }

    //

    if (isset($option['filter']))
    {
      $column_type[] = 'filter';
      $column_value += $option['filter'];
    }

    //

    if (isset($option['join']))
    {
      $column_type[] = 'join';
      $column_value += $option['join'];
    }

    //

    if ($column_value !== [])
    {
      $column_key = self::column($column_type, $column, $column_value, $table, $table_key);
    }

    //

    $index = (isset($column_key['index'])) ? ' AND ' . $column_key['index'] : '';
    $search = (isset($column_key['search'])) ? ' AND (' . $column_key['search'] . ')' : '';
    $filter = (isset($column_key['filter'])) ? ' AND ' . $column_key['filter'] : '';
    $join = (isset($column_key['join'])) ? ' ' . $column_key['join'] : '';
    $join = ($distinct === true && isset($table) && isset($table_key)) ? ' INNER JOIN (SELECT ' . $table . '.' . $table_key . ' FROM' . $join . ' GROUP BY ' . $table . '.' . $table_key . ') self_join_' . $table . ' ON ' . $table . '.' . $table_key . ' = self_join_' . $table . '.' . $table_key : $join;
    $statement = null;

    //

    if (isset($table))
    {
      if ($index !== '' || $search !== '' || $filter !== '')
      {
        $statement = $pdo->prepare('SELECT COUNT(*) AS total FROM ' . $table . $join . ' WHERE TRUE' . $index . $search . $filter);
        $statement->execute($column_key['value']);
      }
      else
      {
        $statement = $pdo->query('SELECT COUNT(*) AS total FROM ' . $table . $join);
      }
    }

    //

    return (int) ((isset($statement)) ? $statement->fetchColumn() : null);
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
    elseif ($type !== 'save' || $type !== 'edit' || $type !== 'remove')
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
              if ($type === 'save')
              {
                (new static::class($data))->save($pdo);
              }
              elseif ($type === 'edit')
              {
                (new static::class($data))->edit($pdo);
              }
              elseif ($type === 'remove')
              {
                (new static::class($data))->remove($pdo);
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

  private static function column(array &$type, array &$column, array &$column_value, ?string $table = null, ?string $table_key = null): array
  {
    $arr = [];
    $index_value = '';
    $key_count = $index_count = $allowed_insert_count = $allowed_edit_count = $search_count = $filter_count = $join_count = $order_by_count = 0;

    //

    foreach ($column_value as $key => &$value)
    {
      foreach ($type as $type_value)
      {
        if (!isset($column[$key]))
        {
          throw new \InvalidArgumentException('Column key not found: ' . $key);
        }
        elseif (!isset($column[$key][$type_value]))
        {
          throw new \InvalidArgumentException('Column type not found: ' . $type_value);
        }
        elseif ($column[$key][$type_value] !== true)
        {
          continue;
        }
        else
        {
          if (!isset($value))
          {
            $value = null;
          }

          //

          if ($type_value === 'key')
          {
            if (is_string($value) || is_int($value) || is_bool($value))
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
            if (is_string($value) || is_int($value) || is_bool($value) || is_null($value))
            {
              if ($type_value === 'index') $index_value = '';
              if ($type_value === 'index_not') $index_value = '!';
              if ($type_value === 'index_gt') $index_value = '>';
              if ($type_value === 'index_lt') $index_value = '<';
              if (!isset($arr['index'])) $arr['index'] = null;
              $arr['index'] .= (($index_count === 0) ? '' : ' AND ') . $key . ' ' . $index_value . '= :' . $key;
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
            if (is_string($value) || is_int($value) || is_bool($value) || is_null($value))
            {
              if (is_string($value) && trim($value) === '')
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
              $arr[$type_value] .= (($search_count === 0) ? '' : ' OR ') . $key . ' LIKE = :' . $key;
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
              $arr[$type_value] .= (($filter_count === 0) ? '' : ' AND ') . $key . ' IN (' . (($in_val !== '') ? $in_val : '0') . ')';
              $filter_count++;
            }
            else
            {
              continue;
            }
          }
          elseif ($type_value === 'join')
          {
            if (is_string($value) && trim($value) !== '' && isset($table) && isset($table_key))
            {
              if (!isset($arr[$type_value])) $arr[$type_value] = null;
              $arr[$type_value] .= (($join_count === 0) ? '' : ' ') . 'INNER JOIN ' . $value . ' ON ' . $table . '.' . $table_key . ' = ' . $value . '.' . $key;
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
              $arr[$type_value] .= (($order_by_count === 0) ? '' : ', ') . $key . ' ' . strtoupper($value);
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

    //

    return $arr;
  }
}
