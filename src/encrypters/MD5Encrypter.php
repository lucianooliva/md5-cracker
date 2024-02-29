<?php
class MD5Encrypter {
    public function myMD5($strInput) {
        $binaryArray = $this->convertStringToAsciiArray($strInput);
        // Steps 1 to 5 according to RFC 1321
        [$binaryArrayLen, $blocks] = $this->appendPaddingBits($binaryArray);
        $blockList = $this->appendLength($binaryArrayLen, $blocks);
        $MDBuffer = $this->initializeMDBuffer();
        $finalVectors = $this->processMessageIn16WordBlocks($MDBuffer, $blockList);
        $result = $this->output($finalVectors);
        return $result;
    }
    private function convertStringToAsciiArray(string $strInput): array {
        $len = strlen($strInput);
        $result = [];
        for ($i = 0; $i < $len; $i++) {
            $result[] = ord($strInput[$i]);
        }
        return $result;
    }
    private function appendPaddingBits(array $binaryArray): array {
        $binaryArrayLen = count($binaryArray);
        $blocks = [];
        foreach($binaryArray as $bin) {
            $blocks[] = $bin;
        }
        $blocks[] = 128;
        $length = count($blocks);
        for (; $length % 64 !== 56; $length++) {
            $blocks[] = 0;
        }
        return [$binaryArrayLen, $blocks];
    }
    private function appendLength(int $binaryArrayLen, array $blocks) {
        $result = [];
        $length = count($blocks);
        $blockLength = $binaryArrayLen * 8;
        $lengthBytesArray = $this->makeBytesFromInt($blockLength);
        for ($i = $length, $j = count($lengthBytesArray)-1; $i % 64 > 0; $i++, $j--) {
            $blocks[$i] = $j >= 0 ? $lengthBytesArray[$j] : 0;
        }
        $b = 0;
        for ($i = 0; $i < $length; $i += 64) {
            $result["block-$b"] = [];
            for ($j = 0; $j < 64; $j++) {
                $result["block-$b"][] = $blocks[$i + $j];
            }
            $b++;
        }
        return $result;
    }
    private function initializeMDBuffer() {
        return [
            "A" => 0x67452301,
            "B" => 0xEFCDAB89,
            "C" => 0x98BADCFE,
            "D" => 0x10325476,
        ];
    }
    private function processMessageIn16WordBlocks(array $MDBuffer, array $blockList): array {
        $savedBuffer = $MDBuffer;
        $Z = hexdec("100000000");
        $k = $this->makeKArray();
        $s = $this->makeSArray();
        
        for ($i = 0; $i < count($blockList); $i++) {    
            $block = $blockList["block-$i"];
            $words = $this->makeWords($block);
        
            $buffer = $savedBuffer;
            
            $buffer = $this->round1($words, $buffer, $k, $s);
            $buffer = $this->round2($words, $buffer, $k, $s);
            $buffer = $this->round3($words, $buffer, $k, $s);
            $buffer = $this->round4($words, $buffer, $k, $s);
        
            foreach (["A", "B", "C", "D"] as $word) {
                $savedBuffer[$word] = $this->modularAddition($savedBuffer[$word], $buffer[$word], $Z);
            }
        }
        return $savedBuffer;
    }
    private function output(array $finalVectors): string {
        foreach ($finalVectors as $k => $v) {
            $finalVectors[$k] = $this->switchBytes($v);
        }
        $result = "";
        foreach ($finalVectors as $k => $v) {
            $v = dechex($v);
            $v = str_pad($v, 8, "0", STR_PAD_LEFT);
            $result .= $v;
        }
        return $result;
    }
    private function makeBytesFromInt(int $int) {
        $n = $int;
        $result = [];
        while (($div = floor($n / 256)) > 0) {
            $result[] = $div;
            $n = $n % 256;
        }
        $result[] = $n;
        return $result;
    }
    private function makeWords($block) {
        $result = [];
        $iniIndex = 0;
        $blockLength = count($block);
        while ($iniIndex < $blockLength) {
            $endIndex = $iniIndex + 4 - 1;
            $word = $this->makeSingleWord($block, $iniIndex, $endIndex);
            $result[] = $word;
            $iniIndex = $endIndex + 1;
        }
        return $result;
    }
    private function makeSingleWord($block, $start, $end) {
        $result = 0;
        $p = $end - $start;
        for ($i = $end; $i >= $start; $i--, $p--) {
            $result += $block[$i] * 256**$p;
        }
        return $result;
    }
    private function round1(array $words, array $vectors, array $k, array $s) {
        [$A, $B, $C, $D] = array_values($vectors);
        for ($i = 0; $i < 16; $i++) {
            $j = $i+1;
            $mi = $words[$i];
            $kj = $k["K$j"];
            $sj = $s["S$j"];
            $functionReturnValue = $this->F($B, $C, $D);
            [$A, $B, $C, $D] = $this->operation($mi, $A, $B, $C, $D, $kj, $sj, $functionReturnValue, $i);
        }
        return ["A"=>$A, "B"=>$B, "C"=>$C, "D"=>$D];
    }
    
