# MD5 password cracker using rainbow table and word list

## Summary

This project is a solution for one of John Crickett's Coding Challenges. To read the full challenge description, go to https://codingchallenges.fyi/challenges/challenge-password-cracker.

## Running this project locally

In this project, all challenges' steps are written in different PHP scripts. They are located in `src/steps`. Here's how to run each one:

### Step 1

In this step, we are asked to write a MD5 hasher. To execute the script, run:

```
php step1.php
```

### Step 2

This script uses brute force to crack a hash provided by the user:

```
php step2.php b04f36eaa39aadf30603a29cba1aaff1
```

Output:

```
result: !$$!
```

### Step 3

The script uses two different strategies to crack a password.

Using a word list:

```
php step3.php word-list 826bbc5d0522f5f20a1da4b60fa8c871 ../../data/sample-word-list.txt
```

Output:

```
Strategy: word-list
Hash: 826bbc5d0522f5f20a1da4b60fa8c871
Result: ghi
```

Using brute force (instead of a fixed length, it uses a maximum length):

```
php step3.php brute-force 826bbc5d0522f5f20a1da4b60fa8c871
```

Output:

```
Strategy: brute-force
Hash: 826bbc5d0522f5f20a1da4b60fa8c871
Result: ghi
```


### Step 4

The script builds a MySQL rainbow table and stores a dictionary with many words and their respective hashes. It uses a word list or generates all permutations with a maximum length.

Using word list:

```
php step4.php word-list ../../data/sample-word-list.txt
```

Generating permutations:

```
php step4.php all-permutations
```


### Step 5

The script uses the database provided on step 4 to crack a password entered by the user.

```
php step5.php 0c30c3d1d797cdb7243fdc1215c93023
```

Output:

```
Result: -fireman08-
```
