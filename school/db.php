<?php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'school_system';


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* CREATE CONNECTION */

$mysqli = new mysqli(
    $DB_HOST,
    $DB_USER,
    $DB_PASS,
    $DB_NAME
);

/* CHECK CONNECTION */

if ($mysqli->connect_error) {

    die(
        "Connection Failed : " .
        $mysqli->connect_error
    );
}

/* CHARACTER SET */

$mysqli->set_charset("utf8mb4");

?>