    private function round2(array $words, array $vectors, array $k, array $s) {
        // M1, M6, M11, M0, M5, M10, M15, M4, M9, M14, M3, M8, M13, M2, M7, M12
        $order = [1, 6, 11, 0, 5, 10, 15, 4, 9, 14, 3, 8, 13, 2, 7, 12];
        $message = [];
        foreach ($order as $i) {
            $message[] = $words[$i];
        }
    
        [$A, $B, $C, $D] = array_values($vectors);
        for ($i = 16; $i < 32; $i++) {
            $j = $i+1;
            $mi = $message[$i-16];
            $kj = $k["K$j"];
            $sj = $s["S$j"];
            $functionReturnValue = $this->G($B, $C, $D);
            [$A, $B, $C, $D] = $this->operation($mi, $A, $B, $C, $D, $kj, $sj, $functionReturnValue);
        }
        return ["A"=>$A, "B"=>$B, "C"=>$C, "D"=>$D];
    }
    
    private function round3(array $words, array $vectors, array $k, array $s) {
        // M5, M8, M11, M14, M1, M4, M7, M10, M13, M0, M3, M6, M9, M12, M15, M2
        $order = [5, 8, 11, 14, 1, 4, 7, 10, 13, 0, 3, 6, 9, 12, 15, 2];
        $message = [];
        foreach ($order as $i) {
            $message[] = $words[$i];
        }
    
        [$A, $B, $C, $D] = array_values($vectors);
        for ($i = 32; $i < 48; $i++) {
            $j = $i+1;
            $mi = $message[$i-32];
            $kj = $k["K$j"];
            $sj = $s["S$j"];
            $functionReturnValue = $this->H($B, $C, $D);
            [$A, $B, $C, $D] = $this->operation($mi, $A, $B, $C, $D, $kj, $sj, $functionReturnValue);
        }
        return ["A"=>$A, "B"=>$B, "C"=>$C, "D"=>$D];
    }
    
