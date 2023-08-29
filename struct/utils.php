<?php

function generateUniqueCode($length = 15) {
    // Pool of characters (lowercase letters and digits)
    $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';

    // Shuffle the characters to randomize them
    $shuffledCharacters = str_shuffle($characters);

    // Take the first $length characters to form the unique code
    $uniqueCode = substr($shuffledCharacters, 0, $length);

    return $uniqueCode;
}