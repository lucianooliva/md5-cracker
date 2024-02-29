<?php
require_once "../encrypters/MD5Encrypter.php";

$inputStrings = [
    "my MD5 input string",
    "The MD5 message-digest algorithm is a widely used hash function producing a 128-bit hash value and this string is 1024 bits long",
    "*"
];

foreach ($inputStrings as $str) {
    $encrypter = new MD5Encrypter;
    $result = $encrypter->myMD5($str);
    $actual = md5($str);
    echo "my hash:     $result\n";
    echo "actual hash: $actual\n";
    echo "\n";
}




