<?php

const BASE_URL = "https://api.github.com/repos/";

const API_COMMITS = "commits";


function build_url(string $user, string $repo, string $section): string
{
    return BASE_URL . $user . "/" . $repo . "/" . $section;
}

function do_request(string $url, string $accessToken): array|null
{
    $req = curl_init($url);
    curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($req, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$accessToken}",
        "Accept: application/vnd.github.v3+json",
        "User-Agent: Awesome-Octocat-App"
    ]);

    $res = curl_exec($req);
    $code = curl_getinfo($req, CURLINFO_HTTP_CODE);
    curl_close($req);

    if ($code == 200) {
        return json_decode($res, true);
    } else {
        log_error("JOB::GITHUB", "ERROR ON REQUEST" . $res);
        print "ERROR ON REQUEST\n";
        print $res;
        return null;
    }
}