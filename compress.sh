#!/bin/bash
sass src/golf-score.scss static/golf-score.css
VERSION=$(git tag -l)
echo "${VERSION}"
sed -i -E "s/^ \* Version\: .*$/ * Version: ${VERSION}/g" wp-golf-score.php
(cd ../ && zip -r wp-golf-score/${VERSION}.zip \
  wp-golf-score/index.html \
  wp-golf-score/LICENSE \
  wp-golf-score/proxy.php \
  wp-golf-score/README.md \
  wp-golf-score/wp-golf-score.php \
  wp-golf-score/dist \
  wp-golf-score/static)