<?php

require_once "../../infra/db/mysql/PDOHelper.php";
require_once "../../dbconfig.php";

define("max_string_length", 4);

$hash = $argv[1] ?? NULL;

if (!$hash) {
    echo "Error: no hash provided\n";
    exit(1);
}

$result = decrypt($hash);
echo "Result: $result\n";

function decrypt(string $hash): ?string {
    $pdo = new PDOHelper;
    $result = $pdo->getByOneField($hash, "hash", ["id", "word", "hash"], "rainbow_table");
    if (empty($result)) {
        return NULL;
    }
    $word = $result[0]->word;
    return $word;

}