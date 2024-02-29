<?php
require_once "../encrypters/MD5Encrypter.php";

define("max_string_length", 4);

$strategy = $argv[1] ?? NULL;
$hash = $argv[2] ?? NULL;
$wordListFilename = $argv[3] ?? NULL;

echo "Strategy: $strategy\n";
echo "Hash: $hash\n";

if (!in_array($strategy, ["brute-force", "word-list"])) {
    echo "Error: invalid decrypt strategy. Choose between 'brute-force' and 'word-list'\n";
    exit(1);
}
if (!$hash) {
    echo "Error: no hash provided\n";
    exit(1);
}
if ($strategy === "word-list" && !$wordListFilename) {
    echo "Error: the selected strategy is 'word-list' but no list was provided\n";
    exit(1);
}
if ($strategy === "word-list" && !file_exists($wordListFilename)) {
    echo "Error: the selected strategy is 'word-list' but the list file provided does not exist\n";
    exit(1);
}

$strategies = makeStrategies();
$result = $strategies[$strategy]($hash, $wordListFilename);
echo "Result: $result\n";







function bruteForce($hash): ?string {
    $encrypter = new MD5Encrypter;
    $testString = " ";
    while (!is_null($testString)) {
        $md5 = $encrypter->myMD5($testString);
        if ($md5 === $hash) {
            return $testString;
        }
        $testString = incrementString($testString);
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
function wordList($hash, $filename=NULL) {
    $fp = @fopen("$filename", "r");
    if (!$fp) {
        return NULL;
    }
    $result = NULL;
    $encrypter = new MD5Encrypter;
    while (($buffer = fgets($fp, 512)) !== FALSE) {
        $word = substr($buffer, 0, -1); // removes '\n'
        $md5 = $encrypter->myMD5($word);
        if ($md5 === $hash) {
            $result = $word;
            break;
        }
    }
    if (!$result && !feof($fp)) {
        echo "Error: Unable to read the entire file\n";
    }
    fclose($fp);
    return $result;
};
function makeStrategies() {
    $bruteForce = function($hash) {
        return bruteForce($hash);
    };
    $wordList = function($hash, $filename=NULL) {
        return wordList($hash, $filename);
    };
    return [
        "brute-force" => $bruteForce,
        "word-list" => $wordList
    ];
}