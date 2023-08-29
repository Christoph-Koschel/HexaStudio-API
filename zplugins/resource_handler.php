<?php

class ResourceHandler extends Plugin
{
    const LOGICAL_NAME = "resource.handler";

    public function __construct()
    {
        parent::__construct(self::LOGICAL_NAME);
    }

    public function verify(Request $req): bool
    {
        return table_can_read($req->token, Resource::LOGICAL_NAME);
    }

    public function fetch(FetchRequest $req): void
    {
        $table = Table::getTable(Resource::LOGICAL_NAME);
        $query = new FilterQuery([Resource::Name, Resource::URL], Resource::LOGICAL_NAME);
        $name = $req->arguments->get("name");
        $query->addCriteria(Resource::Name, "=", sql_str($name));

        $res = $table->fetch($query);
        if ($res->count() == 0) {
            res_set_attribute("error", "Cannot find '$name'");
            res_send(STATUS_FAIL);
        }

        $row = $res->get(0);
        res_set_attribute(Resource::Name, $row->get(Resource::Name));
        res_set_attribute(Resource::URL, $row->get(Resource::URL));
    }

    public function update(UpdateRequest $req): void
    {
        res_send(STATUS_FORBIDDEN);
    }

    public function delete(DeleteRequest $req): void
    {
        res_send(STATUS_FORBIDDEN);
    }

    public function insert(InsertRequest $req): void
    {
        res_send(STATUS_FORBIDDEN);
    }
}

Plugin::register(new ResourceHandler());