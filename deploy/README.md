# Deployment

This is the deployment tool for the production infrastructure of myproject
website.

All the tasks below should be ran in the current directory.

## Ansible dependencies

```sh
# install external ansible playbooks (none required at the time of writing)
$ ansible-galaxy install -r requirements.yml
```

## Secrets

Before running any command, you will need to grab secrets used in the
application. Remember that `vars/secrets.yml` is not versioned in git.

### Someone already worked on the project

Just retrieve their version of their `vars/secrets.yml` and put it in your `vars/`.

### You start the project from scratch

In this case, duplicate [vars/secrets.yml.dist](vars/secrets.yml.dist) into `vars/secrets.yml`:

```sh
$ cp vars/secrets.yml.dist vars/secrets.yml
```

## Configuration

See the [hosts dir](hosts/) for configuration specific to a domain.

## Deployment launch

Deploy preproduction :

```sh
$ ansible-playbook -i hosts/preprod deploy.yml
```

Deploy production :

```sh
$ ansible-playbook -i hosts/prod deploy.yml
```
