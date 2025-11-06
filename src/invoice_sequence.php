<?php

/**
 * Generates a new sequential invoice number.
 * Pattern: HUNTAPP-STRIPE-YYYY-N
 * 
 * Thread-safe using file locks (flock).
 */
function getNextInvoiceNumber(): string {
    $year = date('Y');
    $file = __DIR__ . '/../logs/invoice_sequence.json';

    // Ensure logs directory exists
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    // Open file for read/write (create if missing)
    $fp = fopen($file, 'c+');
    if (!$fp) {
        throw new \RuntimeException("Cannot open sequence file: $file");
    }

    // Acquire exclusive lock to prevent race conditions
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        throw new \RuntimeException("Cannot acquire lock on $file");
    }

    // Read current content
    $contents = stream_get_contents($fp);
    $data = json_decode($contents, true);
    if (!is_array($data)) {
        $data = [];
    }

    // Increment or reset per year
    if (!isset($data[$year])) {
        $data[$year] = 0;
    }
    $data[$year]++;

    $sequence = $data[$year];

    // Write back safely
    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
    fflush($fp);

    // Release lock and close
    flock($fp, LOCK_UN);
    fclose($fp);

    // Compose final invoice number
    $invoiceNumber = sprintf('HUNTAPP-STRIPE-%s-%d', $year, $sequence);

    // Optional audit log
    $logFile = __DIR__ . '/../logs/invoice_sequence.log';
    file_put_contents(
        $logFile,
        sprintf("%s - Generated invoice: %s\n", date('c'), $invoiceNumber),
        FILE_APPEND
    );

    return $invoiceNumber;
}