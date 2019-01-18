# Forecast tools

Forecast tools improves the overall experience with Harvest Forecast, and adds some features:

 * send Slack notification with tomorrow's schedule ;
 * share a Client's or Project's forecast with a client.

## Install

 * clone the project

```sh
$ git clone https://github.com/jolicode/forecast-tools.git
```
 * create an Harvest application at https://id.getharvest.com/developers
   * Name: choose whatever name you want
   * Redirect URL: use your deployment domain, or `http://127.0.0.1:8000` if testing locally
   * Multi Account: choose "I can work with multiple accounts"
   * Products: choose "I want access to Forecast"
 * copy `.env` to `.env.local`, edit its values accordingly
 * install dependencies:

```sh
$ composer install
```

## Run

Run the webserver:

```sh
$ ./bin/console server:run
```

In order to send Slack alerts, add a crontab directive to run every minute the `./bin/console forecast:alert-send` task, eg.

```
* * * * * /path/to/install/bin/console forecast:alert-send
```

## Troubleshoot

Got some problems using this library? Need a missing feature?
Do not hesitate to [open an issue](https://github.com/jolicode/forecast-tools/issues)
and share it with us.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE.md)
file for details.
