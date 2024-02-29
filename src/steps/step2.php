<?php
require_once "../encrypters/MD5Encrypter.php";
define("string_length", 4);

$inputStrings = [
    "7a95bf926a0333f57705aeac07a362a2",
    "08054846bbc9933fd0395f8be516a9f9"
];

foreach ($inputStrings as $str) {
    $result = bruteForce($str);
    echo "result: $result\n";
}


function bruteForce(string $hash): ?string {
    $encrypter = new MD5Encrypter;
    $testString = 1;
    $testString = "    ";
    while (!is_null($testString)) {
        $md5 = $encrypter->myMD5($testString);
        if ($md5 === $hash) {
            return $testString;
        }
        $testString = incrementStringKeepingLength($testString);
    }
    return NULL;
}

function incrementStringKeepingLength(string $string): ?string {
    for ($i = string_length - 1; $i >= 0; $i--) {
        $ord = ord($string[$i]);
        $string[$i] = $ord === 126 ? chr(32) : chr($ord + 1);
        if ($ord !== 126) {
            break;
        }
    }
    return $string !== "    " ? $string : NULL;
}


