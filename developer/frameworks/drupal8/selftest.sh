#/bin/bash
#
# Self-tests.
#
set -e

./exec.sh drupal8 'drush eval "realistic_dummy_content_api_selftest()"'

./exec.sh drupal8 'drush generate-realistic'
# Disabling comment to see if we can still use our module.
# It is a bit of a convoluted process...
./exec.sh drupal8 'drush eval "\Drupal::entityManager()->getStorage('field_config')->load('node.article.comment')->delete();"'
./exec.sh drupal8 'drush cron'
./exec.sh drupal8 'drush -y pm-uninstall comment && drush generate-realistic'

./exec.sh drupal8 'drush -y pm-uninstall realistic_dummy_content'
./exec.sh drupal8 'drush -y pm-uninstall realistic_dummy_content_api'