    private function round4(array $words, array $vectors, array $k, array $s) {
        // M0, M7, M14, M5, M12, M3, M10, M1, M8, M15, M6, M13, M4, M11, M2, M9
        $order = [0, 7, 14, 5, 12, 3, 10, 1, 8, 15, 6, 13, 4, 11, 2, 9];
        $message = [];
        foreach ($order as $i) {
            $message[] = $words[$i];
        }
    
        [$A, $B, $C, $D] = array_values($vectors);
        for ($i = 48; $i < 64; $i++) {
            $j = $i+1;
            $mi = $message[$i-48];
            $kj = $k["K$j"];
            $sj = $s["S$j"];
            $functionReturnValue = $this->I($B, $C, $D);
            [$A, $B, $C, $D] = $this->operation($mi, $A, $B, $C, $D, $kj, $sj, $functionReturnValue);
        }
        return ["A"=>$A, "B"=>$B, "C"=>$C, "D"=>$D];
    }
    private function operation($mi, $A, $B, $C, $D, $kj, $sj, $functionReturnValue, $i=null) {
        $Z = hexdec("100000000");
        /// F + Mi + Kj + A <<S + B
        $v = $this->modularAddition($functionReturnValue, $mi, $Z);
        $v = $this->modularAddition($v, $kj, $Z);
        $v = $this->modularAddition($v, $A, $Z);
        $v = $this->shift($v, $sj);
        $v = $this->modularAddition($v, $B, $Z);
        return [$D, $v, $B, $C];
    }
    private function makeKArray(): array {
        return [
            "K1" => 0xd76aa478,
            "K2" => 0xe8c7b756,
            "K3" => 0x242070db,
            "K4" => 0xc1bdceee,
            "K5" => 0xf57c0faf,
            "K6" => 0x4787c62a,
            "K7" => 0xa8304613,
            "K8" => 0xfd469501,
            "K9" => 0x698098d8,
            "K10" => 0x8b44f7af,
            "K11" => 0xffff5bb1,
            "K12" => 0x895cd7be,
            "K13" => 0x6b901122,
            "K14" => 0xfd987193,
            "K15" => 0xa679438e,
            "K16" => 0x49b40821,
            "K17" => 0xf61e2562,
            "K18" => 0xc040b340,
            "K19" => 0x265e5a51,
            "K20" => 0xe9b6c7aa,
            "K21" => 0xd62f105d,
            "K22" => 0x02441453,
            "K23" => 0xd8a1e681,
            "K24" => 0xe7d3fbc8,
            "K25" => 0x21e1cde6,
            "K26" => 0xc33707d6,
            "K27" => 0xf4d50d87,
            "K28" => 0x455a14ed,
            "K29" => 0xa9e3e905,
            "K30" => 0xfcefa3f8,
            "K31" => 0x676f02d9,
            "K32" => 0x8d2a4c8a,
            "K33" => 0xfffa3942,
            "K34" => 0x8771f681,
            "K35" => 0x6d9d6122,
            "K36" => 0xfde5380c,
            "K37" => 0xa4beea44,
            "K38" => 0x4bdecfa9,
            "K39" => 0xf6bb4b60,
            "K40" => 0xbebfbc70,
            "K41" => 0x289b7ec6,
            "K42" => 0xeaa127fa,
            "K43" => 0xd4ef3085,
            "K44" => 0x04881d05,
            "K45" => 0xd9d4d039,
            "K46" => 0xe6db99e5,
            "K47" => 0x1fa27cf8,
            "K48" => 0xc4ac5665,
            "K49" => 0xf4292244,
            "K50" => 0x432aff97,
            "K51" => 0xab9423a7,
            "K52" => 0xfc93a039,
            "K53" => 0x655b59c3,
            "K54" => 0x8f0ccc92,
            "K55" => 0xffeff47d,
            "K56" => 0x85845dd1,
            "K57" => 0x6fa87e4f,
            "K58" => 0xfe2ce6e0,
            "K59" => 0xa3014314,
            "K60" => 0x4e0811a1,
            "K61" => 0xf7537e82,
            "K62" => 0xbd3af235,
            "K63" => 0x2ad7d2bb,
            "K64" => 0xeb86d391,
        ];
    }
    private function makeSArray(): array {
        $s = [];
        $s[ "S1"] = $s[ "S5"] = $s[ "S9"] = $s["S13"] = 7;
        $s[ "S2"] = $s[ "S6"] = $s["S10"] = $s["S14"] = 12;
        $s[ "S3"] = $s[ "S7"] = $s["S11"] = $s["S15"] = 17;
        $s[ "S4"] = $s[ "S8"] = $s["S12"] = $s["S16"] = 22;
    
        $s["S17"] = $s["S21"] = $s["S25"] = $s["S29"] = 5;
        $s["S18"] = $s["S22"] = $s["S26"] = $s["S30"] = 9;
        $s["S19"] = $s["S23"] = $s["S27"] = $s["S31"] = 14;
        $s["S20"] = $s["S24"] = $s["S28"] = $s["S32"] = 20;
    
        $s["S33"] = $s["S37"] = $s["S41"] = $s["S45"] = 4;
        $s["S34"] = $s["S38"] = $s["S42"] = $s["S46"] = 11;
        $s["S35"] = $s["S39"] = $s["S43"] = $s["S47"] = 16;
        $s["S36"] = $s["S40"] = $s["S44"] = $s["S48"] = 23;
    
        $s["S49"] = $s["S53"] = $s["S57"] = $s["S61"] = 6;
        $s["S50"] = $s["S54"] = $s["S58"] = $s["S62"] = 10;
        $s["S51"] = $s["S55"] = $s["S59"] = $s["S63"] = 15;
        $s["S52"] = $s["S56"] = $s["S60"] = $s["S64"] = 21;
        return $s;
    }
    private function F($B, $C, $D) {
        // F(B, C, D) = (B∧C)∨(¬B∧D)
        return ($B & $C) | ((~$B & 0xFFFFFFFF) & $D);
    }
    private function G($B, $C, $D) {
        // G(B, C, D) = (B∧D)∨(C∧¬D)
        return ($B & $D) | ( $C & ( (~$D) & 0xFFFFFFFF ) );
    }
    private function H($B, $C, $D) {
        // H(B, C, D) = B⊕C⊕D
        return $B ^ $C ^ $D;
    }
    private function I($B, $C, $D) {
        // I(B, C, D) = C⊕(B∨¬D)
        return $C ^ ( $B | ( (~$D) & 0xFFFFFFFF ) );
    }
    private function modularAddition($x, $y, $Z) {
        return ($x + $y) % $Z;
    }
    private function shift($input, $spaceCount) {
        $shiftResult = $input << $spaceCount;
        $exceeding = $input >> (32-$spaceCount);
        $shiftResult = $shiftResult & 0xffffffff;
        $shiftResult = $shiftResult + $exceeding;
        return $shiftResult;
    }
    private function switchBytes($bytes) {
        $result = 0;
        $n = $bytes;
        for ($i = 0; $i < 4; $i++) {
            $result = ($result << 8) + ($n & 0xFF);
            $n = $n >> 8;
        }
        return $result;
    }
}