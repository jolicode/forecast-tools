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
$ yarn install
```

 * build assets:

```sh
$ yarn run build
```

## Run

Run the webserver:

```sh
$ ./bin/console server:run
```

## Cron jobs

In order to send Slack alerts, add a crontab directive to run every minute the `./bin/console forecast:alert-send` task, eg.

```
* * * * * /path/to/install/bin/console forecast:alert-send
```

Harvest tokens need to be refreshed once in a while. They have a two-weeks duration, hence we try to refresh them when they will expire in less than 7 days. In order to force tokens to be refreshed once a day, please add this cron job:

```
0 5 * * * /path/to/install/bin/console forecast:refresh-tokens
```

This will try to update tokens close to expiration.

## Troubleshoot

Got some problems using this library? Need a missing feature?
Do not hesitate to [open an issue](https://github.com/jolicode/forecast-tools/issues)
and share it with us.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE.md)
file for details.

## Deploy

Install the Ansible deps:

```sh
$ pipenv install
```

Then for running the Ansible command, you can:
- either enter into a pipenv shell with `pipenv shell`;
- or prepend every Ansible command by `pipenv run`.

Go ahead with the [deploy explanations](./deploy/README.md).
