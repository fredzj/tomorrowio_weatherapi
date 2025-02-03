<?php
/**
 * Class Database
 * 
 * This class provides methods for interacting with a MySQL database using PDO. It includes methods for
 * connecting to the database, executing SELECT queries, inserting multiple rows, logging messages, and
 * truncating tables.
 * 
 * @package tomorrowio_weatherforecast
 * @version 1.0.0
 * @since 2024
 * @license MIT
 * 
 * COPYRIGHT: 2024 Fred Onis - All rights reserved.
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * 
 * @author Fred Onis
 */
class Database {
    private $dbh;
    private $log;

    /**
     * Database constructor.
     * 
     * @param array $dbConfig The database configuration.
     */
    public function __construct($dbConfig) {
        $this->log = new Log();
        $this->connect($dbConfig);
    }

	/**
	* Establish a database connection.
	*
	* This method establishes a connection to the database using the provided configuration array.
	* It sets the PDO error mode to exception and uses UTF-8 character encoding.
	* If an exception occurs during the connection process, it is caught and logged.
	*
	* @param array $dbConfig The database configuration array containing the following keys:
	*                        - db_pdo_driver_name: The PDO driver name (e.g., 'mysql').
	*                        - db_hostname: The database host name.
	*                        - db_database: The database name.
	*                        - db_username: The database username.
	*                        - db_password: The database password.
	*
	* @return void
	*/
    private function connect(array $dbConfig): void {
        try {
            if (empty($dbConfig['db_pdo_driver_name']) || empty($dbConfig['db_hostname']) || empty($dbConfig['db_database']) || empty($dbConfig['db_username']) || empty($dbConfig['db_password'])) {
                throw new InvalidArgumentException('Invalid database configuration');
            }

            $dsn = $dbConfig['db_pdo_driver_name'] . ':host=' . $dbConfig['db_hostname'] . ';dbname=' . $dbConfig['db_database'] . ';charset=utf8mb4';
            $this->dbh = new PDO(
                $dsn,
                $dbConfig['db_username'],
                $dbConfig['db_password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_PERSISTENT => false
                ]
            );

        } catch (PDOException $e) {
            $this->log->error('Caught PDOException: ' . $e->getMessage());
            $this->dbh = null;

        } catch (InvalidArgumentException $e) {
            $this->log->error('Caught InvalidArgumentException: ' . $e->getMessage());
            $this->dbh = null;
        }
    }

	/**
	* Insert a record into the specified table.
	*
	* This method inserts a record into the specified table with the given columns and values.
	* If an exception occurs during the insertion, it is caught and logged.
	*
	* @param string $table The name of the table to insert the record into.
	* @param array $columns An array of column names for the table.
	* @param array $values An array of values corresponding to the columns.
	*
	* @return void
	*/
    public function insert(string $table, array $columns, array $values): void {
        if (count($values) > 0) {
            $columns = implode(", ", $columns);

            if (mb_substr($values[0], 0, 1) == '(') {
                $values = implode(", ", $values);
            } else {
                $values = "('" . implode("', '", $values) . "')";
            }

            try {
                $sql = "INSERT IGNORE INTO $table ($columns) VALUES $values";
                $stmt = $this->dbh->prepare($sql);
                $stmt->execute();
                $stmt->closeCursor();
            } catch (PDOException $e) {
                $this->log->error('Caught PDOException: ' . $e->getMessage() . ' SQL:' . $sql);
            }
        }
    }

    public function dbinsert(string $table, array $columns, array $values): void {

        if (count($values) > 0) {
            $columns_str = implode(", ", $columns);
            $placeholders = implode(", ", array_fill(0, count($columns), '?'));
            $sql = "INSERT IGNORE INTO $table ($columns_str) VALUES ($placeholders)";
            
            try {
                $stmt = $this->dbh->prepare($sql);
                foreach ($values as $value) {
                    $stmt->execute($value);
                }
                $stmt->closeCursor();
            } catch (PDOException $e) {
                $this->log->error('Caught PDOException: ' . $e->getMessage() . ' SQL:' . $sql);
            }
        }
    }
    
	/**
	* Log a message to the database.
	*
	* This method logs a message to the 'log' table in the database with the specified level, label, and description.
	*
	* @param string $level The log level (e.g., 'error', 'info', 'debug').
	* @param string $label A short label or title for the log entry.
	* @param string $description A detailed description of the log entry.
	*
	* @return void
	*/
    public function log(string $level, string $label, string $description): void {
        $outputColumns = ['level', 'label', 'description'];
        $outputValues = [$level, $label, $description];
        $this->insert('log', $outputColumns, $outputValues);
    }

	/**
	* Execute a SELECT query and return the results.
	*
	* This method prepares and executes a SELECT query with optional parameters and returns the fetched rows as an associative array.
	* If an exception occurs during the query execution, it is caught and logged.
	*
	* @param string $sql The SQL query to execute.
	* @param array $params Optional parameters to bind to the query.
	*
	* @return array The fetched rows as an associative array. Returns an empty array if an error occurs.
	*/
    public function select(string $sql, array $params = []): array {
        try {
            $stmt = $this->dbh->prepare($sql);
            $stmt->execute($params);
            $fetchedRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $fetchedRows;

        } catch (PDOException $e) {
            $this->log->error('Caught PDOException: ' . $e->getMessage());
            return [];
        }
    }

    /**
	* Truncate a table in the database.
	*
	* This method truncates the specified table, removing all rows and resetting any auto-increment values.
	* If an exception occurs during the truncation, it is caught and logged.
	*
	* @param string $tableName The name of the table to truncate.
	*
	* @return void
	*/
    public function truncate(string $tableName):void {
        try {
            $sql = 'TRUNCATE `' . $tableName . '`';
            $stmt = $this->dbh->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
            $this->log->info('- truncated table ' . htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8'));
        } catch (PDOException $e) {
            $this->log->error('Caught PDOException: ' . $e->getMessage());
        }
    }

	/**
	* Update a record in the specified table.
	*
	* This method updates a record in the specified table with the given assignment and sets the current timestamp.
	* If an exception occurs during the update, it is caught and logged.
	*
	* @param string $tableName The name of the table to update.
	* @param string $id The ID of the record to update.
	* @param string $assignment The assignment string specifying the columns and values to update.
	*
	* @return void
	*/
    public function update(string $tableName, string $id, string $assignment): void {
        try {
            $sql = "UPDATE $tableName SET $assignment, timestamp=NOW() WHERE id = :id";
            $stmt = $this->dbh->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();
        } catch (PDOException $e) {
            $this->log->error('Caught PDOException: ' . $e->getMessage());
        }
    }
}