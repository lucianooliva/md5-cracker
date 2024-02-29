<?php
class PDOHelper {
    public $dbConn;

    function __construct() {
        $this->dbConn = $this->connect();
    }

    private function connect() {
        if ($this->isConnected()) {
            return $this->dbConn;
        }
        $host = DB_HOST;
        $dbName = DB_NAME;
        $dsn = "mysql:host=$host;dbname=$dbName";
        $username = DB_USER;
        $password = DB_PASS;
        $this->dbConn = new PDO($dsn, $username, $password);
        if (!$this->dbConn) throw new Exception("Unable to connect to the database");
        return $this->dbConn;
    }
    public function isConnected() {
        if (!$this->dbConn) {
            return FALSE;
        }
        try {
            $this->dbConn->query("SELECT 1");
        }
        catch (PDOException $e) {
            return FALSE;
        }
        return TRUE;
    }
    public function add(array $fields, string $tableName): string {
        $sql  = "INSERT INTO `$tableName` ";
        $colsArray = array_keys($fields);
        $colsStr = implode(",", $colsArray);
        $len = count($fields);
        $questionMarks = substr( str_repeat("?,", $len), 0, -1 );
        $sql .= " ($colsStr) ";
        $sql .= " VALUES ($questionMarks) ";
        if (!$this->dbConn) {
            $this->connect();
        }
        $stmt = $this->dbConn->prepare($sql);
        if (!$stmt) throw new Exception("Could not prepare query. ".json_encode($this->dbConn->errorInfo()));
        $values = array_values($fields);
        if (!$stmt->execute($values)) throw new Exception("Could not execute query. ".json_encode($stmt->errorInfo()));
        return $this->dbConn->lastInsertId();
    }
    public function getByOneField(string $value, string $colName, array $fields, string $tableName): array {
        $fieldsStr = "`" . implode("`,`", $fields) . "`";
        $sql  = "SELECT $fieldsStr ";
        $sql .= "FROM `$tableName` ";
        $sql .= "WHERE `$colName` = :value;";
        if (!$this->dbConn) {
            $this->connect();
        }
        $stmt = $this->dbConn->prepare($sql);
        if (!$stmt) throw new Exception("Could not prepare query. ".json_encode($this->dbConn->errorInfo()));
        if (!$stmt->execute(["value"=>$value])) throw new Exception("Could not execute query. ".json_encode($stmt->errorInfo()));
        $fetchedData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($fetchedData as $k=>$v) $fetchedData[$k] = (object) $v;
        return $fetchedData;
    }
}