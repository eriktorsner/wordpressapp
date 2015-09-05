<?php

class Resolver
{
    public static function resolveReferences()
    {
        global $bootstrapSettings;

        foreach($bootstrapSettings->references as $reference) {
            if(!isset($reference->path) && !isset($reference->paths)) {
                $currentValue = get_option($reference->option_name, 0);
                $postId = findTargetPostId($currentValue);
                if($postId != 0) {
                    update_option($reference->option_name, $postId);
                }
            } elseif(isset($reference->path) && !isset($reference->paths)) {
                $path = $reference->path;
                $currentStruct = get_option($reference->option_name, 0);
                $currentValue = self::getValue($currentStruct, $path);
                $postId = findTargetPostId($currentValue);
                if($postId != 0) {
                    self::setValue($currentStruct, $path, $postId);
                    update_option($reference->option_name, $currentStruct);
                }
            } elseif(isset($reference->paths)) {
                $currentStruct = get_option($reference->option_name, 0);
                $paths = $reference->paths;
                foreach($paths as $path) {
                    $currentValue = self::getValue($currentStruct, $path);
                    $postId = findTargetPostId($currentValue);
                    if($postId != 0) {
                        self::setValue($currentStruct, $path, $postId);
                    }
                }
                update_option($reference->option_name, $currentStruct);
            }
        }
    }

    private static function getValue($obj, $path) {
        return eval("return \$obj".$path .";");
    }

    private static function setValue(&$obj, $path, $value) {
        $evalStr = "\$obj".$path."=" . $value."; return \$obj;";
        $obj = eval($evalStr);
    }

    public static function field_search_replace(&$fld_value, $search, $replace)
    {
        $value = $fld_value;
        $ret = false;
        if (is_numeric($fld_value)) {
            return $ret;
        }

        $b64 = self::is_base64($fld_value);
        if ($b64) {
            $value = base64_decode($value);
        }
        $ser = self::is_serialized($value);
        if ($ser) {
            $value = unserialize($value);
        }

        self::recurse_search_replace($value, $search, $replace);

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

    private static function recurse_search_replace(&$obj, $search, $replace)
    {
        if (is_object($obj) || is_array($obj)) {
            foreach ($obj as &$member) {
                self::recurse_search_replace($member, $search, $replace);
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

    private static function is_serialized($data)
    {
        $test = @unserialize(($data));

        return ($test !== false || $test === 'b:0;') ? true : false;
    }

    private static function is_base64($data)
    {
        if (@base64_encode(base64_decode($data)) === $data) {
            return true;
        } else {
            return false;
        }
    }



}

