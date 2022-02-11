<?php

/*
 * Eduardo Malherbi Martins (http://emalherbi.com/)
 * Copyright @emm
 * Full Stack Web Developer.
 */

namespace MyMssql;

use Exception;

set_time_limit(0);

ini_set('memory_limit', '512M');
ini_set('mssql.timeout', '999999');
ini_set('max_execution_time', '999999');
ini_set('soap.wsdl_cache_ttl', '999999');
ini_set('mssql.textlimit', '2147483647');
ini_set('mssql.textsize', '2147483647');

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

class MyMssql
{
    private $DS = null; // DS
    private $RT = null; // ROOT
    private $DL = null; // DIR LOG

    private $type = null;
    private $db = null;
    private $ini = null;

    public function __construct($ini = array(), $dl = '', $type = 'UTF-8')
    {
        $this->DS = DIRECTORY_SEPARATOR;
        $this->RT = realpath(dirname(__FILE__));
        $this->DL = empty($dl) ? realpath(dirname(__FILE__)) : $dl;

        $this->type = $type; // Ex.: ISO-8859-1

        if (!empty($ini)) {
            $ini = $this->validateIni($ini);
        }
        $this->ini = $ini;
        $this->ini = $this->validateIni($this->ini);

        $this->connect();
    }

    public function validateIni($ini)
    {
        if (array_key_exists('VERBOSE', $ini)) {
            if (!isset($ini['VERBOSE'])) {
                $ini['VERBOSE'] = false;
            }
        }

        return $ini;
    }

    public function getAdapter()
    {
        return (function_exists('mssql_connect')) ? 'MSSQL' : 'SQLSRV';
    }

    /* is connnect */

    public function isConnect()
    {
        return empty($this->db) ? false : true;
    }

    /* connnect */

    public function connect()
    {
        if (!empty($this->db)) {
            return $this->db;
        }

        $hostname = $this->ini['HOSTNAME'];
        $username = $this->ini['USERNAME'];
        $password = $this->ini['PASSWORD'];
        $database = $this->ini['DATABASE'];

        if (true == $this->ini['VERBOSE']) {
            $this->logger('MyMssql Connect');
            $this->logger('HOSTNAME: '.$hostname);
            $this->logger('USERNAME: '.$username);
            $this->logger('PASSWORD: '.$password);
            $this->logger('DATABASE: '.$database);
        }

        $this->ini['ADAPTER'] = $this->getAdapter();

        try {
            if ('SQLSRV' == $this->ini['ADAPTER']) {
                $info = array('Database' => $database, 'UID' => $username, 'PWD' => $password);
                $this->db = @sqlsrv_connect($hostname, $info);
            } else {
                $this->db = @mssql_connect($hostname, $username, $password);
                @mssql_select_db($database, $this->db);
            }
        } catch (Exception $e) {
            $err = $e->getMessage();
            $this->logger('MyMssql Connect '.$this->ini['ADAPTER'], $err);
            die(print_r($e->getMessage()));
        }
    }

    /* disconnect */

    public function disconnect()
    {
        try {
            if ('SQLSRV' == $this->ini['ADAPTER']) {
                @sqlsrv_close($this->db);
            } else {
                @mssql_close($this->db);
            }

            if (true == $this->ini['VERBOSE']) {
                $this->logger('MyMssql Disconnect');
            }

            $this->db = null;
        } catch (Exception $e) {
            $err = $e->getMessage();
            $this->logger('MyMssql Disconnect '.$this->ini['ADAPTER'], $err);
            die(print_r($e->getMessage()));
        }
    }

    /* ini */

    public function getIni()
    {
        try {
            return $this->ini;
        } catch (Exception $e) {
            $err = $e->getMessage();
            $this->logger('MyMssql Get Ini '.$this->ini['ADAPTER'], $err);
            die(print_r($e->getMessage()));
        }
    }

    /* transactions */

    public function begin()
    {
        try {
            $this->connect();

            if (true == $this->ini['VERBOSE']) {
                $this->logger('MyMssql Begin Transaction');
            }

            if ('SQLSRV' == $this->ini['ADAPTER']) {
                return @sqlsrv_begin_transaction($this->db);
            }

            return @mssql_query('BEGIN TRANSACTION', $this->db);
        } catch (Exception $e) {
            $err = $e->getMessage();
            $this->logger('MyMssql Begin Transaction '.$this->ini['ADAPTER'], $err);
            die(print_r($e->getMessage()));
        }
    }

