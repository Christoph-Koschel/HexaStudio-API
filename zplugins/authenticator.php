<?php

class Authenticator extends Plugin
{
    const LOGICAL_NAME = "authenticator";

    const ACTION_LOGIN = "login";
    const ACTION_VALID = "valid";

    public function __construct()
    {
        parent::__construct(self::LOGICAL_NAME);
    }

    public function verify(Request $req): bool
    {
        return table_can_read($req->token, Account::LOGICAL_NAME);
    }

    public function fetch(FetchRequest $req): void
    {
        switch ($req->arguments->get("action")) {
            case self::ACTION_LOGIN:
                $this->actionLogin($req);
                break;
            case self::ACTION_VALID:
                $this->actionValid($req);

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

    public function actionLogin(FetchRequest $req): void
    {
        $table = Table::getTable(Account::LOGICAL_NAME);
        $query = new FilterQuery(false, Account::LOGICAL_NAME);
        $query->addCriteria(Account::Username, "=", sql_str($req->arguments->get("username")));
        $res = $table->fetch($query);
        if ($res->count() != 1) {
            res_set_attribute("error", "Account does not exists");
            res_send(STATUS_FAIL);
        }

        $row = $res->get(0);
        if (password_verify($req->arguments->get("password"), $row->get(Account::Password))) {
            res_set_attribute("token", $row->get(Account::Token));
        } else {
            res_set_attribute("error", "Password of the account is wrong");
            res_send(STATUS_FAIL);
        }
    }

    private function actionValid(FetchRequest $req): void
    {
        $table = Table::getTable(Account::LOGICAL_NAME);
        $query = new FilterQuery(false, Account::LOGICAL_NAME);
        $query->addCriteria(Account::Token, "=", sql_str($req->token));
        $res = $table->fetch($query);
        res_set_attribute("valid", $res->count() != 0);
    }
}

Plugin::register(new Authenticator());

