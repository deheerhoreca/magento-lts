#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/cron-download-external-assets.sh

# Set User Environment
. ${HOME}/.bash_profile

cm

# Replace by npm-asset/bower-asset:
# wget --no-verbose https://cdnjs.cloudflare.com/ajax/libs/glightbox/3.3.0/css/glightbox.min.css -O ./skin/frontend/rwd/dhh/css/ext-glightbox.min.css
# wget --no-verbose https://cdnjs.cloudflare.com/ajax/libs/glightbox/3.3.0/js/glightbox.min.js -O ./js/ext-glightbox.min.js
wget --no-verbose https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js -O ./js/ext-jquery.min.js
wget --no-verbose https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js -O ./js/ext-jquery.ui.touch-punch.min.js
wget --no-verbose https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js -O ./js/ext-jquery-ui.min.js
wget --no-verbose https://cdnjs.cloudflare.com/ajax/libs/prototype/1.7.3/prototype.min.js -O ./js/ext-prototype.min.js
wget --no-verbose https://cdnjs.cloudflare.com/ajax/libs/scriptaculous/1.9.0/builder.min.js -O ./js/ext-scriptaculous-builder.min.js
wget --no-verbose https://cdnjs.cloudflare.com/ajax/libs/scriptaculous/1.9.0/effects.min.js -O ./js/ext-scriptaculous-effects.min.js
wget --no-verbose https://cdnjs.cloudflare.com/ajax/libs/lite-youtube-embed/0.3.2/lite-yt-embed.min.js -O ./js/ext-lite-yt-embed.min.js

wget --no-verbose https://cdn.jsdelivr.net/npm/bootstrap/dist/js/bootstrap.min.js -O ./skin/frontend/rwd/external/bootstrap.min.js
wget --no-verbose https://cdn.jsdelivr.net/npm/bootstrap/dist/js/bootstrap.js -O ./skin/frontend/rwd/external/bootstrap.js
wget --no-verbose https://cdn.jsdelivr.net/npm/bootstrap/dist/css/bootstrap.min.css -O ./skin/frontend/rwd/external/bootstrap.min.css
wget --no-verbose https://cdn.jsdelivr.net/npm/bootstrap/dist/css/bootstrap.css -O ./skin/frontend/rwd/external/bootstrap.css

wget --no-verbose https://cdn.cookie-script.com/s/9e97d160d4e7a60d64717d815a816dd9.js -O ./js/ext-cookie-script.min.js
wget --no-verbose https://static.zdassets.com/ekr/snippet.js?key=6a6d58da-d9c1-4d6a-98f5-5873329abca0 -O ./js/ext-zendesk-snippet.min.js

# These need versioning outside of mod_pagespeed:extend_cache
wget --no-verbose https://cdn.jsdelivr.net/npm/@elastic/apm-rum/dist/bundles/elastic-apm-rum.umd.min.js.map -O ./js/elastic-apm-rum.umd.min.js.map
wget --no-verbose https://cdn.jsdelivr.net/npm/@elastic/apm-rum/dist/bundles/elastic-apm-rum.umd.js -O ./js/ext-elastic-apm-rum.umd.js
wget --no-verbose https://cdn.jsdelivr.net/npm/@elastic/apm-rum/dist/bundles/elastic-apm-rum.umd.min.js -O ./js/ext-elastic-apm-rum.umd.min.js

# Old style:
mkdir -p ./skin/frontend/rwd/external/opensans
wget --no-verbose https://gist.github.com/stefanmaric/a5043c0998d9fc35483d/raw/55afaea418aaee1d074b4a427496573c5e8e5200/open-sans.css -O ./skin/frontend/rwd/external/opensans/open-sans.css
mkdir -p ./skin/frontend/rwd/external/clickcease/monitor
wget --no-verbose https://www.clickcease.com/monitor/stat.js -O ./skin/frontend/rwd/external/clickcease/monitor/stat.js

# font-awesome.min.css was patched to have "font-display:swap;" do not overwrite
# mkdir -p ./skin/frontend/rwd/external/fontawesome
# wget --no-verbose https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css -O ./skin/frontend/rwd/external/fontawesome/font-awesome-4.7.0.min.css
# wget --no-verbose https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/fonts/fontawesome-webfont.woff2?v=4.7.0 -O ./skin/frontend/rwd/external/fontawesome/fontawesome-webfont.woff2
# wget --no-verbose https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/fonts/fontawesome-webfont.ttf -O ./skin/frontend/rwd/external/fontawesome/fontawesome-webfont.ttf

mkdir -p ./skin/frontend/rwd/external/sooqr
wget --no-verbose https://static.sooqr.com/custom/115684/1/combined.css -O ./skin/frontend/rwd/external/sooqr/combined.css
wget --no-verbose https://static.spotlersearch.com/sooqr.js -O ./skin/frontend/rwd/external/sooqr/sooqr.min.js
wget --no-verbose https://spotlersearchanalytics.com/insights.js -O ./skin/frontend/rwd/external/sooqr/insights.min.js
