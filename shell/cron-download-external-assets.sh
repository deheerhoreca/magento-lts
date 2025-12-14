#!/bin/bash

: '
~/workspace/openmage/shell/cron-download-external-assets.sh
'

export NO_DEV=0

. ${HOME}/.bash_profile
cm
. ./shell/cron-bootstrap.sh

# @todo Replace by npm-asset/bower-asset:
wget --no-verbose -q https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js -O ./js/ext-jquery.min.js
wget --no-verbose -q https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js -O ./js/ext-jquery.ui.touch-punch.min.js
wget --no-verbose -q https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js -O ./js/ext-jquery-ui.min.js
wget --no-verbose -q https://cdnjs.cloudflare.com/ajax/libs/prototype/1.7.3/prototype.min.js -O ./js/ext-prototype.min.js
wget --no-verbose -q https://cdnjs.cloudflare.com/ajax/libs/scriptaculous/1.9.0/builder.min.js -O ./js/ext-scriptaculous-builder.min.js
wget --no-verbose -q https://cdnjs.cloudflare.com/ajax/libs/scriptaculous/1.9.0/effects.min.js -O ./js/ext-scriptaculous-effects.min.js
wget --no-verbose -q https://cdnjs.cloudflare.com/ajax/libs/lite-youtube-embed/0.3.2/lite-yt-embed.min.js -O ./js/ext-lite-yt-embed.min.js

wget --no-verbose -q https://cdn.jsdelivr.net/npm/bootstrap/dist/js/bootstrap.min.js -O ./skin/frontend/rwd/external/bootstrap.min.js
wget --no-verbose -q https://cdn.jsdelivr.net/npm/bootstrap/dist/js/bootstrap.js -O ./skin/frontend/rwd/external/bootstrap.js
wget --no-verbose -q https://cdn.jsdelivr.net/npm/bootstrap/dist/css/bootstrap.min.css -O ./skin/frontend/rwd/external/bootstrap.min.css
wget --no-verbose -q https://cdn.jsdelivr.net/npm/bootstrap/dist/css/bootstrap.css -O ./skin/frontend/rwd/external/bootstrap.css

wget --no-verbose -q https://static.zdassets.com/ekr/snippet.js?key=6a6d58da-d9c1-4d6a-98f5-5873329abca0 -O ./js/ext-zendesk-snippet.min.js

# These need versioning outside of mod_pagespeed:extend_cache
wget --no-verbose -q https://cdn.jsdelivr.net/npm/@elastic/apm-rum/dist/bundles/elastic-apm-rum.umd.min.js.map -O ./js/elastic-apm-rum.umd.min.js.map
wget --no-verbose -q https://cdn.jsdelivr.net/npm/@elastic/apm-rum/dist/bundles/elastic-apm-rum.umd.js -O ./js/ext-elastic-apm-rum.umd.js
wget --no-verbose -q https://cdn.jsdelivr.net/npm/@elastic/apm-rum/dist/bundles/elastic-apm-rum.umd.min.js -O ./js/ext-elastic-apm-rum.umd.min.js

# Old style:
# mkdir -p ./skin/frontend/rwd/external/sooqr
# wget --no-verbose -q https://static.sooqr.com/custom/115684/1/combined.css -O ./skin/frontend/rwd/external/sooqr/combined.css
# wget --no-verbose -q https://static.spotlersearch.com/sooqr.js -O ./skin/frontend/rwd/external/sooqr/sooqr.min.js
# wget --no-verbose -q https://spotlersearchanalytics.com/insights.js -O ./skin/frontend/rwd/external/sooqr/insights.min.js

. ./shell/cron-wrapup.sh
