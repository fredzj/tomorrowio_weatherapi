<?php
/**
 * SCRIPT: importWeatherForecast.php
 * PURPOSE: This script imports weather forecast data from Tomorrow.io API and stores it in the database.
 *
 * This script sets the default timezone, internal encoding, and locale settings. It then creates an instance 
 * of WeatherForecastImporter and runs the import process to fetch weather forecast data from Tomorrow.io API 
 * and store it in the database. If any exceptions occur during the import, they are caught and logged.
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

require 'classes/Database.php';
require 'classes/ExitHandler.php';
require 'classes/Log.php';
require 'classes/WeatherForecastImporter.php';

// Set defaults
define("MAX_CALLS_PER_DAY",		'500');
define("MAX_CALLS_PER_HOUR",	'25');
define("MAX_CALLS_PER_SECOND",	'3');

date_default_timezone_set('Europe/Amsterdam');
mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'nl_NL.utf8');

$dbConfigPath = substr(__DIR__, 0, mb_strrpos(__DIR__, '/')) . '/config/db.ini';
$inputUrl = 'https://api.tomorrow.io/v4/weather/forecast?location=<<LATLNG>>&apikey=';
$log = new Log();

// Create an instance of the importer and run the import
try {
    $importer = new WeatherForecastImporter($dbConfigPath, $inputUrl);
    $importer->import();
} catch (PDOException $e) {
    $log->error('Caught PDOException: ' . $e->getMessage());
} catch (Exception $e) {
    $log->error('Caught Exception: ' . $e->getMessage());
} finally {
	// The exit handler will be called automatically at the end of the script
}