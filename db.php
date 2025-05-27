PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_FOUND_ROWS => true
            ];
            
            $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            // Log error and display friendly message
            error_log('Database Connection Error: ' . $e->getMessage());
            die("Sorry, there was a problem connecting to the database. Please try again later.");
        }
    }
    
    return $conn;
}

/**
 * Execute a SQL query and return the result
 * 
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind to the query
 * @return array|bool Result set array or false on failure
 */
function executeQuery($sql, $params = []) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        // Check if query is a SELECT query
        if (stripos(trim($sql), 'SELECT') === 0) {
            return $stmt->fetchAll();
        }
        
        return true;
    } catch (PDOException $e) {
        error_log('SQL Error: ' . $e->getMessage() . " in query: $sql");
        return false;
    }
}

/**
 * Get a single record from the database
 * 
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind to the query
 * @return array|null Single row or null if not found
 */
function getRecord($sql, $params = []) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result !== false ? $result : null;
    } catch (PDOException $e) {
        error_log('SQL Error: ' . $e->getMessage() . " in query: $sql");
        return null;
    }
}

/**
 * Insert a record and return the last insert ID
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @return int|bool Last insert ID or false on failure
 */
function insertRecord($table, $data) {
    try {
        $conn = getDbConnection();
        
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $conn->lastInsertId();
    } catch (PDOException $e) {
        error_log('Insert Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update a record
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @param string $whereClause WHERE clause
 * @param array $whereParams Parameters for WHERE clause
 * @return bool True on success, false on failure
 */
function updateRecord($table, $data, $whereClause, $whereParams = []) {
    try {
        $conn = getDbConnection();
        
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "$column = ?";
        }
        
        $setClause = implode(', ', $setParts);
        $sql = "UPDATE $table SET $setClause WHERE $whereClause";
        
        $params = array_merge(array_values($data), $whereParams);
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log('Update Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete a record
 * 
 * @param string $table Table name
 * @param string $whereClause WHERE clause
 * @param array $params Parameters for WHERE clause
 * @return bool True on success, false on failure
 */
function deleteRecord($table, $whereClause, $params = []) {
    try {
        $conn = getDbConnection();
        
        $sql = "DELETE FROM $table WHERE $whereClause";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log('Delete Error: ' . $e->getMessage());
        return false;
    }
}

?>