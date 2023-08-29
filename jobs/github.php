<?php

include "utils/requester.php";
include "utils/database.php";
include "../logger.php";

$db = open_db(__DIR__ . "/../conf.ini");
init_log(__DIR__ . "/../logs/github.log");

log_info("JOB::GITHUB", "===================== START OF TASK =====================");
log_info("JOB::GITHUB", "Start tracking process");
log_info("JOB::GITHUB", "Read tracks");

$tracks = read_tracks($db);
$count = count($tracks);

log_info("JOB::GITHUB", "Resolved $count Tracks");

$count = 0;

foreach ($tracks as $track) {
    log_info("JOB::GITHUB", "Track {$track["username"]}/{$track["repo"]}");
    $url = build_url($track["username"], $track["repo"], API_COMMITS);
    log_info("JOB::GITHUB", "Using url: $url");

    $res = do_request($url, $track["token"]);
    if (!$res) {
        exit(1);
    }

    if (is_project($db, $url)) {
        notify_change($db, $url);
    }

    foreach ($res as $commit) {
        log_info("JOB::GITHUB", "Check if commit '{$commit["sha"]}' already exists");
        if (commit_exists($db, $commit["sha"])) {
            break;
        }

        log_info("JOB::GITHUB", "Add commit '{$commit["sha"]}' to table");
        $count++;

        if (!commit_push($db, $commit["sha"], $commit["commit"]["committer"]["name"], $commit["commit"]["message"], $commit["commit"]["committer"]["date"], $track["username"] . "/" . $track["repo"])) {
            log_error("JOB::GITHUB", "Failed to execute SQL");
            echo "FAILED to execute SQL";
            exit(1);
        }
    }
}

log_info("JOB::GITHUB", "Tracked $count commits");
log_info("JOB::GITHUB", "====================== END OF TASK ======================");