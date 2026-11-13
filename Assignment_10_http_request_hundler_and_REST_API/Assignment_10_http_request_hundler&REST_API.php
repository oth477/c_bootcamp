<?php
declare(strict_types=1);

// 1. Set the response header programmatically to return JSON format
header("Content-Type: application/json; charset=UTF-8");

// 2. Read the incoming HTTP request method from the server superglobal
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// 3. Extract incoming HTTP headers and check for the Authorization security header
$headers = function_exists('getallheaders') ? getallheaders() : [];
$normalizedHeaders = array_change_key_case($headers, CASE_LOWER);
$authHeader = $normalizedHeaders['authorization'] ?? 'No Authorization header provided';

// 4. Read raw data from the request body using php://input stream
$rawBody = file_get_contents("php://input");
$inputData = null;

if (!empty($rawBody)) {
    // First, attempt to decode the body as JSON
    $decodedJson = json_decode($rawBody, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        $inputData = $decodedJson;
    } elseif (!empty($_POST)) {
        // Fallback: If sent via a standard HTML <form>, read from $_POST
        $inputData = $_POST;
    } else {
        // Fallback for PUT/DELETE URL-encoded form data, or reject malformed JSON
        $contentType = $normalizedHeaders['content-type'] ?? '';
        if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            parse_str($rawBody, $inputData);
        } else {
            http_response_code(400); // 400 Bad Request
            $errorResponse = [
                "status"  => "error",
                "code"    => 400,
                "method"  => $method,
                "message" => "Invalid JSON payload: " . json_last_error_msg()
            ];
            echo json_encode($errorResponse, JSON_PRETTY_PRINT);
            exit;
        }
    }
}

// Initialize a unified response template
$response = [
    "status"        => "success",
    "code"          => 200,
    "method"        => $method,
    "authorization" => $authHeader,
    "message"       => "",
    "data"          => null
];

// 5. Route and process the request based on the HTTP method
switch ($method) {
    case 'GET':
        http_response_code(200);
        $response["code"]    = 200;
        $response["message"] = "GET request successful. Resource data retrieved.";
        $response["data"]    = [
            "query_parameters" => $_GET,
            "sample_record"    => [
                "id"      => 1,
                "service" => "REST API Security Gateway",
                "status"  => "Active"
            ]
        ];
        break;

    case 'POST':
        if (empty($inputData)) {
            http_response_code(400);
            $response["status"]  = "error";
            $response["code"]    = 400;
            $response["message"] = "POST request requires a non-empty body.";
        } else {
            http_response_code(201); // 201 Created
            $response["code"]    = 201;
            $response["message"] = "POST request successful. New account created.";
            $response["data"]    = $inputData;
        }
        break;

    case 'PUT':
        if (empty($inputData)) {
            http_response_code(400);
            $response["status"]  = "error";
            $response["code"]    = 400;
            $response["message"] = "PUT request requires a body to update the resource.";
        } else {
            http_response_code(200);
            $response["code"]    = 200;
            $response["message"] = "PUT request successful. Existing resource updated.";
            $response["data"]    = $inputData;
        }
        break;

    case 'DELETE':
        http_response_code(200);
        $resourceId = $_GET['id'] ?? ($inputData['id'] ?? 'unspecified');
        $response["code"]    = 200;
        $response["message"] = "DELETE request successful. Target resource removed.";
        $response["data"]    = [
            "deleted_resource_id" => $resourceId
        ];
        break;

    default:
        header("Allow: GET, POST, PUT, DELETE");
        http_response_code(405); // 405 Method Not Allowed
        $response["status"]  = "error";
        $response["code"]    = 405;
        $response["message"] = "HTTP method '$method' is not allowed on this endpoint.";
        break;
}

// 6. Output the final structured JSON response
echo json_encode($response, JSON_PRETTY_PRINT);