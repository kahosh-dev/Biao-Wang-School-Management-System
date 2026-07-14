<?php

require_once 'functions.php';

// Allow basic cross-origin requests for API usage (adjust for production)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try{

    // ensure DB connection is available
    $db = db();
    if(!$db){
        throw new Exception('Database connection not available');
    }

    $sql = "SELECT
                students.id AS student_id,
                students.name AS student_name,
                students.reg_no AS reg_no,
                marks.subject AS subject,
                marks.marks AS marks,
                marks.term AS term
            FROM marks
            JOIN students ON marks.student_id = students.id";

    $stmt = db_prepare_execute($sql);

    // mysqli_stmt::get_result may not be available in all builds; provide a fallback
    $data = [];

    if(method_exists($stmt, 'get_result')){
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()){
            $data[] = $row;
        }
    } else {
        // fallback: bind_result
        $stmt->bind_result($student_id, $student_name, $reg_no, $subject, $marks_val, $term);
        while($stmt->fetch()){
            $data[] = [
                'student_id' => $student_id,
                'student_name' => $student_name,
                'reg_no' => $reg_no,
                'subject' => $subject,
                'marks' => $marks_val,
                'term' => $term
            ];
        }
    }

    echo json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);

}catch(Exception $e){

    http_response_code(500);
    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

?>