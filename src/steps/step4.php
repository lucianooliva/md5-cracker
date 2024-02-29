<?php

require_once "../encrypters/MD5Encrypter.php";
require_once "../../infra/db/mysql/PDOHelper.php";
require_once "../../dbconfig.php";

define("max_string_length", 4);

$process = $argv[1] ?? NULL;
$wordListFilename = $argv[2] ?? NULL;

if (!in_array($process, ["all-permutations", "word-list"])) {
    echo "Error: invalid process. Choose between 'all-permutations' and 'word-list'\n";
    exit(1);
}
if ($process === "word-list" && !$wordListFilename) {
    echo "Error: the selected process is 'word-list' but no list was provided\n";
    exit(1);
}
if ($process === "word-list" && !file_exists($wordListFilename)) {
    echo "Error: the selected process is 'word-list' but the list file provided does not exist\n";
    exit(1);
}

$processes = makeProcesses();
$processes[$process]($wordListFilename);




function allPermutations(): ?string {
    $encrypter = new MD5Encrypter;
    $pdo = new PDOHelper;
    $word = " ";
    while (!is_null($word)) {
        $md5 = $encrypter->myMD5($word);
        savePair($pdo, $word, $md5);
        $word = incrementString($word);
    }
    return NULL;
}

function incrementString(string $string): ?string {
    $stringLength = strlen($string);
    if ($stringLength > max_string_length) {
        return NULL;
    }
    for ($i = $stringLength - 1; $i >= 0; $i--) {
        $ord = ord($string[$i]);
        $string[$i] = $ord === 126 ? chr(32) : chr($ord + 1);
        if ($ord !== 126) {
            break;
        }
    }
    $zeroString = str_pad("", $stringLength, " ", STR_PAD_LEFT);
    return $string !== $zeroString ? $string : incrementString($zeroString . " ");
}
function wordList($filename=NULL) {
    $fp = @fopen("$filename", "r");
    if (!$fp) {
        return NULL;
    }
    $result = NULL;
    $encrypter = new MD5Encrypter;
    $pdo = new PDOHelper;
    while (($buffer = fgets($fp, 512)) !== FALSE) {
        $word = substr($buffer, 0, -1); // removes '\n'
        $md5 = $encrypter->myMD5($word);
        savePair($pdo, $word, $md5);
    }
    if (!$result && !feof($fp)) {
        echo "Error: Unable to read the entire file\n";
    }
    fclose($fp);
    return $result;
};
function makeProcesses() {
    $allPermutations = function() {
        return allPermutations();
    };
    $wordList = function($filename=NULL) {
        return wordList($filename);
    };
    return [
        "all-permutations" => $allPermutations,
        "word-list" => $wordList
    ];
}

function savePair(PDOHelper $pdo, string $word, string $hash) {
    $fields = [
        "word" => $word,
        "hash" => $hash
    ];
    $pdo->add($fields, "rainbow_table");
}