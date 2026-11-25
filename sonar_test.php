<?php

function sonarTest(int $amount): string
{
    // Issue: unused local variable.
    $unusedMessage = "This variable is never used";

    $curl = curl_init("https://example.com");

    // Vulnerability: server certificate verification is disabled.
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    // Issue: comparing a variable with itself is always true here.
    if ($amount === $amount) {
        return (string) curl_exec($curl);
    }

    return "Unreachable result";
}

// No function call is needed. SonarQube analyses the source code.