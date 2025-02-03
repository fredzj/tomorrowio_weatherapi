<?php
/**
 * Class Log
 * 
 * This class provides methods to log messages at different levels (e.g., error, info, debug) and store them in a log file.
 * It includes methods for logging error, info, and debug messages, and a private method to handle the actual logging process.
 * 
 * @package tomorrowio_weatherforecast
 * @version 1.0.0
 * @since 2024
 * @license MIT
 * 
 * COPYRIGHT: 2024 Fred Onis - All rights reserved.
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * 
 * @author Fred Onis
 */
class Log {
    private $logFile;

    public function __construct($logFile = 'app.log') {
        $this->logFile = $logFile;
    }

    /**
     * Log an error message.
     *
     * @param string $message The error message to log.
     * @return void
     */
    public function error(string $message): void {
        $this->log('ERROR', $message);
    }

    /**
     * Log an info message.
     *
     * @param string $message The info message to log.
     * @return void
     */
    public function info(string $message): void {
        $this->log('INFO', $message);
    }

    /**
     * Log a debug message.
     *
     * @param string $message The debug message to log.
     * @return void
     */
    public function debug(string $message): void {
        $this->log('DEBUG', $message);
    }

    /**
     * Log a message with a specified level.
     *
     * @param string $level The log level (e.g., 'ERROR', 'INFO', 'DEBUG').
     * @param string $message The message to log.
     * @return void
     */
    private function log(string $level, string $message): void {
        $timestamp = date("[Y-m-d G:i:s]");
        $logMessage = "$timestamp [$level] $message" . PHP_EOL;
        //file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        echo $logMessage;
    }
}