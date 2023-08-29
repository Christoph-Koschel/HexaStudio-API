<?php

function open_db(string $file): PDO
{
    if (!$settings = parse_ini_file($file, TRUE)) {
        print 'Unable to open ' . $file;
        exit(1);
    }

    $dns = $settings['DATABASE']['driver'] .
        ':host=' . $settings['DATABASE']['host'] .
        ((!empty($settings['DATABASE']['port'])) ? (';port=' . $settings['DATABASE']['port']) : '') .
        ';dbname=' . $settings['DATABASE']['schema'];

    $db = new PDO($dns, $settings["DATABASE"]["username"], $settings["DATABASE"]["password"]);
    return $db;
}

function read_tracks(PDO $db): array
{
    if (!$res = $db->query("SELECT username, repo, token FROM ghtracks")) {
        log_error("JOB::GITHUB", "Failed to execute SQL" . $db->errorInfo());
        echo "FAILED to execute SQL";
        exit(1);
    }
    return $res->fetchAll(PDO::FETCH_ASSOC);
}

function commit_exists(PDO $db, string $sha): bool
{
    $sth = $db->prepare("SELECT * FROM ghcommits WHERE sha = :sha");
    $sth->bindParam(":sha", $sha);
    if (!$sth->execute()) {
        log_error("JOB::GITHUB", "Failed to execute SQL" . $db->errorInfo());
        echo "FAILED to execute SQL";
        exit(1);
    }

    return $sth->rowCount() != 0;
}

function get_project(PDO $db, string $github): array|false
{
    $sth = $db->prepare("SELECT * FROM projects WHERE github = :github");
    $sth->bindParam(":github", $github);
    if (!$sth->execute()) {
        return false;
    }

    if ($sth->rowCount() == 0) {
        return false;
    }

    return $sth->fetch(PDO::FETCH_ASSOC);
}

function is_project(PDO $db, string $github): bool
{
    return get_project($db, $github) != false;
}

function notify_change(PDO $db, string $url): void
{
    $sth = $db->prepare("INSERT INTO project_tracker (github) VALUES (:url);");
    $sth->bindParam(":url", $url);

    $sth->execute();
}

function read_notifies(PDO $db): array
{
    $sth = $db->prepare("SELECT * FROM project_tracker");
    $sth->execute();
    $res = array();

    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        $proj = get_project($db, $row["github"]);
        if ($proj) {
            $res[] = $proj;
        }
    }

    return $res;
}

function remove_notify(PDO $db, string $github): void
{
    $sth = $db->prepare("DELETE FROM project_tracker WHERE github = :github");
    $sth->bindParam(":github", $github);
    $sth->execute();
}

function lang_exists(PDO $db, string $name): bool
{
    $sth = $db->prepare("SELECT * FROM languages WHERE name = :name");
    $sth->bindParam(":name", $name);
    if (!$sth->execute()) {
        return false;
    }

    return $sth->rowCount() != 0;
}

function create_lang(PDO $db, string $name, string $displayName, string $color): void
{
    $sth = $db->prepare("INSERT INTO languages (name, display_name, language, color) VALUES (:name, :display, 1, :color)");
    $sth->bindParam(":name", $name);
    $sth->bindParam(":display", $displayName);
    $sth->bindParam(":color", $color);

    $sth->execute();
}

function detail_exists(PDO $db, string $project, string $language): bool
{
    $sth = $db->prepare("SELECT * FROM project_details WHERE project = :project AND language = :lang");
    $sth->bindParam(":project", $project);
    $sth->bindParam(":lang", $language);
    if (!$sth->execute()) {
        return false;
    }

    return $sth->rowCount() != 0;
}

function create_detail(PDO $db, string $project, string $language, float $percentage): void
{
    $sth = $db->prepare("INSERT INTO project_details (project, language, percentage) VALUES (:project, :lang, :perc)");
    $sth->bindParam(":project", $project);
    $sth->bindParam(":lang", $language);
    $sth->bindParam(":perc", $percentage);

    $sth->execute();
}

function update_detail(PDO $db, string $project, string $language, float $percentage): void
{
    $sth = $db->prepare("UPDATE project_details SET percentage = :perc WHERE project = :project AND language = :lang");
    $sth->bindParam(":project", $project);
    $sth->bindParam(":lang", $language);
    $sth->bindParam(":perc", $percentage);

    $sth->execute();
}

function commit_push(PDO $db, string $sha, string $author, string $message, string $date, string $repo): bool
{
    $sth = $db->prepare("INSERT INTO ghcommits (sha, author, message, repo, date) VALUES (:sha, :author, :message, :repo, :date)");
    $sth->bindParam(":sha", $sha);
    $sth->bindParam(":author", $author);
    $sth->bindParam(":message", $message);
    $sth->bindParam(":repo", $repo);
    $sth->bindParam(":date", $date);

    return $sth->execute();
}

function update_resource(PDO $db, string $name, string $content): void
{
    $sth = $db->prepare("UPDATE resource SET data = :data WHERE name = :name");
    $sth->bindParam(":data", $content);
    $sth->bindParam(":name", $name);
    $sth->execute();
}