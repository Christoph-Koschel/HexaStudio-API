<?php


class SkillsHandler extends Plugin
{
    const LOGICAL_NAME = "skills.handler";

    public function __construct()
    {
        parent::__construct(self::LOGICAL_NAME);
    }

    public function verify(Request $req): bool
    {
        return table_can_read($req->token, Skills::LOGICAL_NAME);
    }

    public function fetch(FetchRequest $req): void
    {
        $table = Table::getTable(Skills::LOGICAL_NAME);
        $query = new BulgFetchQuery([Skills::Name, Skills::Resource], Skills::LOGICAL_NAME);

        $res = $table->fetch($query);
        for ($i = 0; $i < $res->count(); $i++) {
            $row = $res->get($i);
            res_push_attributes(array(
                "name" => $row->get(Skills::Name),
                "resource" => $row->get(Skills::Resource)
            ));
        }
    }

    public function update(UpdateRequest $req): void
    {
        res_send(STATUS_NOT_SUPPORT);
    }

    public function delete(DeleteRequest $req): void
    {
        res_send(STATUS_NOT_SUPPORT);
    }

    public function insert(InsertRequest $req): void
    {
        res_send(STATUS_NOT_SUPPORT);
    }
}

Plugin::register(new SkillsHandler());