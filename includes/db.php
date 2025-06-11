<?php
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

$conn = connectDB();

function executeQuery($sql, $params = [], $types = null) {
    global $conn;
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return false;
    }
    
    if (!empty($params)) {
        if ($types === null) {
            $types = str_repeat('s', count($params));
        }
        
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    
    return $stmt;
}

function fetchAll($sql, $params = [], $types = null) {
    $stmt = executeQuery($sql, $params, $types);
    
    if (!$stmt) {
        return false;
    }
    
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    
    $stmt->close();
    
    return $rows;
}

function fetchOne($sql, $params = [], $types = null) {
    $stmt = executeQuery($sql, $params, $types);
    
    if (!$stmt) {
        return false;
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    
    return $row;
}

function insert($table, $data) {
    global $conn;
    
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    
    $stmt = executeQuery($sql, array_values($data));
    
    if (!$stmt) {
        return false;
    }
    
    $id = $conn->insert_id;
    $stmt->close();
    
    return $id;
}

function update($table, $data, $where, $whereParams = []) {
    $set = [];
    
    foreach ($data as $column => $value) {
        $set[] = "$column = ?";
    }
    
    $setClause = implode(', ', $set);
    
    $sql = "UPDATE $table SET $setClause WHERE $where";
    
    $params = array_merge(array_values($data), $whereParams);
    
    $stmt = executeQuery($sql, $params);
    
    if (!$stmt) {
        return false;
    }
    
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    return $affected > 0;
}

function delete($table, $where, $params = []) {
    $sql = "DELETE FROM $table WHERE $where";
    
    $stmt = executeQuery($sql, $params);
    
    if (!$stmt) {
        return false;
    }
    
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    return $affected > 0;
}
?>