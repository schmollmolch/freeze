<?php

include_once('../config/config.php');

// Retrieve the raw POST data
$rawData = file_get_contents("php://input");

// Decode the JSON data
$payload = json_decode($rawData, true);

// Check if the data was successfully decoded
if ($payload === null) {
    http_response_code(400);
    die('No JSON data');
}

// Check if the data was successfully decoded
if (!array_key_exists('received_at', $payload) || !array_key_exists('uplink_message', $payload) || !array_key_exists('decoded_payload', $payload['uplink_message'])) {
    http_response_code(400);
    die('Malformed JSON data. Did not find uplink_message.decoded_payload or received_at');
}
$decodedValues = $payload['uplink_message']['decoded_payload'];
$timestamp = date('Y-m-d H:i:s', strtotime($payload['received_at']));


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

    foreach ($decodedValues as $sensor => $value) {
        file_put_contents(
            'webhook.log',
            "$sensor: $value \r\n",
            FILE_APPEND
        );

        // Bind parameters
        $stmt->bind_param("sss", $sensor, $value, $timestamp);

        // Execute the query
        if (!$stmt->execute()) {
            // Log the successful insertion
            // Log errors if the insertion fails
            file_put_contents('webhook.log', 'Error inserting data into database: ' . $conn->error, FILE_APPEND);
            http_response_code(500);
            die('Error storing webhook data in the database');
        }
    }
    http_response_code(200);
    echo 'Webhook data received and stored successfully';

    // Close the database connection
    $stmt->close();
    $conn->close();
} else {
    file_put_contents('webhook.log', "Timestamp: $timestamp \r\n", FILE_APPEND);
    foreach ($decodedValues as $key => $value) {
        file_put_contents('webhook.log', "$key: $value \r\n", FILE_APPEND);
    }
    file_put_contents('webhook.log', "\r\n\r\n", FILE_APPEND);
    http_response_code(200);
    echo 'Webhook data received successfully';
}
