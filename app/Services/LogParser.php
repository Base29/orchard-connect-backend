<?php

namespace App\Services;

class LogParser
{
    /**
     * Parses the laravel.log file into structured log entries.
     * Reads line-by-line to stay memory-efficient.
     */
    public static function parseLogsLazy(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [];
        }

        $entries = [];
        $currentEntry = null;
        $index = 0;

        while (($line = fgets($handle)) !== false) {
            // Check if line starts with timestamp: [YYYY-MM-DD HH:MM:SS]
            if (preg_match('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $line)) {
                if ($currentEntry) {
                    $entries[] = $currentEntry;
                    $index++;
                }

                // Parse header
                $timestamp = '';
                $env = '';
                $level = '';
                $message = trim($line);

                if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+([a-zA-Z0-9_-]+)\.([a-zA-Z]+):\s+(.*)$/', trim($line), $matches)) {
                    $timestamp = $matches[1];
                    $env = $matches[2];
                    $level = strtoupper($matches[3]);
                    $message = $matches[4];
                } elseif (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+([a-zA-Z0-9_-]+)\.([a-zA-Z]+):/', trim($line), $matches)) {
                    $timestamp = $matches[1];
                    $env = $matches[2];
                    $level = strtoupper($matches[3]);
                    $message = trim(substr(trim($line), strlen($matches[0])));
                }

                $currentEntry = [
                    'index' => $index,
                    'timestamp' => $timestamp,
                    'env' => $env,
                    'level' => $level,
                    'message' => $message,
                    'stack' => '',
                    'raw' => $line,
                ];
            } else {
                if ($currentEntry) {
                    $currentEntry['stack'] .= $line;
                    $currentEntry['raw'] .= $line;
                }
            }
        }

        if ($currentEntry) {
            $entries[] = $currentEntry;
        }

        fclose($handle);
        return $entries;
    }

    /**
     * Deletes a single log entry by its index.
     * Uses stream processing to remain highly memory efficient.
     */
    public static function deleteEntry(string $filePath, int $targetIndex): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $tempPath = $filePath . '.tmp';
        $readHandle = fopen($filePath, 'r');
        $writeHandle = fopen($tempPath, 'w');

        if (!$readHandle || !$writeHandle) {
            if ($readHandle) fclose($readHandle);
            if ($writeHandle) fclose($writeHandle);
            return false;
        }

        $currentIndex = -1;
        $skipCurrent = false;

        while (($line = fgets($readHandle)) !== false) {
            if (preg_match('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $line)) {
                $currentIndex++;
                if ($currentIndex === $targetIndex) {
                    $skipCurrent = true;
                    continue;
                } else {
                    $skipCurrent = false;
                }
            }

            if ($skipCurrent) {
                continue;
            }

            fwrite($writeHandle, $line);
        }

        fclose($readHandle);
        fclose($writeHandle);

        return rename($tempPath, $filePath);
    }
}