    public function commit()
    {
        try {
            $this->connect();

            if (true == $this->ini['VERBOSE']) {
                $this->logger('MyMssql Commit');
            }

            if ('SQLSRV' == $this->ini['ADAPTER']) {
                return @sqlsrv_commit($this->db);
            }

            return @mssql_query('COMMIT TRANSACTION', $this->db);
        } catch (Exception $e) {
            $err = $e->getMessage();
            $this->logger('MyMssql Commit '.$this->ini['ADAPTER'], $err);
            die(print_r($e->getMessage()));
        }
    }

    public function rollback()
    {
        try {
            $this->connect();

            if (true == $this->ini['VERBOSE']) {
                $this->logger('MyMssql RollBack');
            }

            if ('SQLSRV' == $this->ini['ADAPTER']) {
                return @sqlsrv_rollback($this->db);
            }

            return @mssql_query('IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION', $this->db);
        } catch (Exception $e) {
            $err = $e->getMessage();
            $this->logger('MyMssql RollBack '.$this->ini['ADAPTER'], $err);
            die(print_r($e->getMessage()));
        }
    }

    /* fecth */

    public function fetchOne($sql)
    {
        try {
            $stmt = $this->query($sql);

            if ('SQLSRV' == $this->ini['ADAPTER']) {
                $result = @sqlsrv_fetch_array($stmt);
            } else {
                $result = @mssql_fetch_array($stmt);
            }

            $result = $this->getResult($result);

            if (true == $this->ini['VERBOSE']) {
                $this->logger('MyMssql Fetch One: '.json_encode($result));
            }

            return empty($result) ? false : $result[0];
        } catch (Exception $e) {
            $err = $e->getMessage();
            $this->logger('MyMssql Fetch One '.$this->ini['ADAPTER'], $err);
            die(print_r($e->getMessage()));
        }
    }

    public function fetchRow($sql)
    {
        try {
            $stmt = $this->query($sql);

            if ('SQLSRV' == $this->ini['ADAPTER']) {
                $result = @sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            } else {
                $result = @mssql_fetch_array($stmt, MSSQL_ASSOC);
            }

            $result = $this->getResult($result);

            if (true == $this->ini['VERBOSE']) {
                $this->logger('MyMssql Fetch Row: '.json_encode($result));
            }

            return empty($result) ? array() : $result;
        } catch (Exception $e) {
            $err = $e->getMessage();
            $this->logger('MyMssql Fetch Row '.$this->ini['ADAPTER'], $err);
            die(print_r($e->getMessage()));
        }
    }

    public function fetchAll($sql)
    {
        try {
            $stmt = $this->query($sql);

            $result = array();
            if ('SQLSRV' == $this->ini['ADAPTER']) {
                while ($row = @sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $row = $this->getResult($row);
                    $result[] = $row;
                }
            } else {
                while ($row = @mssql_fetch_array($stmt, MSSQL_ASSOC)) {
                    $row = $this->getResult($row);
                    $result[] = $row;
                }
            }

            if (true == $this->ini['VERBOSE']) {
                $this->logger('MyMssql Fetch All: '.json_encode($result));
            }

            return empty($result) ? array() : $result;
        } catch (Exception $e) {
            $err = $e->getMessage();
            $this->logger('MyMssql Fetch All '.$this->ini['ADAPTER'], $err);
            die(print_r($e->getMessage()));
        }
    }

    /* query */

    public function query($sql)
    {
        try {
            $this->connect();

            if ('UTF-8' != $this->type) {
                $sql = @iconv('UTF-8', $this->type, $sql);
            }

            if ('SQLSRV' == $this->ini['ADAPTER']) {
                $stmt = @sqlsrv_query($this->db, $sql, array(), array('Scrollable' => 'static'));
            } else {
                $stmt = @mssql_query($sql, $this->db);
            }

            if (true == $this->ini['VERBOSE']) {
                $this->logger('MyMssql Query: '.$sql);
            }

            return $stmt;
        } catch (Exception $e) {
            $err = $e->getMessage();
            $this->logger('MyMssql Query '.$this->ini['ADAPTER'], $err);
            die(print_r($e->getMessage()));
        }
    }

    /* exec */

    public function exec($sql)
    {
        try {
            $stmt = $this->query($sql);

            if (true == $this->ini['VERBOSE']) {
                $this->logger('MyMssql Exec: '.$sql);
            }

            return empty($stmt) ? false : true;
        } catch (Exception $e) {
            $err = $e->getMessage();
            $this->logger('MyMssql Exec '.$this->ini['ADAPTER'], $err);
            die(print_r($e->getMessage()));
        }
    }

    public function execSql($sql)
    {
        return $this->exec($sql);
    }

