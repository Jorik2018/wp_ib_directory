<?php

/* file: app/Util/Utils.php */

namespace IB\directory\Util;

use WPMVC\Bridge;

function renameFields(array $data, array $fieldMap): array
{
    $result = [];
    foreach ($data as $key => $value) {
        if (isset($fieldMap[$key])) {
            $result[$fieldMap[$key]] = $value;
        } else {
            $result[$key] = $value; // conserva los no mapeados
        }
    }
    return $result;
}

function toLowerCase($data)
{
    if (is_object($data)) {
        $result = new \stdClass();
        foreach ($data as $key => $value) {
            $newKey = strtolower($key);
            $result->$newKey = toCamelCase($value);
        }
        return  $result;
    } elseif (is_array($data)) {
        $keys = array_keys($data);
        $isNumeric = empty($keys);
        if (!$isNumeric)
            foreach ($keys as $key) {
                if (is_int($key)) {
                    $isNumeric = true;
                }
                break;
            }
        if ($isNumeric) {
            $result = array();
            foreach ($data as $item) {
                $result[] = toLowerCase($item);
            }
            return $result;
        } else {
            $result = new \stdClass();
            foreach ($data as $key => $value) {
                $newKey = strtolower($key);
                $result->$newKey = $value;
            }
            return  $result;
        }
    } else {
        return $data;
    }
}

function toCamelCase($data)
{
    if (is_object($data)) {
        // Convertir objeto → array para mantener consistencia
        $data = (array) $data;
    }

    if (is_array($data)) {

        // Detectar si es array numérico
        $isNumeric = array_keys($data) === range(0, count($data) - 1);

        if ($isNumeric) {
            // Lista de elementos
            $result = [];
            foreach ($data as $item) {
                $result[] = toCamelCase($item);
            }
            return $result;
        }

        // Array asociativo → devolver array, NO stdClass
        $result = [];
        foreach ($data as $key => $value) {
            $newKey = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
            $result[$newKey] = toCamelCase($value);
        }

        return $result;
    }

    return $data; // valor primitivo
}

function cdfield(&$row, $key)
{
    if (is_numeric($row[$key])) {
        $row[$key] = date("Y-m-d", $row[$key] / 1000);
    }
    return $row;
}

function cbfield(&$row, $key)
{
    if (is_numeric($row[$key])) {
        $v = $row[$key];
        unset($row[$key]);
        $row[$key] = intval($v) > 0;
    }
    return $row;
}

function cdfield2(&$row, $key)
{
    if (is_numeric($row[$key])) {
        $row[$key] = date("Y-m-d H:i:s", $row[$key] / 1000);
    }
    return $row;
}

function cfield(&$row, $from, $to)
{
    if (array_key_exists($from, $row)) {
        $row[$to] = $row[$from];
        unset($row[$from]);
    }
    return $row;
}

function get_param($request, $param_name = null)
{
    // Si es objeto tipo WP_REST_Request
    if (is_object($request) && method_exists($request, 'get_param')) {
        return $param_name !== null
            ? $request->get_param($param_name)
            : (method_exists($request, 'get_params')
                ? $request->get_params()
                : null);
    }

    // Si es array
    if (is_array($request)) {
        return $param_name !== null
            ? ($request[$param_name] ?? null)
            : $request;
    }

    return null;
}

function remove(array &$arr, $key)
{
    if (array_key_exists($key, $arr)) {
        $val = $arr[$key];
        unset($arr[$key]);
        return $val;
    }
    return null;
}

function t_error($msg = false)
{
    global $wpdb;
    $error = new \WP_Error(500, $msg ? $msg : $wpdb->last_error, array('status' => 500));
    $wpdb->query('ROLLBACK');
    return $error;
}
