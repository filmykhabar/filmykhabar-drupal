### Check migration status

```bash
drush migrate-status
```

### Migrate News content

```bash
$ drush migrate-import content_news_media_image
# [notice] Processed 100 items (100 created, 0 updated, 0 failed, 0 ignored) - done with 'content_news_media_image'

$ drush migrate-import content_news_body_to_paragraph
# [notice] Processed 100 items (100 created, 0 updated, 0 failed, 0 ignored) - done with 'content_news_body_to_paragraph'

$ drush migrate-import content_news
# [notice] Processed 100 items (100 created, 0 updated, 0 failed, 0 ignored) - done with 'content_news'

$ drush cr
```

docker exec -it filmykhabar_database_1 bash

mysql -u root -p drupal9
password: <empty>

mysql> GRANT ALL PRIVILEGES ON filmykhabar_old.\* TO 'drupal9'@'%';
mysql> exit;

mysql -u drupal9 -p drupal9
mysql> show databases
mysql> exit

cd /app
gunzip < filmykhabar_db.local.20210920-1.sql.gz | mysql -u drupal9 -p filmykhabar_old
