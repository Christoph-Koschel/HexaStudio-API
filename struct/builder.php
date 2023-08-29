<?php

function build_fetch(Request $req): FetchRequest
{
    $req = FetchRequest::map($req);

    if (isset($_POST["columns"])) {
        $req->columns = convert_array($_POST["columns"]);
    }

    if (isset($_POST["count"])) {
        $req->maxCount = $_POST["count"];
    }

    return $req;
}

function build_insert(Request $req): InsertRequest
{
    $req = InsertRequest::map($req);
    return $req;
}

function build_update(Request $req): UpdateRequest
{
    log_error("CORE:BUILDER:UPDATE", "Request failed with code 'STATUS_FORBIDDEN'");
    res_send(STATUS_NOT_SUPPORT);
}

function build_delete(Request $req): DeleteRequest
{
    log_error("CORE:BUILDER:DELETE", "Request failed with code 'STATUS_FORBIDDEN'");
    res_send(STATUS_NOT_SUPPORT);
}