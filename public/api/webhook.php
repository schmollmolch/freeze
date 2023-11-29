<?php

// include_once('../config/config.php');

// Retrieve the raw POST data
$rawData = file_get_contents("php://input");

// Decode the JSON data
$data = json_decode($rawData, true);

// Check if the data was successfully decoded
if ($data === null) {
    http_response_code(400);
    die('Invalid JSON data');
}

if (isset($servername)) {
    // Create a database connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check the connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Process the data and store it in the database
    // Customize the SQL query based on your database schema
    $sql = "INSERT INTO freeze_data (sensor_name, sensor_value, timestamp) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bind_param("sss", $data['sensor'], $data['value'], $data['timestamp']);

    // Execute the query
    if ($stmt->execute()) {
        // Log the successful insertion
        file_put_contents('webhook.log', 'Data inserted into database: ' . print_r($data, true), FILE_APPEND);
        http_response_code(200);
        echo 'Webhook data received and stored successfully';
    } else {
        // Log errors if the insertion fails
        file_put_contents('webhook.log', 'Error inserting data into database: ' . $conn->error, FILE_APPEND);
        http_response_code(500);
        echo 'Error storing webhook data in the database';
    }

    // Close the database connection
    $stmt->close();
    $conn->close();
} else {
    file_put_contents('webhook.log', 'Webhook received: ' . print_r($data, true), FILE_APPEND);
    http_response_code(200);
    echo 'Webhook data received successfully';
}
