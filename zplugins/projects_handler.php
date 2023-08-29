<?php


class ProjectsHandler extends Plugin
{
    const LOGICAL_NAME = "projects.handler";
    const ACTION_EXISTS = "EXISTS";
    const ACTION_FETCH = "FETCH";
    const ACTION_FETCH_ALL = "FETCH_ALL";

    public function __construct()
    {
        parent::__construct(self::LOGICAL_NAME);
    }

    public function verify(Request $req): bool
    {
        return table_can_read($req->token, Projects::LOGICAL_NAME);
    }

    public function fetch(FetchRequest $req): void
    {
        if ($req->arguments->get("action") == self::ACTION_FETCH_ALL) {
            $this->actionFetchAll();
        } else if ($req->arguments->get("action") == self::ACTION_FETCH) {
            $this->actionFetch($req);
        } else if ($req->arguments->get("action") == self::ACTION_EXISTS) {
            $this->actionExists($req);
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

    public function actionFetchAll(): void
    {
        $table = Table::getTable(Projects::LOGICAL_NAME);
        $query = new BulgFetchQuery([Projects::Name, Projects::PrimaryLanguage, Projects::PageURL, Projects::Banner], Projects::LOGICAL_NAME);

        $res = $table->fetch($query);
        for ($i = 0; $i < $res->count(); $i++) {
            $row = $res->get($i);
            res_push_attributes(array(
                "name" => $row->get(Projects::Name),
                "primary_language" => $row->get(Projects::PrimaryLanguage),
                "page" => $row->get(Projects::PageURL),
                "banner" => $row->get(Projects::Banner)
            ));
        }
    }

    public function actionFetch(FetchRequest $req): void
    {
        $table = Table::getTable(Projects::LOGICAL_NAME);
        $query = new FilterQuery([Projects::Name, Projects::PrimaryLanguage, Projects::PageURL, Projects::Banner, Projects::Description, Projects::Details, Projects::GitHub], Projects::LOGICAL_NAME);
        $query->addCriteria(Projects::Name, "=", sql_str($req->arguments->get("project")), true);
        $res = $table->fetch($query);

        if ($res->count() != 1) {
            res_set_attribute("error", "Cannot find project '{$req->arguments->get("project")}'");
            res_send(STATUS_FAIL);
        }

        $row = $res->get(0);
        res_set_attribute("name", $row->get(Projects::Name));
        res_set_attribute("primary_language", $row->get(Projects::PrimaryLanguage));
        res_set_attribute("page", $row->get(Projects::PageURL));
        res_set_attribute("banner", $row->get(Projects::Banner));
        res_set_attribute("description", $row->get(Projects::Description));
        res_set_attribute("details", $row->get(Projects::Details));
        res_set_attribute("github", $row->get(Projects::GitHub));
    }

    private function actionExists(FetchRequest $req): void
    {
        $table = Table::getTable(Projects::LOGICAL_NAME);
        $query = new FilterQuery([Projects::Name], Projects::LOGICAL_NAME);
        $query->addCriteria(Projects::Name, "=", sql_str($req->arguments->get("project")), true);
        $res = $table->fetch($query);
        res_set_attribute("exists", $res->count() != 0);
    }
}

Plugin::register(new ProjectsHandler());