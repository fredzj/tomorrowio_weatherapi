# tomorrowio_weatherapi

This project allows you to download and view weather forecast data from tomorrow.io.

## Prerequisites
- A web server (e.g., Apache, Nginx)
- PHP installed on the server
- MySQL or MariaDB database

## 1. Save Database Credentials
Create a file `/config/db.ini` and enter your database credentials in the following format:
```ini
hostname=your_hostname
databasename=your_databasename
username=your_username
password=your_password
```

### 2. Create Database Tables
Import all SQL files from the database directory into your database:
```sh
mysql -u your_username -p your_databasename < /path/to/database/file.sql
```
## 3. Transfer Files
Transfer all files to your server.  

## 4. Import Weather Forecast Data
Schedule `importWeatherForecast.php` in order to import Weather Forecast data from tomorrow.io and save it into your database. You can use a cron job for this:
```sh
# Example cron job to run the script daily at midnight
0 0 * * * /usr/bin/php /path/to/importWeatherForecast.php
```

## 5. View Weather Forecast Dashboard
Open `tomorrowio.php` in your browser.
