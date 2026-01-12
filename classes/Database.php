<?php
/**
 * Database Class - PDO Wrapper
 * Handles all database operations with prepared statements
 */

class Database {
    private static $instance = null;
    private $conn;
    private $stmt;
    private $error;
    
    /**
     * Private constructor for Singleton pattern
     */
    private function __construct() {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => true
        ];
        
        try {
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            if (DB_ERROR_DISPLAY) {
                die("Database connection failed: " . $e->getMessage());
            } else {
                // Log error to file
                if (DB_ERROR_LOG) {
                    error_log("Database Error: " . $e->getMessage());
                }
                die("Database connection failed. Please contact administrator.");
            }
        }
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Prepare SQL statement
     */
    public function query($sql) {
        $this->stmt = $this->conn->prepare($sql);
        return $this;
    }
    
    /**
     * Bind values to prepared statement
     */
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }
    
    /**
     * Execute prepared statement
     */
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            if (DB_ERROR_LOG) {
                error_log("Query Error: " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Get multiple records
     */
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }
    
    /**
     * Get single record
     */
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }
    
    /**
     * Get row count
     */
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->conn->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollBack() {
        return $this->conn->rollBack();
    }
    
    /**
     * Get error message
     */
    public function getError() {
        return $this->error;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
