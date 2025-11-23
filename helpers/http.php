<?php
// helpers/http.php

function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function getQueryParams(): array
{
    return $_GET ?? [];
}
