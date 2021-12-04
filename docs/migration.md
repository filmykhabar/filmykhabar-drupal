# Migrating data from old database to new Drupal database.

Here is the list of core, contrib and custom modules used for migration:

- migrate (core)
- [migrate_plus](https://www.drupal.org/project/migrate_plus) (contrib)
- [migrate_tools](https://www.drupal.org/project/migrate_tools) (contrib)
- [migrate_file](https://www.drupal.org/project/migrate_file) (contrib)
- filmykhabar_migration (custom)

<br />

# Import old database

In local development environment, create a new service `database_old` for importing the old database dump

.lando.yml

```yml
services:
  database_old:
    type: mysql:5.7
```

and, rebuild lando:

```bash
lando rebuild -y
```

Get the database details by running `lando info` in command line.

```json
{
  "service": "database_old",
  "urls": [],
  "type": "mysql",
  "healthy": true,
  "internal_connection": { "host": "database_old", "port": "3306" },
  "external_connection": { "host": "127.0.0.1", "port": "not forwarded" },
  "healthcheck": "bash -c \"[ -f /bitnami/mysql/.mysql_initialized ]\"",
  "creds": { "database": "database", "password": "mysql", "user": "mysql" },
  "config": {},
  "version": "5.7",
  "meUser": "www-data",
  "hasCerts": false,
  "hostnames": ["database_old.filmykhabar.internal"]
}
```

Now, import the old database dump:

```bash
# Import database.
lando db-import old-database-dump.sql.gz --host database_old

# SSH to mysql instance.
lando mysql --host database_old

# Verify.
mysql> show databases;
mysql> use database;
mysql> show tables;
```

<br />

## Drupal configuration

Add the old database details in `settings.php` or `settings.local.php`

```php
$databases['migrate']['default'] = array(
    'driver' => 'mysql',
    'database' => 'database',
    'username' => 'mysql',
    'password' => 'mysql',
    'host' => 'database_old',
    'port' => 3306
);
```
