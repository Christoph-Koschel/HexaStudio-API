<?php

function convert_array(string $data): array
{
    $json = json_decode($data, true);
    if (!$json) {
        log_error("CORE:CONVERT", "Request failed with code 'STATUS_WRONG_ARGUMENT_TYPE'");
        res_send(STATUS_WRONG_ARGUMENT_TYPE);
    }

    return $json;
}