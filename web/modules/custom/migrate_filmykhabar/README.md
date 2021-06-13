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
