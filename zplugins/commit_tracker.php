<?php


class CommitTracker extends Plugin
{
    const LOGICAL_NAME = "github.tracker";

    public function __construct()
    {
        parent::__construct(self::LOGICAL_NAME);
    }

    public function verify(Request $req): bool
    {
        if ($req->method == METHOD_FETCH) {
            return table_can_read($req->token, GitHubCommits::LOGICAL_NAME);
        }

        return false;
    }

    public function fetch(FetchRequest $req): void
    {
        $table = Table::getTable(GitHubCommits::LOGICAL_NAME);
        $query = new BulgFetchQuery(false, GitHubCommits::LOGICAL_NAME);
        $res = $table->fetch($query);
        for ($i = 0; $i < $res->count(); $i++) {
            $row = $res->get($i);
            res_push_attributes(array(
                "author" => $row->get(GitHubCommits::Author),
                "message" => $row->get(GitHubCommits::Message),
                "repo" => $row->get(GitHubCommits::Repo),
                "date" => $row->get(GitHubCommits::Date)
            ));
        }
    }

    public function update(UpdateRequest $req): void
    {
        // TODO: Implement update() method.
    }

    public function delete(DeleteRequest $req): void
    {
        // TODO: Implement delete() method.
    }

    public function insert(InsertRequest $req): void
    {
        // TODO: Implement insert() method.
    }
}

Plugin::register(new CommitTracker());
