#!/bin/bash

set -x

cd ~/httpdocs/deheerhoreca-magento/shell

# New style

wget https://cdnjs.cloudflare.com/ajax/libs/glightbox/3.2.0/css/glightbox.min.css -O ../skin/frontend/rwd/dhh/css/ext-glightbox.min.css
# Does not work, manually: https://cookie-script.com/item/edit/137075
# wget https://cookie-script.com/item/edit/137075 -O ../js/ext-cookie-script.js
wget https://cdnjs.cloudflare.com/ajax/libs/vanilla-lazyload/17.8.3/lazyload.min.js -O ../js/ext-lazyload.min.js
wget https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.3/jquery.min.js -O ../js/ext-jquery.min.js
wget https://cdnjs.cloudflare.com/ajax/libs/prototype/1.7.3/prototype.min.js -O ../js/ext-prototype.min.js
wget https://cdnjs.cloudflare.com/ajax/libs/scriptaculous/1.9.0/builder.min.js -O ../js/ext-scriptaculous-builder.min.js
wget https://cdnjs.cloudflare.com/ajax/libs/scriptaculous/1.9.0/effects.min.js -O ../js/ext-scriptaculous-effects.min.js
wget https://cdnjs.cloudflare.com/ajax/libs/glightbox/3.2.0/js/glightbox.min.js -O ../js/ext-glightbox.min.js
wget https://cdnjs.cloudflare.com/ajax/libs/lite-youtube-embed/0.2.0/lite-yt-embed.min.js -O ../js/ext-lite-yt-embed.min.js
wget https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js -O ../js/ext-jquery.ui.touch-punch.min.js
wget https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js -O ../js/ext-jquery-ui.min.js

# These need versioning outside of mod_pagespeed:extend_cache
wget https://cdn.jsdelivr.net/npm/@elastic/apm-rum/dist/bundles/elastic-apm-rum.umd.min.js.map -O ../js/elastic-apm-rum.umd.min.js.map
wget https://cdn.jsdelivr.net/npm/@elastic/apm-rum/dist/bundles/elastic-apm-rum.umd.min.js -O ../js/ext-elastic-apm-rum.umd.min.js
wget https://tr.datatrics.com -O ../js/ext-datatrics-1.min.js

# Old style

mkdir -p ../skin/frontend/rwd/external/opensans
wget https://gist.github.com/stefanmaric/a5043c0998d9fc35483d/raw/55afaea418aaee1d074b4a427496573c5e8e5200/open-sans.css -O ../skin/frontend/rwd/external/opensans/open-sans.css

mkdir -p ../skin/frontend/rwd/external/clickcease/monitor
wget https://www.clickcease.com/monitor/stat.js -O ../skin/frontend/rwd/external/clickcease/monitor/stat.js

mkdir -p ../skin/frontend/rwd/external/ga
wget https://www.google-analytics.com/analytics.js -O ../skin/frontend/rwd/external/ga/analytics.js

mkdir -p ../skin/frontend/rwd/external/zopim
wget https://v2.zopim.com/?5GFYzGgeADrvMUAoHWiZPPglkc93U381  -O ../skin/frontend/rwd/external/zopim/widget.js

mkdir -p ../skin/frontend/rwd/external/elastic_apm
# needs a touch as well because the file timestamp would be in the 1980s otherwise
wget https://unpkg.com/@elastic/apm-rum/dist/bundles/elastic-apm-rum.umd.min.js  -O ../skin/frontend/rwd/external/elastic_apm/elastic-apm-rum.umd.min.js && touch ../skin/frontend/rwd/external/elastic_apm/elastic-apm-rum.umd.min.js

mkdir -p ../skin/frontend/rwd/external/fontawesome
wget https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css -O ../skin/frontend/rwd/external/fontawesome/font-awesome-4.7.0.min.css
wget https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/fonts/fontawesome-webfont.woff2?v=4.7.0 -O ../skin/frontend/rwd/external/fontawesome/fontawesome-webfont.woff2
wget https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/fonts/fontawesome-webfont.ttf -O ../skin/frontend/rwd/external/fontawesome/fontawesome-webfont.ttf

mkdir -p ../skin/frontend/rwd/external/sooqr
wget https://static.sooqr.com/custom/115684/1/combined.css  -O ../skin/frontend/rwd/external/sooqr/combined.css
wget https://static.sooqr.com/sooqr.js  -O ../skin/frontend/rwd/external/sooqr/sooqr.js
