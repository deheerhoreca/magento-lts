#!/bin/bash

#mkdir -p ../skin/frontend/rwd/external/ga
#wget https://www.google-analytics.com/analytics.js -O ../skin/frontend/rwd/external/ga/analytics.js

mkdir -p ../skin/frontend/rwd/external/opensans
wget https://gist.github.com/stefanmaric/a5043c0998d9fc35483d/raw/55afaea418aaee1d074b4a427496573c5e8e5200/open-sans.css -O ../skin/frontend/rwd/external/opensans/open-sans.css

mkdir -p ../skin/frontend/rwd/external/clickcease/monitor
wget https://www.clickcease.com/monitor/stat.js -O ../skin/frontend/rwd/external/clickcease/monitor/stat.js

mkdir -p ../skin/frontend/rwd/external/hotjar
wget https://static.hotjar.com/c/hotjar-943471.js?sv=6 -O ../skin/frontend/rwd/external/hotjar/hotjar-943471.js

mkdir -p ../skin/frontend/rwd/external/ga
wget https://www.google-analytics.com/analytics.js -O ../skin/frontend/rwd/external/ga/analytics.js

mkdir -p ../skin/frontend/rwd/external/zopim
wget https://v2.zopim.com/?5GFYzGgeADrvMUAoHWiZPPglkc93U381  -O ../skin/frontend/rwd/external/zopim/widget.js

mkdir -p ../skin/frontend/rwd/external/elastic_apm
# needs a touch as well because the file timestamp would be in the 1980s otherwise
wget https://unpkg.com/@elastic/apm-rum/dist/bundles/elastic-apm-rum.umd.min.js  -O ../skin/frontend/rwd/external/elastic_apm/elastic-apm-rum.umd.min.js && touch ../skin/frontend/rwd/external/elastic_apm/elastic-apm-rum.umd.min.js

mkdir -p ../skin/frontend/rwd/external/fontawesome
# corehacked:
#wget https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css -O ../skin/frontend/rwd/external/fontawesome/font-awesome-4.7.0.min.css

mkdir -p ../skin/frontend/rwd/external/sooqr
wget https://static.sooqr.com/custom/115684/1/combined.css  -O ../skin/frontend/rwd/external/sooqr/combined.css
wget https://static.sooqr.com/sooqr.js  -O ../skin/frontend/rwd/external/sooqr/sooqr.js
