<?php

include_once('../config/config.php');

$value_count = null;
if (array_key_exists('value_count', $_GET)) {
    $value_count = $_GET["value_count"];
}

$sensor = null;
if (array_key_exists('sensor', $_GET)) {
    $sensor = $_GET["sensor"];
}

$latest = array_key_exists('latest', $_GET);

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
    if ($value_count === null) {
        $value_count = 1;
    }

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

    if ($value_count === null) {
        $value_count = 100;
    }

    // Process the data and store it in the database
    // Customize the SQL query based on your database schema
    $sql = "SELECT sensor_name, sensor_value, timestamp
FROM (
    SELECT sensor_name, sensor_value, timestamp,
           ROW_NUMBER() OVER (PARTITION BY sensor_name ORDER BY timestamp DESC) AS row_num
    FROM freeze_data
) ranked
WHERE row_num = 1 LIMIT ?";

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
