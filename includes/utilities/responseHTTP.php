<?php

function responseOK($data)
{
    return [
        'success' => true,
        'data' => $data,
        'errors' => ErrorList::getAll()
    ];
}

function responseError($data)
{
    return [
        'success' => false,
        'data' => $data,
        'errors' => ErrorList::getAll()
    ];
}
