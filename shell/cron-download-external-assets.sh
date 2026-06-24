#!/usr/bin/env bash

: "───────────────────────────────────────────────────────────────────────────────────────────────────────────────
${HOME}/workspace/openmage/shell/cron-download-external-assets.sh
─────────────────────────────────────────────────────────────────────────────────────────────────────────────────"

export NO_DEV=0

. ${HOME}/.profile || die "Failed to load ~/.profile"
cm || die "Failed to go to the openmage directory"
. ./shell/cron-bootstrap.sh || die "Failed to run ./shell/cron-bootstrap.sh"

wget --no-verbose -q https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.js -O ./js/ext-jquery.js
wget --no-verbose -q https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js -O ./js/ext-jquery.min.js
wget --no-verbose -q https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js -O ./js/ext-jquery.ui.touch-punch.min.js
wget --no-verbose -q https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.js -O ./js/ext-jquery-ui.js
wget --no-verbose -q https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js -O ./js/ext-jquery-ui.min.js
wget --no-verbose -q https://cdnjs.cloudflare.com/ajax/libs/prototype/1.7.3/prototype.js -O ./js/ext-prototype.js
wget --no-verbose -q https://cdnjs.cloudflare.com/ajax/libs/prototype/1.7.3/prototype.min.js -O ./js/ext-prototype.min.js
wget --no-verbose -q https://cdnjs.cloudflare.com/ajax/libs/scriptaculous/1.9.0/builder.js -O ./js/ext-scriptaculous-builder.js
wget --no-verbose -q https://cdnjs.cloudflare.com/ajax/libs/scriptaculous/1.9.0/builder.min.js -O ./js/ext-scriptaculous-builder.min.js
wget --no-verbose -q https://cdnjs.cloudflare.com/ajax/libs/scriptaculous/1.9.0/effects.js -O ./js/ext-scriptaculous-effects.js
wget --no-verbose -q https://cdnjs.cloudflare.com/ajax/libs/scriptaculous/1.9.0/effects.min.js -O ./js/ext-scriptaculous-effects.min.js
wget --no-verbose -q https://cdnjs.cloudflare.com/ajax/libs/lite-youtube-embed/0.3.2/lite-yt-embed.js -O ./js/ext-lite-yt-embed.js
wget --no-verbose -q https://cdnjs.cloudflare.com/ajax/libs/lite-youtube-embed/0.3.2/lite-yt-embed.min.js -O ./js/ext-lite-yt-embed.min.js

wget --no-verbose -q https://cdn.jsdelivr.net/npm/bootstrap/dist/js/bootstrap.min.js -O ./skin/frontend/rwd/external/bootstrap.min.js
wget --no-verbose -q https://cdn.jsdelivr.net/npm/bootstrap/dist/js/bootstrap.js -O ./skin/frontend/rwd/external/bootstrap.js
wget --no-verbose -q https://cdn.jsdelivr.net/npm/bootstrap/dist/css/bootstrap.min.css -O ./skin/frontend/rwd/external/bootstrap.min.css
wget --no-verbose -q https://cdn.jsdelivr.net/npm/bootstrap/dist/css/bootstrap.css -O ./skin/frontend/rwd/external/bootstrap.css

wget --no-verbose -q https://static.zdassets.com/ekr/snippet.js?key=6a6d58da-d9c1-4d6a-98f5-5873329abca0 -O ./js/ext-zendesk-snippet.min.js

. ./shell/cron-wrapup.sh
