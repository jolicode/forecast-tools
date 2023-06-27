# Forecast tools

![Tests badge](https://github.com/jolicode/forecast-tools/actions/workflows/tests.yml/badge.svg)

Forecast tools improves the overall experience with Harvest & Harvest Forecast, and adds some features:

 * share publicly a Client's or Project's forecast
 * Slack notifications for the team's schedule
 * per-channel Slack notifications for pinging the right people for the standup meeting
 * a Slack command for letting team members know the schedule
 * mass-insert entries in Harvest timesheets or Forecast schedules
 * Harvest timesheets and Forecast schedules comparison
 * Invoicing workflow for a stronger validation

## Requirements

A Docker environment is provided and requires you to have these tools available:

 * Docker
 * 🐘 PHP
 * 🦫 [Castor](https://github.com/jolicode/castor#installation)

### Castor

Once Castor is installed, in order to improve your usage of Castor scripts, you
can install the console autocompletion script.

If you are using bash:

```bash
castor completion | sudo tee /etc/bash_completion.d/castor
```

If you are using something else, please refer to your shell documentation. You
may need to use `castor completion > /to/somewhere`.

Castor supports completion for `bash`, `zsh` & `fish` shells.

## Starting up

### Clone the project

```sh
$ git clone https://github.com/jolicode/forecast-tools.git
```

### Get a Harvest developer key and a Slack application

Create an Harvest application at https://id.getharvest.com/developers
  * Name: choose whatever name you want
  * Redirect URL: use your deployment domain, or `https://local.forecast.jolicode.com` if testing locally
  * Multi Account: choose "I can work with multiple accounts"
  * Products: choose "I want access to Forecast and Harvest"

Create a Slack application for your Slack workspace at https://api.slack.com/apps
  * configure two slash commands:
    * `/forecast` - In "Request URL", write https://[YOUR DOMAIN]/slack/command
    * `/standup-reminder` - In "Request URL", write https://[YOUR DOMAIN]/slack/command
  * under "Interactive components", in "Request URL", write https://[YOUR DOMAIN]/slack/interactive-endpoint
  * under "OAuth & permissions"
    * add https://local.forecast.jolicode.com/ as a "Redirect URL" (or your own deployment domain)
    * choose the following scopes: `channels:read`, `chat:write`, `chat:write.customize`, `chat:write.public`, `commands`, `users:read`, `users:read.email`

Then, copy `.env` file to `.env.local`, and edit its values accordingly.

### Domain configuration (first time only)

Before running the application for the first time, ensure your domain names
point the IP of your Docker daemon by editing your `/etc/hosts` file.

This IP is probably `127.0.0.1` unless you run Docker in a special VM (docker-machine, dinghy, etc).

Note: The router binds port 80 and 443, that's why it will work with `127.0.0.1`

```sh
$ echo '127.0.0.1 local.forecast.jolicode.com encore.local.forecast.jolicode.com' | sudo tee -a /etc/hosts
```

Using dinghy? Run `dinghy ip` to get the IP of the VM.

### Starting the stack

Launch the stack by running this command:

```sh
$ castor start
```

> Note: the first start of the stack should take a few minutes.

The site is now accessible at the hostnames your have configured over HTTPS
(you may need to accept self-signed SSL certificate if you do not have mkcert
installed on your computer - see below).

### SSL certificates

This stack does not embed self-signed SSL certificates. Instead, they will be
generated the first time you start the infrastructure (`castor start`) or if you
run `castor infra:generate-certificates`. So *HTTPS will work out of the box*.

If you have `mkcert` installed on your computer, it will be used to generate
locally trusted certificates. See [`mkcert` documentation](https://github.com/FiloSottile/mkcert#installation)
to understand how to install it. Do not forget to install CA root from mkcert
by running `mkcert -install`.

If you don't have `mkcert`, then self-signed certificates will instead be
generated with openssl. You can configure [infrastructure/docker/services/router/openssl.cnf](infrastructure/docker/services/router/openssl.cnf)
to tweak certificates.

You can run `castor infra:generate-certificates --force` to recreate new certificates
if some were already generated. Remember to restart the infrastructure to make
use of the new certificates with `castor up` or `castor start`.

### Builder

Having some composer, yarn or other modifications to make on the project?
Start the builder which will give you access to a container with all these
tools available:

```bash
castor builder
```

### Other tasks

Type `castor` in the shell to have the list of available tasks.

#### Assets watcher

Watch for changes:

```sh
$ castor app:watch
```

#### CS fix

```sh
$ castor qa:cs
```

#### Quality tools

```bash
$ castor qa:phpstan
$ castor qa:rector
```

## Commands and cron jobs

### Commands

The project provides 4 Symfony commands:

 * `forecast:alert-send` has to be executed every minute. It sends Slack Forecast schedule reminders, according to their configuration in the organization settings ;
 * `forecast:standup-meeting-reminder-send` has to be executed every 15 minutes. It sends Slack standup meeting reminders to Slack channels ;
 * `forecast:timesheet-reminder` has to be executed once a day. It sends Slack private message reminders to users who have not filled their Harvest timesheets for the previous month ;
 * `forecast:refresh-tokens` has to be executed once a day. It refreshes Harvest API tokens once per week, to that alerts can be sent even if a user did not connect since a long time.

### Cron jobs setup

In order to send Slack alerts, add a crontab directive to run every minute the `./bin/console forecast:alert-send` task, every 15 minutes the `./bin/console forecast:standup-meeting-reminder-send` task, and every day at 10am the `./bin/console forecast:timesheet-reminder` task eg.

```
* * * * * /path/to/install/bin/console forecast:alert-send
*/15 * * * * /path/to/install/bin/console forecast:standup-meeting-reminder-send
0 10 * * * /path/to/install/bin/console forecast:timesheet-reminder
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