    /* exec script */

    public function execScript($sql)
    {
        try {
            $stmt = $this->query($sql);

            if (true == $this->ini['VERBOSE']) {
                $this->logger('MyMssql Exec Script: '.$sql);
            }

            return $stmt;
        } catch (Exception $e) {
            $err = $e->getMessage();
            $this->logger('MyMssql Exec Script '.$this->ini['ADAPTER'], $err);
            die(print_r($e->getMessage()));
        }
    }

    /* exec script result */

    public function execScriptResult($sql, $isObject = false)
    {
        try {
            $stmt = $this->query($sql);

            if (true == $this->ini['VERBOSE']) {
                $this->logger('MyMssql Exec Script Result: '.$sql);
            }

            if (is_bool($stmt)) {
                return $stmt;
            }

            $result = array();

            if ('SQLSRV' == $this->ini['ADAPTER']) {
                do {
                    while ($row = @sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                        $row = $this->getResult($row);
                        if (true == $isObject) {
                            $row = (object) $row;
                        }
                        $result[] = $row;
                    }
                } while (sqlsrv_next_result($stmt));
            } else {
                while ($row = @mssql_fetch_array($stmt, MSSQL_ASSOC)) {
                    $row = $this->getResult($row);
                    if (true == $isObject) {
                        $row = (object) $row;
                    }
                    $result[] = $row;
                }
            }

            if (1 === count($result)) {
                return $result[0];
            }

            return $result;
        } catch (Exception $e) {
            $err = $e->getMessage();
            $this->logger('MyMssql Exec Script Result '.$this->ini['ADAPTER'], $err);
            die(print_r($e->getMessage()));
        }
    }

    /* sx */

    public function fetchAllSx($sxName, $params, $test = false)
    {
        return $this->querySx($sxName, $params, $test, 'fetchAll');
    }

    public function execSx($sxName, $params, $test = false)
    {
        return $this->querySx($sxName, $params, $test, 'exec');
    }

    public function fetchRowSx($sxName, $params, $test = false)
    {
        return $this->querySx($sxName, $params, $test, 'fetchRow');
    }

    private function querySx($sxName, $params, $test = false, $function = 'exec')
    {
        if (false == $this->sxExist($sxName)) {
            return 'Stored Procedure '.$sxName.' not find.';
        }

        $array = $this->getFields($sxName);

        if (count($params) != count($array)) {
            return 'Parameters reported differently than stored procedure parameters.';
        }

        $sql = '';
        $sql .= ' BEGIN ';

        for ($i = 0; $i < count($params); ++$i) {
            $type = trim(strtoupper($array[$i]['TYPE']));
            $columns = trim(strtoupper($array[$i]['COLUMNS']));
            $isOutParam = $array[$i]['ISOUTPARAM'];

            if ($isOutParam) {
                $sql .= ' DECLARE '.$columns.' DECIMAL ';
                $sql .= ' SET '.$columns.' = ';

                if (in_array($type, array('DATETIME', 'SMALLDATETIME', 'TIMESTAMP', 'CHAR', 'NCHAR', 'SQLCHAR', 'TEXT', 'NTEXT', 'VARCHAR', 'NVARCHAR', 'SQLVARCHAR', 'BINARY', 'VARBINARY', 'IMAGE'), true)) {
                    $sql .= "'".$params[$i]."'";
                } else {
                    $sql .= $params[$i];
                }
            }
        }

        $sql .= ' EXEC '.$sxName.' ';

        for ($i = 0; $i < count($params); ++$i) {
            if (0 != $i) {
                $sql .= ', ';
            }

            $type = trim(strtoupper($array[$i]['TYPE']));
            $columns = trim(strtoupper($array[$i]['COLUMNS']));
            $isOutParam = $array[$i]['ISOUTPARAM'];

            if ($isOutParam) {
                $sql .= $columns.' OUTPUT ';
            } elseif (in_array($type, array('DATETIME', 'SMALLDATETIME', 'TIMESTAMP', 'CHAR', 'NCHAR', 'SQLCHAR', 'TEXT', 'NTEXT', 'VARCHAR', 'NVARCHAR', 'SQLVARCHAR', 'BINARY', 'VARBINARY', 'IMAGE'), true)) {
                if (empty($params[$i])) {
                    $sql .= 'NULL';
                } else {
                    $sql .= "'".$params[$i]."'";
                }
            } else {
                if ('0' == $params[$i] || 0 == $params[$i]) {
                    $sql .= 0;
                } elseif (empty($params[$i])) {
                    $sql .= 'NULL';
                } else {
                    $sql .= $params[$i];
                }
            }
        }

        $first = true;
        for ($i = 0; $i < count($array); ++$i) {
            $columns = trim(strtoupper($array[$i]['COLUMNS']));
            $isOutParam = $array[$i]['ISOUTPARAM'];

            if ($isOutParam) {
                if (false == $first) {
                    $sql .= ', ';
                }

                if (true == $first) {
                    $sql .= ' SELECT ';
                }

                $sql .= " $columns AS ".str_replace('@', '', $columns);

                $first = false;
            }
        }

        $sql .= ' END ';

        if (true == $test) {
            echo '<pre>';
            echo print_r($sql);
            echo '</pre>';
            exit;
        }

        if ('exec' == $function) {
            $stmt = $this->query($sql);

            $result = false;
            if (is_bool($stmt)) {
                $result = $stmt;
            } else {
                if ('SQLSRV' == $this->ini['ADAPTER']) {
                    do {
                        while ($row = @sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                            $row = $this->getResult($row);
                            $result = $row;
                        }
                    } while (sqlsrv_next_result($stmt));
                } else {
                    while ($row = @mssql_fetch_array($stmt, MSSQL_ASSOC)) {
                        $row = $this->getResult($row);
                        $result = $row;
                    }
                }
            }

            return $result;
        }

        return $this->$function($sql);
    }

