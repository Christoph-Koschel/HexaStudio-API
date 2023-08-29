<?php

include "utils/requester.php";
include "utils/database.php";
include "../logger.php";

$db = open_db(__DIR__ . "/../conf.ini");
init_log(__DIR__ . "/../logs/projects.log");

log_info("JOB::PROJECTS", "===================== START OF TASK =====================");
log_info("JOB::PROJECTS", "Start tracking process");
log_info("JOB::PROJECTS", "Read notifies");

function generateUniqueTmpSubfolder(): string
{
    $tmpFolder = sys_get_temp_dir();

    do {
        $subfolderName = uniqid('tmp_', true);
        $subfolderPath = $tmpFolder . DIRECTORY_SEPARATOR . $subfolderName;
    } while (file_exists($subfolderPath));

    mkdir($subfolderPath);
    return $subfolderPath;
}

$notifies = read_notifies($db);

foreach ($notifies as $notify) {
    $detailID = $notify["details"];
    $tmp = generateUniqueTmpSubfolder();
    echo $tmp . "\n";
    chdir($tmp);
    exec("git clone {$notify["github"]}");
    $output = array();
    exec("cloc .", $output);
    array_shift($output);
    array_shift($output);
    array_shift($output);
    array_shift($output);

    $line = array_shift($output);
    $toUpdate = array();

    while (!str_starts_with($line, "----") && !str_ends_with($line, "----")) {
        $parts = explode(" ", $line);
        $displayName = trim($parts[0]);
        $name = str_replace(" ", "_", strtolower($displayName));
        $code = (int)trim($parts[count($parts) - 1]);
        echo $name . "[$displayName]: " . $code . "\n";

        if (!lang_exists($db, $name)) {
            log_info("JOB::PROJECTS", "Create language '$displayName'");
            create_lang($db, $name, $displayName, "#E5E5E5");
        }

        $toUpdate[] = array(
            "name" => $name,
            "value" => $code
        );

        $line = array_shift($output);
    }

    $sum = array_shift($output);
    $parts = explode(" ", $sum);
    $complete = (int)$parts[count($parts) - 1];

    $fakeSum = 0;
    for ($i = 0; $i < count($toUpdate); $i++) {
        $percentage = round($toUpdate[$i]["value"] / $complete * 100, 2);
        $fakeSum += $percentage;
        $toUpdate[$i]["value"] = $percentage;
    }
    $toUpdate[count($toUpdate) - 1]["value"] = round(abs(100 - $fakeSum), 2);

    log_info("JOB::PROJECTS", "Update project '{$notify["name"]}'");
    foreach ($toUpdate as $update) {
        log_info("JOB::PROJECTS", "Update details '{$update["name"]}'");
        if (detail_exists($db, $notify["details"], $update["name"])) {
            update_detail($db, $notify["details"], $update["name"], $update["value"]);
        } else {
            create_detail($db, $notify["details"], $update["name"], $update["value"]);
        }
    }

    $firstFolder = scandir($tmp)[2];
    if (file_exists("./$firstFolder/readme.md")) {
        log_info("JOB::PROJECTS", "Update description");
        update_resource($db, $notify["description"], file_get_contents("./$firstFolder/readme.md"));
    }
    if (file_exists("./README.md")) {
        log_info("JOB::PROJECTS", "Update description");
        update_resource($db, $notify["description"], file_get_contents("./$firstFolder/README.md"));
    }

    remove_notify($db, $notify["github"]);

    chdir(__DIR__);
}