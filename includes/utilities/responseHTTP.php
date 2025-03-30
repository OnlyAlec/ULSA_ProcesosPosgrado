<?php

require_once 'handleErrors.php';

function responseOK($data)
{
    http_response_code(200);
    header('Content-Type: application/json');

    return json_encode([
        'success' => true,
        'data' => $data,
        'errors' => ErrorList::getAll()
    ]);
}

function responseBadRequest($data)
{
    http_response_code(400);
    header('Content-Type: application/json');

    return json_encode([
        'success' => false,
        'data' => $data,
        'errors' => ErrorList::getAll()
    ]);
}

function responseInternalError($data)
{
    http_response_code(500);
    header('Content-Type: application/json');

    return json_encode([
        'success' => false,
        'data' => $data,
        'errors' => ErrorList::getAll()
    ]);
}
