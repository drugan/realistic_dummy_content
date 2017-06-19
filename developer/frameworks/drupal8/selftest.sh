#/bin/bash
#
# Self-tests.
#
set -e

./exec.sh drupal8 'drush eval "realistic_dummy_content_api_selftest()"'

./exec.sh drupal8 'drush generate-realistic'
./exec.sh drupal8 '/resources/uninstall-comment-module.sh'
# Make sure we can run generate-realistic even if the
# comment module is disabled.
./exec.sh drupal8 'drush generate-realistic'

./exec.sh drupal8 'drush -y pm-uninstall realistic_dummy_content'
./exec.sh drupal8 'drush -y pm-uninstall realistic_dummy_content_api'
