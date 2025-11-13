# HidroganikMQTT

## Store MQTT telemetry into MySQL

This project now uses Firebase only for authentication. To persist telemetry from devices, run a small MQTT → HTTP ingester that posts data into MySQL through a secured API endpoint.

### 1) Create database table

Run the migration to create `telemetry_logs`:

```
php spark migrate
```

Ensure your database credentials are set in `.env` or in `app/Config/Database.php`.

### 2) Configure API token

Add an ingest token in your `.env` (server side):

```
INGEST_TOKEN=change-me
```

The API endpoint is:

- POST `/api/telemetry/ingest`
- Header: `X-INGEST-TOKEN: <INGEST_TOKEN>`
- JSON body example:

```json
{
  "kebun": "kebun-a",
  "ph": 6.5,
  "tds": 850,
  "suhu": 25.2,
  "cal_ph_asam": 4.01,
  "cal_ph_netral": 6.86,
  "cal_tds_k": 0.5,
  "timestamp": 1730325600000
}
```

### 3) Run MQTT ingester

There is a simple Node script in `mqtt-bridge/ingest.js`.

Requirements: Node.js >= 16.

Install dependencies and run:

```
cd mqtt-bridge
npm init -y
npm i mqtt axios
setx INGEST_URL "http://localhost/hidroganik/api/telemetry/ingest"
setx INGEST_TOKEN "change-me"
setx MQTT_URL "wss://broker.hivemq.com:8884/mqtt"
setx MQTT_TOPIC "hidroganik/+/telemetry"
node ingest.js
```

Adjust the variables as needed. The ingester subscribes to telemetry topic(s), parses JSON payload, and posts them to the API which stores rows into `telemetry_logs`.

### 4) Using the data

Create a controller/view to query `telemetry_logs` via `App\Models\TelemetryModel` for reports, CSV export, or dashboards.

Example fetch (PHP):

```php
$model = new \App\Models\TelemetryModel();
$rows = $model->where('kebun', 'kebun-a')
							->orderBy('timestamp_ms', 'DESC')
							->limit(100)
							->find();
```

Notes:

- The website front-end reads/writes no Firebase DB anymore.
- Only login uses Firebase Auth on the server.

# CodeIgniter 4 Application Starter

## What is CodeIgniter?

CodeIgniter is a PHP full-stack web framework that is light, fast, flexible and secure.
More information can be found at the [official site](https://codeigniter.com).

This repository holds a composer-installable app starter.
It has been built from the
[development repository](https://github.com/codeigniter4/CodeIgniter4).

More information about the plans for version 4 can be found in [CodeIgniter 4](https://forum.codeigniter.com/forumdisplay.php?fid=28) on the forums.

You can read the [user guide](https://codeigniter.com/user_guide/)
corresponding to the latest version of the framework.

## Installation & updates

`composer create-project codeigniter4/appstarter` then `composer update` whenever
there is a new release of the framework.

When updating, check the release notes to see if there are any changes you might need to apply
to your `app` folder. The affected files can be copied or merged from
`vendor/codeigniter4/framework/app`.

## Setup

Copy `env` to `.env` and tailor for your app, specifically the baseURL
and any database settings.

## Important Change with index.php

`index.php` is no longer in the root of the project! It has been moved inside the _public_ folder,
for better security and separation of components.

This means that you should configure your web server to "point" to your project's _public_ folder, and
not to the project root. A better practice would be to configure a virtual host to point there. A poor practice would be to point your web server to the project root and expect to enter _public/..._, as the rest of your logic and the
framework are exposed.

**Please** read the user guide for a better explanation of how CI4 works!

## Repository Management

We use GitHub issues, in our main repository, to track **BUGS** and to track approved **DEVELOPMENT** work packages.
We use our [forum](http://forum.codeigniter.com) to provide SUPPORT and to discuss
FEATURE REQUESTS.

This repository is a "distribution" one, built by our release preparation script.
Problems with it can be raised on our forum, or as issues in the main repository.

## Server Requirements

PHP version 8.1 or higher is required, with the following extensions installed:

- [intl](http://php.net/manual/en/intl.requirements.php)
- [mbstring](http://php.net/manual/en/mbstring.installation.php)

> [!WARNING]
>
> - The end of life date for PHP 7.4 was November 28, 2022.
> - The end of life date for PHP 8.0 was November 26, 2023.
> - If you are still using PHP 7.4 or 8.0, you should upgrade immediately.
> - The end of life date for PHP 8.1 will be December 31, 2025.

Additionally, make sure that the following extensions are enabled in your PHP:

- json (enabled by default - don't turn it off)
- [mysqlnd](http://php.net/manual/en/mysqlnd.install.php) if you plan to use MySQL
- [libcurl](http://php.net/manual/en/curl.requirements.php) if you plan to use the HTTP\CURLRequest library
