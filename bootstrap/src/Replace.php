<?php

class Replace
{
    public function search_replace_database($host, $dbname, $user, $pass, $search_replace)
    {
        $mysqli = new mysqli($host, $user, $pass, $dbname);
        $mysqli->set_charset(get_charset());

        /*  Step 4. Rewrite db tables */
        $tables = $mysqli->query('SHOW TABLES');
        while ($table = $tables->fetch_row()) {
            $page_size = 1000;

            $curr_table = $table[0];
            $pkey = get_primary_key($mysqli, $curr_table);
            // skip tables that don't have any pk
            if ($pkey) {
                $curr_page = 0;
                $done = false;
                while (!$done) {
                    $sql = sprintf(
                        "select * from `%s` LIMIT %s, %s",
                        $curr_table,
                        $curr_page * $page_size,
                        $page_size
                    );
                    $result = $mysqli->query($sql);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_object()) {
                            $info = $result->fetch_fields();
                            foreach ($row as $name => $value) {
                                $update_query = sprintf("UPDATE `%s` SET ", $curr_table);
                                $update_fields = array();
                                foreach ($search_replace as $pair) {
                                    $mod = field_search_replace($value, $pair[0], $pair[1]);
                                    if ($mod) {
                                        $update_fields[] = sprintf("%s='%s'", $name, $mysqli->real_escape_string($value));
                                    }
                                }
                                if (count($update_fields) > 0) {
                                    $update_query .= implode(',', $update_fields);
                                    $update_query .= where_clause($pkey, $info, $row);
                                    //echo "$update_query\n";
                                    $mysqli->query($update_query);
                                }
                            }
                        }
                        $curr_page++;
                    } else {
                        $done = true;
                    }
                }
            }
        }
    }

    public function where_clause($pkey, $fields, $row)
    {
        $parts = array();
        foreach ($fields as $f) {
            $fields[$f->orgname] = $f;
        }
        foreach ($pkey as $p) {
            $part = "$p =";
            if (field_is_numeric($fields[$p])) {
                $part .= $row->$p;
            } else {
                $part .= "'{$row->$p}'";
            }
            $parts[] = $part;
        }

        return ' WHERE '.implode(' AND ', $parts);
    }

    public function field_search_replace(&$fld_value, $search, $replace)
    {
        $value = $fld_value;
        $ret = false;
        if (is_numeric($fld_value)) {
            return $ret;
        }

        $b64 = is_base64($fld_value);
        if ($b64) {
            $value = base64_decode($value);
        }
        $ser = is_serialized($value);
        if ($ser) {
            $value = unserialize($value);
        }

        recurse_search_replace($value, $search, $replace);

        if ($ser) {
            $value = serialize($value);
        }
        if ($b64) {
            $value = base64_encode($value);
        }

        if ($fld_value != $value) {
            $fld_value = $value;
            $ret = true;
        }

        return $ret;
    }

    public function recurse_search_replace(&$obj, $search, $replace)
    {
        if (is_object($obj) || is_array($obj)) {
            foreach ($obj as &$member) {
                recurse_search_replace($member, $search, $replace);
            }
        } else {
            if (is_numeric($obj)) {
                return;
            }
            if (is_bool($obj)) {
                return;
            }
            if (is_null($obj)) {
                return;
            }
            $obj =  str_replace($search, $replace, $obj);
        }
    }

    public function is_serialized($data)
    {
        $test = @unserialize(($data));

        return ($test !== false || $test === 'b:0;') ? true : false;
    }

    public function is_base64($data)
    {
        if (base64_encode(base64_decode($data)) === $data) {
            return true;
        } else {
            return false;
        }
    }

    public function get_primary_key($dbh, $table)
    {
        $ret = array();
        $query = sprintf("SHOW KEYS FROM `%s` WHERE Key_name = 'PRIMARY'", $table);
        $result = $dbh->query($query);
        if ($result) {
            while ($row = $result->fetch_object()) {
                $ret[] = $row->Column_name;
            }

            return $ret;
        }

        return false;
    }

    public function field_is_numeric($field)
    {
        switch ($field->type) {
            case MYSQLI_TYPE_DECIMAL:
            case MYSQLI_TYPE_NEWDECIMAL:
            case MYSQLI_TYPE_BIT:
            case MYSQLI_TYPE_TINY:
            case MYSQLI_TYPE_SHORT:
            case MYSQLI_TYPE_LONG:
            case MYSQLI_TYPE_FLOAT:
            case MYSQLI_TYPE_DOUBLE:
            case MYSQLI_TYPE_LONGLONG:
            case MYSQLI_TYPE_INT24:
                return true;
        }

        return false;
    }
}
