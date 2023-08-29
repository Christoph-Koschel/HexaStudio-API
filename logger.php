<?php

$file = null;
function init_log(string $path)
{
    global $file;
    $file = fopen($path, "a");
    if (!$file) {
        print 'Unable to open ' . $file;
        exit(1);
    }

    return $file;
}

function log_write(string $type, string $sender, string $message): void
{
    global $file;
    $date = date('Y-m-d H:i:s,v');
    $message = "\n" . $message;
    $message = str_replace("\n", "\n" . str_repeat(" ", 34), $message);
    $logEntry = "$date   $type - $sender$message\n";
    fwrite($file, $logEntry);
}

function log_append(string $message): void
{
    global $file;
    $message = str_replace("\n", "\n" . str_repeat(" ", 34), $message);
    fwrite($file, str_repeat(" ", 34) . $message . "\n");
}

function log_info(string $sender, string $message): void
{
    log_write("   INFO", $sender, $message);
}

function log_warning(string $sender, string $message): void
{
    log_write("WARNING", $sender, $message);
}

function log_error(string $sender, string $message): void
{
    log_write("  ERROR", $sender, $message);
}
