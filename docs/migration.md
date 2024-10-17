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

## Check migration status

```bash
lando drush migrate:status
```

```
 ------------------- ----------------------------------- -------- ------- ---------- ------------- ---------------
  Group               Migration ID                        Status   Total   Imported   Unprocessed   Last Imported
 ------------------- ----------------------------------- -------- ------- ---------- ------------- ---------------
  Default (default)   content_news_media_image            Idle     100     0          100
  Default (default)   content_photos_media_image          Idle     239     0          239
  Default (default)   content_videos_media_remote_video   Idle     594     0          594
  Default (default)   content_news_body_to_paragraph      Idle     100     0          100
  Default (default)   content_videos                      Idle     594     0          594
  Default (default)   pictures_media_image                Idle     3953    0          3953
  Default (default)   content_news                        Idle     100     0          100
  Default (default)   pictures_paragraphs_item            Idle     3953    0          3953
  Default (default)   content_photos                      Idle     239     0          239
 ------------------- ----------------------------------- -------- ------- ---------- ------------- ---------------
```

## Migrate "News" content

```bash
$ lando drush migrate-import content_news_media_image
# [notice] Processed 100 items (100 created, 0 updated, 0 failed, 0 ignored) - done with 'content_news_media_image'

$ lando drush migrate-import content_news_body_to_paragraph
# [notice] Processed 100 items (100 created, 0 updated, 0 failed, 0 ignored) - done with 'content_news_body_to_paragraph'

$ lando drush migrate-import content_news
# [notice] Processed 100 items (100 created, 0 updated, 0 failed, 0 ignored) - done with 'content_news'

$ lando drush cr
```
