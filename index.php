<?php
// FOR DEBUG REASONS ONLY
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
// ========================
include "logger.php";

init_log(__DIR__ . "/logs/api.log");

function load($dir): void
{
    foreach (scandir($dir) as $entry) {
        if ($entry == "index.php" || $entry == "logger.php" || $entry == "." || $entry == ".." || $entry == "jobs") {
            continue;
        }

        $path = $dir . "/" . $entry;

        if (is_dir($path)) {
            load($path);
        } else if (str_ends_with($entry, ".php")) {
            include $path;
        }
    }
}

load(__DIR__);

$req = new Request();

$req_string = $_SERVER['REQUEST_URI'];
$req_parts = explode("/", $req_string);

if (count($req_parts) >= 2 && $req_parts[count($req_parts) - 2] == "resource") {
    Table::init(__DIR__ . "/conf.ini");
    $table = Table::getTable(Resource::LOGICAL_NAME);
    $query = new FilterQuery([Resource::Data], Resource::LOGICAL_NAME);
    $query->addCriteria(Resource::URL, "=", sql_str("resource/" . $req_parts[count($req_parts) - 1]));
    $res = $table->fetch($query);

    if ($res->count() == 0) {
        var_dump($res);
    }

    echo $res->get(0)->get(Resource::Data);
    exit(0);
}

switch ($req_parts[count($req_parts) - 1]) {
    case "fetch":
        $req->method = METHOD_FETCH;
        break;
    case "update":
        $req->method = METHOD_UPDATE;
        break;
    case "insert":
        $req->method = METHOD_INSERT;
        break;
    case "delete":
        $req->method = METHOD_DELETE;
        break;
    default:
        log_error("CORE:PREBUILD", "Request failed with code 'STATUS_UNDEFINED_METHOD'");
        res_send(STATUS_UNDEFINED_METHOD);
}

if (!isset($_POST["logicalName"])) {
    res_set_attribute("error", "Missing argument 'logicalName'");
    log_error("CORE:PREBUILD", "Request failed with code 'STATUS_CORRUPTED_REQUEST'");
    res_send(STATUS_CORRUPTED_REQUEST);
}
$req->logicalName = $_POST["logicalName"];
res_set_logicalName($req->logicalName);

if (!isset($_POST["token"])) {
    res_set_attribute("error", "Missing argument 'token'");
    log_error("CORE:PREBUILD", "Request failed with code 'STATUS_CORRUPTED_REQUEST'");
    res_send(STATUS_CORRUPTED_REQUEST);
}

$req->token = $_POST["token"];

if (!isset($_POST["authName"])) {
    res_set_attribute("error", "Missing argument 'authName'");
    log_error("CORE:PREBUILD", "Request failed with code 'STATUS_CORRUPTED_REQUEST'");
    res_send(STATUS_CORRUPTED_REQUEST);
}

$req->authName = $_POST["authName"];
log_info("CORE:PREBUILD", "Request from $req->authName");

$req->arguments = new RequestProperties($_POST, $req->logicalName);

$plugin = Plugin::getPlugin($req->logicalName);
if (!$plugin) {
    log_error("CORE:PREBUILD", "Request failed with code 'STATUS_PLUGIN_NOT_FOUND'");
    res_send(STATUS_PLUGIN_NOT_FOUND);
}

Table::init(__DIR__ . "/conf.ini");

if (!$plugin->verify($req)) {
    res_set_attribute("error", "Plugin $plugin->logicalName forbid request");
    log_error("CORE:SECURITY", "Plugin $plugin->logicalName forbid request");
    res_send(STATUS_FORBIDDEN);
}

switch ($req->method) {
    case METHOD_FETCH:
        $req = build_fetch($req);
        log_info("CORE:PREBUILD", "Execute 'fetch' on $plugin->logicalName");
        $plugin->fetch($req);
        break;
    case METHOD_INSERT:
        $req = build_insert($req);
        log_info("CORE:PREBUILD", "Execute 'insert' on $plugin->logicalName");
        $plugin->insert($req);
        break;
    case METHOD_UPDATE:
        $req = build_update($req);
        log_info("CORE:PREBUILD", "Execute 'update' on $plugin->logicalName");
        $plugin->update($req);
        break;
    case METHOD_DELETE:
        $req = build_delete($req);
        log_info("CORE:PREBUILD", "Execute 'delete' on $plugin->logicalName");
        $plugin->delete($req);
        break;
    default:
        log_error("CORE:PREBUILD", "Request failed with code 'STATUS_UNDEFINED_METHOD'");
        res_send(STATUS_UNDEFINED_METHOD);
}

res_send();