    /* private */

    private function sxExist($sxName)
    {
        $sql = '';
        $sql .= ' SELECT COUNT(*) AS EXIST ';
        $sql .= ' FROM SYSOBJECTS ';
        $sql .= " WHERE ID = OBJECT_ID('".$sxName."')";

        $array = $this->fetchRow($sql);
        $count = $array['EXIST'];

        return ($count > 0) ? true : false;
    }

    private function getFields($sxName)
    {
        $sql = '';
        $sql .= ' SELECT ';
        $sql .= ' COLUMNS.NAME AS COLUMNS, ';
        $sql .= ' TYPES.NAME AS TYPE, ';
        $sql .= ' COLUMNS.ISOUTPARAM, ';
        $sql .= ' COLUMNS.LENGTH ';
        $sql .= ' FROM ';
        $sql .= ' SYSOBJECTS AS TABLES, ';
        $sql .= ' SYSCOLUMNS AS COLUMNS, ';
        $sql .= ' SYSTYPES AS TYPES ';
        $sql .= ' WHERE TABLES.ID = COLUMNS.ID ';
        $sql .= ' AND COLUMNS.XTYPE = TYPES.XTYPE ';
        $sql .= ' AND COLUMNS.USERTYPE = TYPES.USERTYPE ';
        $sql .= " AND TABLES.NAME = '".$sxName."'";
        $sql .= ' ORDER BY TABLES.ID ';

        return $this->fetchAll($sql);
    }

    private function getResult($result)
    {
        if (!empty($result) && is_array($result)) {
            foreach ($result as $key => $value) {
                if (('UTF-8' != $this->type) && ('string' == gettype($value))) {
                    if ('array' == gettype($result)) {
                        $result[$key] = iconv($this->type, 'UTF-8', $value);
                    } elseif ('object' == gettype($result)) {
                        $result->$key = iconv($this->type, 'UTF-8', $value);
                    }
                }

                if (('object' == gettype($value)) && (is_a($value, 'DateTime'))) {
                    if ('array' == gettype($result)) {
                        $result[$key] = $value->format('Y-m-d H:i:s');
                    } elseif ('object' == gettype($result)) {
                        $result->$key = $value->format('Y-m-d H:i:s');
                    }
                }

                if (is_int($value) && (0 == $value)) {
                    if ('array' == gettype($result)) {
                        $result[$key] = 0;
                    } elseif ('object' == gettype($result)) {
                        $result->$key = 0;
                    }
                } elseif (is_numeric($value) && (0 == $value)) {
                    if ('array' == gettype($result)) {
                        $result[$key] = '0.00';
                    } elseif ('object' == gettype($result)) {
                        $result->$key = '0.00';
                    }
                }
            }
        }

        return $result;
    }

    private function logger($str, $err = '')
    {
        $date = date('Y-m-d');
        $hour = date('H:i:s');

        @mkdir($this->DL, 0777, true);
        @chmod($this->DL, 0777);

        $log = '';
        $log .= "[$hour] > $str \n";
        if (!empty($err)) {
            $log .= "[ERROR] > $err \n\n";
        }

        $file = fopen($this->DL.$this->DS."log-$date.txt", 'a+');
        fwrite($file, $log);
        fclose($file);
    }
}
