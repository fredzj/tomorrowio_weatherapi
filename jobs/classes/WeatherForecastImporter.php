<?php
/**
 * Class WeatherForecastImporter
 *
 * This class is responsible for importing weather forecast data from the Tomorrow.io API into the database.
 * It handles various types of data, such as weather timelines and location-specific forecasts.
 * The class ensures that the data is up-to-date and accurately reflects the current weather conditions.
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
class WeatherForecastImporter {
    private $db;
	private $dbConfigPath;
    private $config;
    private $inputUrl;
	private $log;
    private $outputColumns = ['iso3166_1_alpha_2_code', 'iso3166_2_subdivision_code', 'timelines'];
	private $outputDataLines = 0;
    private $outputValues;
    private $timeStart;

    public function __construct($dbConfigPath, $inputUrl) {
		$this->dbConfigPath = $dbConfigPath;
		$this->inputUrl = $inputUrl;
		$this->log = new Log();
		$this->registerExitHandler();
		$this->connectDatabase();
		$this->importConfig();
	}

    /**
     * Register the exit handler.
     *
     * @return void
     */
    private function registerExitHandler(): void {
        $this->timeStart = microtime(true);
        register_shutdown_function([new ExitHandler($this->timeStart), 'handleExit']);
    }

	/**
	 * Connects to the database using the configuration file.
	 *
	 * This method reads the database configuration from the specified INI file,
	 * parses the configuration, and establishes a connection to the database.
	 * If the configuration file cannot be parsed, an exception is thrown.
	 *
	 * @throws Exception If the configuration file cannot be parsed.
	 * @return void
	 */
	private function connectDatabase() {
		if (($dbConfig = parse_ini_file($this->dbConfigPath, FALSE, INI_SCANNER_TYPED)) === FALSE) {
			throw new Exception("Parsing file " . $this->dbConfigPath	. " FAILED");
		}
		$this->db = new Database($dbConfig);
	}

    /**
     * Retrieves the Tomorrow.io configuration from the database.
     *
     * This method fetches the configuration from the 'config' table where the name is 'tomorrow.io',
     * decodes the JSON configuration, and stores it in the $config property.
     *
     * @return void
     */
    private function importConfig(): void {
        $configuration = $this->getConfig();
    
        if (empty($configuration)) {
            $this->log->error('Error: Configuration for Tomorrow.io not found.');
            return;
        }

        $this->config = json_decode($configuration, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log->error('Error: Failed to decode JSON configuration - ' . json_last_error_msg());
            $this->config = [];
        } else {
			$this->inputUrl .= $this->config['apikey'];
		}
    }

    /**
     * Import and process data from Tomorrow.io.
     *
     * @return void
     */
    public function import(): void {
		$apiCallCount = $this->getApiCallCountToday();
		$this->log->info("Today's number of API calls: " . $apiCallCount);
		if ($apiCallCount < MAX_CALLS_PER_DAY) {

			$coordinates = $this->getNextCoordinates();
			foreach ($coordinates as $coordinate) {

				$this->log->info('Downloading weather for ' . $coordinate['iso3166_2_subdivision_name']);
				$nextUrl = str_replace('<<LATLNG>>', $coordinate['latlng'], $this->inputUrl);
				if (($contents = @file_get_contents($nextUrl)) !== false) {
					
					$this->outputValues = [
						$coordinate['iso3166_1_alpha_2_code'],
						$coordinate['iso3166_2_subdivision_code'],
						$contents
					];
					if (empty($coordinate['timestamp'])) {
						$this->db->insert('vendor_tomorrow_io_weather', $this->outputColumns, $this->outputValues);
					} else {
						$this->db->update('vendor_tomorrow_io_weather', $coordinate['id'], "timelines='$contents'");
					}
					
					$this->outputDataLines++;

				} else {
					$this->log->error('Error: Failed to download weather data for ' . $coordinate['iso3166_2_subdivision_name']);
				}
				sleep(1);
			}

		}
        $this->log->info('- ' . $this->outputDataLines . ' rows processed');
    }

	/**
	* Get the count of API calls made today.
	*
	* @return int The count of API calls made today.
	*/
	private function getApiCallCountToday(): int {
		$sql			=	"
		SELECT		COUNT(*)						AS	count
		FROM		vendor_tomorrow_io_weather
		WHERE		DATE(timestamp)					=	CURDATE()";
		$fetchedRows = $this->db->select($sql);
		return $fetchedRows[0]['count'];
	}

	/**
	* Get the configuration for Tomorrow.io.
	*
	* @return string The configuration settings for Tomorrow.io.
	*/
	private function getConfig(): string {
		$sql			=	"
		SELECT		configuration 
		FROM		config 
		WHERE		name							=	'tomorrow.io'";
		$fetchedRows = $this->db->select($sql);
		return $fetchedRows[0]['configuration'];
	}

	/**
	* Get the next coordinates for weather data import.
	*
	* @return array The next set of coordinates for weather data import.
	*/
	private function getNextCoordinates(): array {
		$sql		=	"
		SELECT		r.iso3166_1_alpha_2_code,
					r.iso3166_2_subdivision_code,
					r.iso3166_2_subdivision_name,
					r.latlng,
					w.id,
					w.timestamp
		FROM		destination_regions_level2 r
		LEFT JOIN	vendor_tomorrow_io_weather w	ON	w.iso3166_2_subdivision_code	=	r.iso3166_2_subdivision_code
		WHERE	(
					w.timestamp						IS	NULL		OR
					DATE(w.timestamp)				<	CURDATE()
				)
		AND			COALESCE(r.latlng, '')			<>	''
		ORDER BY	w.timestamp, r.iso3166_2_subdivision_code
		LIMIT		" . MAX_CALLS_PER_HOUR;
		return $this->db->select($sql);
	}
}