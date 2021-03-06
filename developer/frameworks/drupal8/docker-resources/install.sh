#!/bin/bash
#
# Install Drupal 8
#

set -e

cp -r /sites-default/* sites/default

drush si \
  -y \
  --db-url=mysql://root:@database/drupal8 \
  --account-name=admin \
  --account-pass=admin \
  standard \
  install_configure_form.enable_update_status_module=NULL \
  install_configure_form.enable_update_status_emails=NULL

drush en realistic_dummy_content devel_generate devel -y
