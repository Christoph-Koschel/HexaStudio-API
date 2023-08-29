<?php

use JetBrains\PhpStorm\NoReturn;

const RESPONSE_KEY = "__response__";

$_POST[RESPONSE_KEY] = array();
$_POST[RESPONSE_KEY]["attributes"] = array();
$_POST[RESPONSE_KEY]["logicalName"] = "";
$_POST[RESPONSE_KEY]["canBeArray"] = true;
$_POST[RESPONSE_KEY]["isArray"] = false;

function res_set_logicalName(string $name): void
{
    $_POST[RESPONSE_KEY]["logicalName"] = $name;
}

function res_set_attribute(string $key, string|bool|int|float|array $value): void
{
    if ($_POST[RESPONSE_KEY]["isArray"]) {
        throw new Exception("Cannot set key on an array attribute");
    }
    $_POST[RESPONSE_KEY]["canBeArray"] = false;

    $_POST[RESPONSE_KEY]["attributes"][$key] = $value;
}

function res_push_attributes(array $value): void
{
    if (!$_POST[RESPONSE_KEY]["isArray"] && !$_POST[RESPONSE_KEY]["canBeArray"]) {
        throw new Exception("Cannot push an attribute on a non array attributes");
    }

    $_POST[RESPONSE_KEY]["isArray"] = true;

    $_POST[RESPONSE_KEY]["attributes"][] = $value;
}

#[NoReturn] function res_send(int $code = STATUS_OK): void
{
    $wrapper = array(
        "code" => $code,
        "url" => (empty($_SERVER["HTTPS"]) ? "http" : "https") . "://{$_SERVER["HTTP_HOST"]}{$_SERVER["REQUEST_URI"]}",
        "data" => $_POST[RESPONSE_KEY]["attributes"],
        "logicalName" => $_POST[RESPONSE_KEY]["logicalName"]
    );
    header("Content-Type: application/json");
    echo json_encode($wrapper, JSON_PRETTY_PRINT);
    exit(0);
}