<?php
/**
 * Class ExitHandler
 * 
 * This class provides a standard exit routine for scripts. It calculates and displays the total execution time
 * and memory usage when the script terminates.
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
class ExitHandler {
    private $log;
    private $timeStart;

     /**
     * ExitHandler constructor.
     * 
     * @param float $timeStart The start time of the script execution.
     */
    public function __construct($timeStart) {
        $this->log = new Log();
        $this->timeStart = $timeStart;
    }

    /**
     * Handles the exit routine.
     * 
     * This method calculates the total execution time and memory usage, and displays them.
     * 
     * @return void
     */
    public function handleExit(): void {
        $executionTime = microtime(true) - $this->timeStart;
        $executionText = ($executionTime > 120) 
            ? round($executionTime / 60, 2) . " minutes" 
            : round($executionTime, 2) . " seconds";

            $currentUsage = round(memory_get_usage() / (1024 * 1024), 2) . ' MB';
            $peakUsage = round(memory_get_peak_usage() / (1024 * 1024), 2) . ' MB';
    
            $this->log->info('Memory usage:');
            $this->log->info('- current usage: ' . $currentUsage);
            $this->log->info('- peak usage: ' . $peakUsage);
            $this->log->info('Total Execution Time: ' . $executionText);
    }
}