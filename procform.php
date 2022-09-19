<?php

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $params = json_decode($input, true);

    $regex = [
        'phone' => '/^[0-9]{10}$/',
        'firstname' => '/^[а-яА-Я]{2,255}$/',
        'lastname' => '/^[а-яА-Я]{2,255}$/',
        'contract_number' => '/^[а-яА-Я0-9]{2,255}$/',
        'person_type' => '/^natural_person|legal_person$/'
    ];

    $error = [];
    $result = [];

    foreach($regex as $fieldName => $fieldRegex) {
        if(array_key_exists($fieldName, $params)) {
            $result[$fieldName] = preg_match($fieldRegex, $params[$fieldName]);
        }
    }

    $fieldRequired = [
        'phone', 'firstname', 'lastname', 'person_type'
    ];

    $value = [];

    foreach($fieldRequired as $fieldName) {
        if(!array_key_exists($fieldName, $result) || $result[$fieldName] === 0) {
            $error[$fieldName] = true;
        } else {
            $value[$fieldName] = $params[$fieldName];
        }
    }
    
    if(!$error['person_type']) {
        if($params['person_type'] === 'legal_person') {
            $key = 'contract_number';
            if(!array_key_exists($key, $result) || $result[$key] === 0) {
                $error[$key] = true;
            } else {
                $value[$key] = $params[$key];
            }
        }
    }

    $response = [
        'error' => $error,
        'order' => $value
    ];

    if(count($error) > 0) {
        echo json_encode($response);
        http_response_code(422);
        die('Unprocessable Entity');
    }
    try {

        $db = new PDO('pgsql:host=localhost;port=5432;dbname=test;user=postgres;password=postgres');

        $field = array_keys($value);

        $fieldString = implode(',', array_map(function ($value) { 
            return '"'.$value.'"';
        }, $field));

        $paramString = implode(',', array_map(function ($value) { 
            return ':'.$value; 
        }, $field));

        $sql = sprintf('insert into "orders" (%s) values (%s)', $fieldString, $paramString);

        $statement = $db->prepare($sql);
        if($statement === false) {
            // print_r($db->errorInfo());
            http_response_code(500);
            die('Internal Server Error');
        }

        if($statement->execute($value) === false) {
            // print_r($statement->errorInfo());
            http_response_code(500);
            die('Internal Server Error');
        }

    } catch(PDOException $e) {
        http_response_code(500);
        die('Internal Server Error');
    }
    
    echo json_encode($response);
    http_response_code(201);
    
} else {
    // Метод не разрешён
    http_response_code(405);
    die('Method Not Allowed');
}
