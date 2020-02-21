# Forecast tools

Forecast tools improves the overall experience with Harvest & Harvest Forecast, and adds some features:

 * share publicly a Client's or Project's forecast
 * have Slack notifications for the team's schedule
 * a Slack command for letting team members know the schedule
 * mass-insert entries in Harvest timesheets or Forecast schedules
 * Harvest timesheets and Forecast schedules comparison
 * Invoicing workflow for a stronger validation

## Requirements

A Docker environment is provided and requires you to have these tools available:

 * Docker
 * pipenv

Install and run `pipenv` to install the required tools:

```bash
pipenv install
```

You can configure your current shell to be able to use fabric commands directly
(without having to prefix everything by `pipenv run`)

```bash
pipenv shell
```

## Starting up

### Clone the project

```sh
$ git clone https://github.com/jolicode/forecast-tools.git
```

### Get a Harvest developer key

Create an Harvest application at https://id.getharvest.com/developers
  * Name: choose whatever name you want
  * Redirect URL: use your deployment domain, or `http://127.0.0.1:8000` if testing locally
  * Multi Account: choose "I can work with multiple accounts"
  * Products: choose "I want access to Forecast"

Then, copy `.env` file to `.env.local`, and edit its values accordingly.

### Domain configuration (first time only)

Before running the application for the first time, ensure your domain names
point the IP of your Docker daemon by editing your `/etc/hosts` file.

This IP is probably `127.0.0.1` unless you run Docker in a special VM (docker-machine, dinghy, etc).

Note: The router binds port 80 and 443, that's why it will work with `127.0.0.1`

```sh
$ echo '127.0.0.1 local.forecast.jolicode.com' | sudo tee -a /etc/hosts
```

Using dinghy? Run `dinghy ip` to get the IP of the VM.

### Starting the stack

Launch the stack by running this command:

```sh
$ fab start
```

> Note: the first start of the stack should take a few minutes.

The site is now accessible at the hostnames your have configured over HTTPS
(you may need to accept self-signed SSL certificate).

### Assets watcher

Watch for changes:

```sh
$ fab watch
```

### CS fix

```sh
$ fab cs
```

### Builder

Having some composer, yarn or another modifications to make on the project?
Start the builder which will give you access to a container with all these
tools available:

```sh
$ fab builder
```

Note: You can add as many fabric command as you want. But the command should be
ran by the builder, don't forget to add `@with_builder` annotation to the
function.

### Other tasks

Checkout `fab -l` to have the list of available fabric tasks.

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

Go ahead with the [deploy explanations](./deploy/README.md).
