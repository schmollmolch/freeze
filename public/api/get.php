<?php

include_once('../config/config.php');

$value_count = 1;
if (array_key_exists('value_count', $_GET)) {
    $value_count = $_GET["value_count"];
}

$sensor = null;
if (array_key_exists('sensor', $_GET)) {
    $sensor = $_GET["sensor"];
}

if (isset($servername)) {
    // Create a database connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check the connection
    if ($conn->connect_error) {
        http_response_code(500);
        die("Connection failed: " . $conn->connect_error);
    }
    if ($sensor === null) {
        getAllSensors($conn, $value_count);
    } else {
        getSensorValues($conn, $sensor, $value_count);
    }

    $conn->close();
}

function getSensorValues($conn, $sensor, $value_count)
{
    // Process the data and store it in the database
    // Customize the SQL query based on your database schema
    $sql = "SELECT * FROM freeze_data WHERE sensor_name=? ORDER BY timestamp DESC LIMIT ?";
    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bind_param("si", $sensor, $value_count);

    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo ('Sensor not found');
    } else {
        http_response_code(200);
        $queryresults = array();
        while ($row = $result->fetch_assoc()) {
            $queryresults[] = $row;
        }
        echo (json_encode($queryresults));
    }
    // Close the database connection
    $stmt->close();
}

function getAllSensors($conn, $value_count)
{
    // Process the data and store it in the database
    // Customize the SQL query based on your database schema
    $sql = "SELECT DISTINCT sensor_name FROM freeze_data LIMIT ?";
    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bind_param("i", $value_count);

    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo ('No sensors found. Probably never received any data.');
    } else {
        http_response_code(200);
        $queryresults = array();
        while ($row = $result->fetch_assoc()) {
            $queryresults[] = $row;
        }
        echo (json_encode($queryresults));
    }
    // Close the database connection
    $stmt->close();
}
