<?php
// helpers/validator.php

function requireFields(array $data, array $fields): array
{
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        response([
            'success' => false,
            'message' => 'Campos obrigatórios ausentes',
            'missing' => $missing,
        ], 422);
    }

    return $data;
}
