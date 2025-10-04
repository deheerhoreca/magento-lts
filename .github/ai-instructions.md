========================
CODE SNIPPETS
========================
TITLE: OpenMage Installation Script
DESCRIPTION: The `install.sh` script is provided to assist with creating a fresh installation of OpenMage. It handles initial setup steps, potentially including database configuration and file copying.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/tools/oneline.md#_snippet_8

LANGUAGE: bash
CODE:
```
#!/bin/bash

# This is a placeholder for the actual install.sh script content.
# The script would typically handle:
# - Database creation and user setup
# - Copying local.xml to app/etc/
# - Setting file permissions
# - Running Magento setup commands

echo "Running OpenMage installation script..."

# Example: Create a dummy local.xml
# mkdir -p pub/app/etc/
# cp dev/openmage/local.xml.sample pub/app/etc/local.xml

echo "Installation script finished."
```

----------------------------------------

TITLE: Example: Retrieving Products via Magento REST API
DESCRIPTION: Demonstrates how to make a GET request to the Magento REST API to retrieve a list of products. Includes URL examples with and without a limit parameter.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/api/rest/testing_rest_resources.md#_snippet_4

LANGUAGE: HTTP
CODE:
```
GET https://om.ddev.site/api/rest/products

// To limit the results to 4 products:
GET https://om.ddev.site/api/rest/products?limit=4
```

----------------------------------------

TITLE: Install MkDocs and Plugins
DESCRIPTION: Installs MkDocs and essential plugins (mkdocs-material, mkdocs-minify-plugin, mkdocs-redirects) using pip3. This command assumes Python 3 and pip3 are already installed.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/mkdocs.md#_snippet_0

LANGUAGE: bash
CODE:
```
pip3 install mkdocs mkdocs-material mkdocs-minify-plugin mkdocs-redirects
```

----------------------------------------

TITLE: Install Magento Sample Data
DESCRIPTION: Installs Magento Sample Data using the `ddev openmage-install` command. Supports various flags for default values, sample data installation, keeping archives, and quiet mode.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/tools/ddev.md#_snippet_13

LANGUAGE: bash
CODE:
```
ddev openmage-install -d -s -k -q
```

----------------------------------------

TITLE: DDEV: Updated Install Script
DESCRIPTION: Provides an updated install script for the DDEV environment. This ensures that new DDEV setups are configured correctly and efficiently.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/blog/posts/releases/2024-12-20-release-20-11-0.md#_snippet_56

LANGUAGE: bash
CODE:
```
# DDEV: updated install script [#4407]
```

----------------------------------------

TITLE: Installing mkcert for Secured Connections
DESCRIPTION: Instructions for installing the mkcert tool on Windows to create self-signed TLS certificates, including running the installer and confirming the certificate installation.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/blog/posts/guides/2024-02-17-ddev-windows-10.md#_snippet_3

LANGUAGE: Shell
CODE:
```
mkcert-vX.X.X-windows-amd64.exe --install
```

----------------------------------------

TITLE: Install Browsersync Add-on
DESCRIPTION: Installs the DDEV Browsersync add-on, which provides features like live reloads and click mirroring. Requires a DDEV restart after installation.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/tools/ddev.md#_snippet_10

LANGUAGE: bash
CODE:
```
ddev get ddev/ddev-browsersync
ddev restart
ddev browsersync
```

----------------------------------------

TITLE: Installing OpenMage Dependencies with DDEV
DESCRIPTION: Commands to configure, install dependencies, and install sample data for OpenMage using DDEV.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/blog/posts/guides/2024-02-17-ddev-windows-10.md#_snippet_4

LANGUAGE: bash
CODE:
```
ddev config
ddev composer install
ddev openmage-install -s -k
```

----------------------------------------

TITLE: Install phpMyAdmin Add-on
DESCRIPTION: Installs the phpMyAdmin add-on for DDEV to manage databases through a web interface. Requires restarting DDEV after installation.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/tools/ddev.md#_snippet_0

LANGUAGE: bash
CODE:
```
ddev get ddev/ddev-phpmyadmin
```

----------------------------------------

TITLE: PhpStorm Metadata Factory Helper Setup
DESCRIPTION: This snippet shows how to generate PhpStorm meta files for Magento extensions using N98-magerun. It requires N98-magerun to be installed and provides the command to run.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/tools/phpstorm.md#_snippet_0

LANGUAGE: bash
CODE:
```
n98-magerun.phar dev:ide:phpstorm:meta
```

----------------------------------------

TITLE: Install Compass with DDEV
DESCRIPTION: Installs Compass, a CSS preprocessor framework, into the DDEV web container. This involves creating a custom Dockerfile to install Ruby and Compass.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/tools/ddev.md#_snippet_11

LANGUAGE: dockerfile
CODE:
```
ARG BASE_IMAGE
FROM $BASE_IMAGE
RUN apt-get update
RUN DEBIAN_FRONTEND=noninteractive apt-get install -y -o Dpkg::Options::="--force-confold" --no-install-recommends --no-install-suggests build-essential ruby-full rubygems
RUN gem install compass

```

----------------------------------------

TITLE: Starting and Launching OpenMage with DDEV
DESCRIPTION: Commands to start the DDEV environment and launch the OpenMage project in the browser.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/blog/posts/guides/2024-02-17-ddev-windows-10.md#_snippet_6

LANGUAGE: bash
CODE:
```
ddev start
ddev launch
```

----------------------------------------

TITLE: Install Legacy Frontend Themes
DESCRIPTION: Installs legacy frontend themes using Composer. This is a command-line operation.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/changelog/index.md#_snippet_4

LANGUAGE: bash
CODE:
```
composer require openmage/legacy-frontend-themes
```

----------------------------------------

TITLE: Install OpenMage with Docker Compose
DESCRIPTION: Clones the OpenMage repository and installs it using Docker Compose. This command sets up a new Docker Compose product named 'openmage' and runs the installation script.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/tools/oneline.md#_snippet_0

LANGUAGE: bash
CODE:
```
git clone https://github.com/OpenMage/magento-lts.git && cd magento-lts && dev/openmage/install.sh
```

----------------------------------------

TITLE: Initialize New Composer Project
DESCRIPTION: Creates a new project using Composer. This is the first step in setting up a Magento project with Composer.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/install/use-composer.md#_snippet_0

LANGUAGE: bash
CODE:
```
composer init
```

----------------------------------------

TITLE: Install OpenMage Ignition
DESCRIPTION: Installs the empiricompany/openmage_ignition module, which provides spatie/ignition integration for OpenMage.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/log-debug.md#_snippet_0

LANGUAGE: bash
CODE:
```
composer require empiricompany/openmage_ignition
```

----------------------------------------

TITLE: DDEV Project Configuration and Management
DESCRIPTION: Commands for configuring, describing, listing, starting, stopping, and restarting DDEV projects.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/tools/ddev.md#_snippet_15

LANGUAGE: bash
CODE:
```
ddev config
ddev describe
ddev list
ddev start
ddev stop
ddev restart
ddev poweroff
```

----------------------------------------

TITLE: Grid Column Callback Attributes
DESCRIPTION: Demonstrates the use of callback attributes for grid columns when custom classes are not necessary. Includes examples for rendering and filtering.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/blog/posts/guides/2025-03-11-brief-guide-on-grid-column.md#_snippet_2

LANGUAGE: php
CODE:
```
// in addColumn()
    'frame_callback' => [$this, '_decorateUserUpdatedAt'],
    'filter_condition_callback' => [$this, '_findInSet'],
```

----------------------------------------

TITLE: Install OpenMage with Docker Compose and Sample Data
DESCRIPTION: Installs OpenMage using Docker Compose and includes the Magento Sample Data. This is achieved by setting the SAMPLE_DATA environment variable to '1' before running the installation script.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/tools/oneline.md#_snippet_1

LANGUAGE: bash
CODE:
```
git clone https://github.com/OpenMage/magento-lts.git && cd magento-lts && SAMPLE_DATA=1 dev/openmage/install.sh
```

----------------------------------------

TITLE: Installing a Linux Distribution via Command Prompt
DESCRIPTION: Steps to install a Linux distribution (e.g., Ubuntu-20.04) using the Windows Command Prompt with WSL.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/blog/posts/guides/2024-02-17-ddev-windows-10.md#_snippet_2

LANGUAGE: Shell
CODE:
```
wsl --list --online
wsl --install -d Ubuntu-20.04
```

----------------------------------------

TITLE: Install Cron Add-on
DESCRIPTION: Installs the DDEV cron add-on to manage scheduled tasks. After installation, DDEV needs to be restarted.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/tools/ddev.md#_snippet_3

LANGUAGE: bash
CODE:
```
ddev get ddev/ddev-cron
```

----------------------------------------

TITLE: Install Python 3
DESCRIPTION: Installs Python 3.8 and pip3 on Debian-based systems using apt-get. This is a prerequisite for installing MkDocs and its related Python packages.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/mkdocs.md#_snippet_2

LANGUAGE: bash
CODE:
```
sudo apt-get update
sudo apt-get install python3.8 python3-pip
```

----------------------------------------

TITLE: Install Magento Change Attribute Set Module
DESCRIPTION: Installs the flagbit/magento-changeattributeset module, enabling the switching of a product's attribute set in Magento. This command also installs the firegento/logger dependency.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/backend.md#_snippet_1

LANGUAGE: bash
CODE:
```
composer require firegento/logger
```

----------------------------------------

TITLE: OpenMage Installation Database Configuration
DESCRIPTION: Database connection details required for installing OpenMage.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/tools/ddev.md#_snippet_23

LANGUAGE: APIDOC
CODE:
```
OpenMage Installation Database Configuration:
  Host: db
  Database Name: db
  User Name: db
  User Password: db
```

----------------------------------------

TITLE: Composer.json with Patches
DESCRIPTION: Example of how to define patches within the `composer.json` file for a project. This configuration tells Composer which patches to apply to specific packages during installation or updates.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/blog/posts/guides/2023-05-01-customize-your-openmage.md#_snippet_0

LANGUAGE: json
CODE:
```
{
  "require": {
    "vendor/package": "1.0.0"
  },
  "extra": {
    "patches": {
      "vendor/package": {
        "Fix Bug #1234": "patches/fix-bug-1234.patch"
      }
    }
  }
}
```

----------------------------------------

TITLE: Require Magento Core Composer Installer
DESCRIPTION: Installs the `magento-core-composer-installer` package. Different versions are available for PHP 7, PHP 8, and compatibility with both.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/install/use-composer.md#_snippet_3

LANGUAGE: bash
CODE:
```
composer require "aydin-hassan/magento-core-composer-installer":"~2.1.0"
```

LANGUAGE: bash
CODE:
```
composer require "aydin-hassan/magento-core-composer-installer":"^2.0.0"
```

LANGUAGE: bash
CODE:
```
composer require "aydin-hassan/magento-core-composer-installer":"~2.0.0 || ^2.1.0"
```

----------------------------------------

TITLE: Install openmage-turpentine-varnish
DESCRIPTION: Installs the Varnish connector for OpenMage (Turpentine) using Composer.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/cache.md#_snippet_2

LANGUAGE: bash
CODE:
```
composer require luigifab/openmage-turpentine-varnish
```

----------------------------------------

TITLE: WSL2 Installation and Management Commands
DESCRIPTION: Provides essential commands for installing, updating, and managing Windows Subsystem for Linux 2 (WSL2) distributions and versions.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/blog/posts/guides/2024-02-17-ddev-windows-10.md#_snippet_0

LANGUAGE: Shell
CODE:
```
wsl --install
wsl --version
wsl --update
wsl --list --online
wsl --install -d DISTRO-NAME
wsl --set-version <distro name> 2
```

----------------------------------------

TITLE: Robots.txt Directives for Magento LTS
DESCRIPTION: This snippet details the standard `robots.txt` directives for a Magento LTS installation. It includes general directives for all user agents, specific rules for dynamic filters, and examples of how to disallow indexing for media bots.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/samples/robots-txt.md#_snippet_0

LANGUAGE: robots.txt
CODE:
```
# QQOQOQQQQQQQQQQQQQQQQQQQQQQQMQQQQO^^!!^^^^!^!^^^!!^!^^!^^!!^!^^^!^^^^^^^^^.^^^^^^^^^^^^^^^^^^^^!^6OOOOOOQOO6OO6O66OOOO6O6OOOOO6OQOOOO6
# QQQQQQQQQMQQMQMQMQMQMQMQMQMQMQMQMO^^^!!6O666O66OO6I6III||I||!!!!^^^^^!!!^!!^!^^!^!^!^!^^^^!^^^^!!OQOOOOQOOOOOOO66I6I^^  .I6OOQOQOQOQOO
# QQQQQQMQMQMQMQMQMQMQMQMQMQMQMQMQMQ!^!!!I|OO6666666666I66III66I6I6I||O6I666O66OOOOOOQOOOO6I|^!!^^^QQOOQQQOOOOOO6              .OQQQQQQQ
# QMQQMQMQMMQMQQMQMQMQMMQMQMQMQMQMQQ!^!!!|!6I|!|||!||||!|!||!|||I|I^!^!III6I6I666I6II6IIIIIII|^!^!^OQQQQQQQOQO6.           !  .  QQOQQQO
# QMQMQMQMQMMQMQMQMQQMQMQMMQMQMQMQMQ!!!!!!|O6||!!!||!!|!!!!!^!^!!.         ^^^^!^^^^!^^^!!!II!!^!!!QQQQQQQQOO^        ..I!.Q6Q6^. MQQQQO
# QMQMQMQMQMQMQMQMQMQMQMQMQMQMQMQMQM!!!!!!!O6|||!!!!|!!!^!^^^^^^    !^  !I   ^^^.^^^.^^^^^^II!!!!!!QQQQQQQQO     . ^^OQQQMQMQMQMQO.6QQQQ
# QMQMQMMQMQMQMQMQMQMQMQMQMQMQMQMQMQ!|!!!^^6O||!|!|!|!!!!!^!^^^ ^| 6QOOQQQQMQMMQ.^.^..^^^^^|I.^!!^!QQQQQQOQ.   ..!QMMQMQQMQMQMQMQMM^QQQQ
# QMQQMMQQMQMQMQMQMQMQMQMQMQMQMQMQMQ|!!||^!IOI||!|!!|!!!!!^^^!O|   .!.     IMQQMO....^..^.^!I.^!!!!QQQQMQQQ.  !QOQQMQMQMQMMQMQMQMQMQ6MQQ
# QMMQMQMQMQMQMQMQMQMQMQMQQMQMQMQMQM|!|!|^^IO6||||||!!|!!!!!^QMQ          .!QQQMQI ........!|.^!!!|MQQQMQMO  |6OQMQMQMQMQMMQQMQMQMMQQQQQ
# MQMQMMQMQMQMQMQMQMQMQMQMQMQMQMQMQQ|!||!^^|OII||||I|!|!|!!^OMQM          ..MQQMQM.. . ....!|.^!!!|QMQMQQQQ^!OMQMQMQMQ6MQMMQMQMQQMQQQOQQ
# MQMQMQMQMQMQMQMQMQMQMQMQMQMQMQMMQM|||||^^|O6II|||II||!||!.OQQO  ..  |O^!I..MQMMQ.. . ...^!!.^!!!IMQMQMQMQQ^|MQQMQMM^^QQMQMQMQMOQMQOQMQ
# MQMQMQMQMQMQMQMQMQMQMQMQMQMQMQMQMQI|II|.^|QO6III|I|II|||^. OQO.6^.. ...^ ..OQ|QMQ  . ....!! !!!!6MQMQMQMQMQMQQMQ.MQ!....OQQMIOMQMQQMQM
# MQMQMQMQMQMQMQMQMQMQMQMQMQMMQMQMQQI||I|.^|Q66III|||I|!|!^...MO  .  ..     .|MIMQ|. . ....|^.!!!!6QMQMMQMQMQQM..^.6^^...^IOQMQMQMQQMMQM
# MQMQMQMQMQMQMQMQMQMQMQMQMQMQQMQMMQ6|II|^!IQ6I|II|I|I|||!!. ^MQ^.. ..I.  ..^!MQMQ. . .. ..6!^!!!!6MQMQMQMMQMQQ QOM!|Q^.!|Q6MQ6OQMQMQQMQ
# QMQMQMQMQMQMQMQMQMQMQMMQMQMQMQMQQM6III|^!IQOIIII|||||||!!^ .QQ.. . ... ..^^|MQMQ. . . ...6||!!!!OMMQMQMQMQQMQMQMMQ.|I!|IQQMO|6QIMMQMQM
# MQMQMQMQMQMQMQMQMQMQMQMQQMMQMQMQMQ6I6II^!IQ6IIII|||||!|!^. .IMM^^.^...^.^^|QQMQMQ  . . ..6!|!!!!QMMQMQMQMQMQMQMQQQQMO6QMMQQ!!6|I |QMQM
# QQMQMMQQMQMQMMQMQMQMQQMMQQMQMMQQMQ6II6I!^IQ66|I||!^.^.^^..^ ^QMQ|^....^^!MMQMQMQM.  . . .I^!|!!!QQMQMQQMQMQMQMQQMQQQQ6IMOII!!|.  OMOQM
# MQMQMQMQMQMMQMQQMQMMQMQMQMQMQMQMQM6I6I6|!QQQ6OO^   . .  .^...OQ. M^I|^IQMQMMMQQMM.. . . .|^^!!!!QMQMQMQMQMQMQQMQMQQQQMQQI!^.    .OMQO.
# QMMMMQQ6.    ^ MQQMQMQMQMQMQMQMQMQOIII|||QMQMQ  .   ...||6MOIOOQ  ^OQQOQQQ!!QQMMQ^.^^.^^^I^!!!!!QMQMQQO^.....^^.^^|Q ^. O.       MMQMO
#           |!!!   QMQMQMQQMQMQMQMQMO6I6I^I|^.     .  !|QQMQMMQQMQ     .!. ..^ .QMQMO!|I6666|!^!!!MQM|^.....^^!.!I^^I|| .6QI       QMQMM
#   .      .6Q6|!  .MQQMQMMQMQMQMQMQ6||^. ..^   .O!I^^QMQMQMQQMOQMO    IQM. ..|QQMQMQMMQQO^....^^!QMQM.I^Q^IQMMO!....^   QO       OQMQMQ
# |QM^6QQQ^!!Q.MOO. . MMMMQMM6.       . .^^QQMMQ6O|MQMQQMQMQMQMQOQM   .^Q.. .  .MMQMQMQMQMQM6...^^QQI.QMMO6QMQMI|!!|IQ  MQQ       MMQMQM
# MQMQM.6Q6Q^QQMQ||II|   .   .I6QQQI|...! |IMMMQQMQMQMQMQMQMQMQMQMQ^.. O^.   |I.MMQMQMQMQMQMQI...^MQO!|I!QMMQMQMQMQQMQ.|MMQ      6QMQMQQ
# QMQMQQI  |QMQQO^MO|||..| !Q.!.  .QMMQMIQQOQQMMQMQMQMQMQQMQMM|. .   ^OQQQQO!.6IQQMQMQMQMQMQQM... ..^..!MMMQMQMQMQQMMQ QQMQ      MQMQMQM
# MQMQMQMM |6I6IOO6Q||I||IQ..Q!MQMQQQMQMQMQMQMMQMQMQMQMQMQMQMQQIQQQQMQQQMQ6Q||M.  .MQMMMQM . .... ..^MQMQQMQMQMQMQMQMM.MQMO     ^QMQMQQM
# QMQMQMQM  !|I6IO6^II6|!|Q..^MQMQMQMQMQMQMQMQMQMQQMQMQMQMQMQM|OMQM6MMQMQMMM6QMQQMQI . . ........!MQ|QQMQMQMQMQMQMMQQ6OMQMI    .MMQMQMQM
# MQQMQMQM6 .O66I||^6|6IIQM..IQMQMQMQMQMMQQMQMMQQMMQMQMQMQMQMQQMQMQ!!6M.6QOQMQMQMQMM..^|.^^^!..!^!OIQMQMQMQMQMQMQQMQM.MQMQ^     QMQMMQMQ
# MQMQMMQQMQMQMQMQMQMQIQMM6.OQMQMQMQMQQMQQMMO . .. IQMQMQMQMQMQMQQM!!6Q.OMQMQMQMQMQMQM!^M|QQMQMQMQMQMQMQMQMQMQMQMQMMQ MQMQ.   .|QMQMQMQM
# MQQMQMQMQMQMQMQMMQQMQMMQMQMQMQQMQMQI66I.!|! . .  OMMQMQMQMQMQMQMQ!^IM.IQQMQMMQQMQMQMM.QMQMQMQMQMQMQMQMQMQMQMQMQMQMQ QMQM.    MQMQMMQQM
# QMQMQMQMQMQMQMQQMQMQMQMQMQMQMMQMQMQI||I.!!^.   . QMQMQMQMQMQMQMMM!6QQM|QMQMQM|6OQMQMQ^MMQMMQMQMQMQMQMQMQMQMQMQMQMQMOQQMQ.   .MQMQMMQMQ
# QMQMQMQMQMQMQMQMQMQMQMQMQMQMQMQMQMQIII|^!!!   . .MQMQMQMQMQMQMQQI^!IM .6QMMQOIQMQQMQQQMMQMQMQMQQMQMQMQMQMQMQMQMQMM^QMQQM. ..IQMQMQMQMQ
# QMQMQMQMQMQMQMQMQMQMQMQMQMQMQMQMQMQ||I|^^!!    .^MQMQMQMQMQMQMQM^^^^6Q6QM!^^^IMQMQQMQMQMMQMQMQ..QMQMQMQMQMQMQMQMQM QMQMQ ...MMQMQMQMQM
# QMQMQMQMQMMQMQQMQMQMQMQMQMQMQMQMQMQ|I6|^^|!  .  OMQMQMQMQMQMQMQO^^^^!MQMM||!!|QMQMQMQMQMQMQMQQ.^MQMQMQMQMQMQMQMMQQ|MQMQQ...|QMQMQMMQQM
# MQMQMQMMQQMMQMQMQQMQMQMQMQMMQMQMQMQIII|!!I| . . QMQQMQMQMQMQMQM^^^^!IQMQM|!!|QQMQMQMQMMQQMQMQM.^MQMQMQMQMQMQMQMQM!MQMQQM..!MQMQMQMQMQM
# MQMQMQMQMQMQMQMQMQMQMQMQMQMQMQMMQMQII6I!!||!^^^.QMQMQMQMQMQMQMQ^^^^|MQQMQM^^^!QMQMQMQMQMQMQMMQ.^MQMQMQMQMQMQQMQMMQMQMQMQ.!MQQMQMMQMQMQ

User-agent: *

Crawl-delay: 1

Disallow: /index.php
Disallow: /catalog/product_compare/
# Disallow: /catalog/category/view/
# Disallow: /catalog/product/view/
# Disallow: /catalog/product/gallery/
Disallow: /control/
Disallow: /contacts/
Disallow: /customer/
Disallow: /customize/
Disallow: /newsletter/
Disallow: /poll/
Disallow: /review/
Disallow: /sales/
Disallow: /sendfriend/
Disallow: /tag/
Disallow: /wishlist/
Disallow: /cdn-cgi/
Disallow: /media/static/
Disallow: /no-route
Disallow: /*&multipass=true*
Disallow: /*?multipass=true*
Disallow: /*?price=*
Disallow: /*?___from_store=*
Disallow: /*?___store=*
Disallow: /*?order=*
Disallow: /*&order=*
Disallow: /*?dir=*
Disallow: /*&dir=*
Disallow: /*?limit=*
Disallow: /*&limit=*
Disallow: /*?mode=*
Disallow: /*&mode=*
Disallow: /*?sqr=*
Disallow: /*&sqr=*
Disallow: /*.php$
Disallow: /*?SID=
Disallow: /*&SID=
Disallow: /*?refreshfpc
Disallow: /*&refreshfpc
Disallow: /*?nofpc
Disallow: /*&nofpc
Disallow: /*?csredir
Disallow: /*&csredir
Disallow: /*?*&*&*&*

```

----------------------------------------

TITLE: Install Magento Debug Toolbar
DESCRIPTION: Installs the madalinoprea/magneto-debug module, a development debug toolbar specifically for Magento 1.x.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/log-debug.md#_snippet_3

LANGUAGE: bash
CODE:
```
composer require madalinoprea/magneto-debug
```

----------------------------------------

TITLE: Uninstall OpenMage Docker Environment
DESCRIPTION: Stops and removes all services, volumes, and associated files for the OpenMage Docker Compose setup. This command is used to start fresh or completely remove the development environment.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/tools/oneline.md#_snippet_4

LANGUAGE: bash
CODE:
```
$ docker compose down --volumes && rm -f ../../app/etc/local.xml
```

----------------------------------------

TITLE: Install openmage-lesti-fpc
DESCRIPTION: Installs the Simple Magento FPC module for OpenMage using Composer.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/cache.md#_snippet_1

LANGUAGE: bash
CODE:
```
composer require fballiano/openmage-lesti-fpc
```

----------------------------------------

TITLE: Retrieving Customer List via REST API
DESCRIPTION: Demonstrates how to retrieve a list of all customers using a GET request to the Magento REST API. Requires Admin type user privileges.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/api/rest/testing_rest_resources.md#_snippet_6

LANGUAGE: APIDOC
CODE:
```
Method: GET
URL: https://om.ddev.site/api/rest/customers
Headers:
  Accept: application/json or Accept: text/xml
Send Request.
Response Body: Information about all customers.
```

----------------------------------------

TITLE: Advanced REST Client OAuth Authentication Setup
DESCRIPTION: Configures OAuth authentication within the Advanced REST Client for making secure API requests. This involves setting the signature method, consumer key, consumer secret, access token, and access token secret.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/api/rest/testing_rest_resources.md#_snippet_5

LANGUAGE: APIDOC
CODE:
```
Open Advanced REST Client.
In Headers, select the Form tab.
Add Authorization header.
Click Construct link to configure OAuth.
In Authorization window, select OAuth tab.
Select Signed Request option.
Choose signature method (HMAC-SHA1 or PLAINTEXT).
Fill in:
  Consumer key: Magento Admin Panel Key value.
  Consumer secret: Magento Admin Panel Secret value.
  Access Token: oauth_token value from authentication.
  Access Token Secret: oauth_token_secret value from authentication.
Click OK.
Note: Consumer secret and Access Token Secret are not saved and must be re-entered.
```

----------------------------------------

TITLE: DDEV: Command for Local Development
DESCRIPTION: Introduces a specific command for local development within the DDEV setup. This command likely streamlines common development tasks and workflows.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/blog/posts/releases/2024-12-20-release-20-11-0.md#_snippet_54

LANGUAGE: bash
CODE:
```
# DDEV: command for local development [#4133]
```

----------------------------------------

TITLE: Environment Variable Setup for Production
DESCRIPTION: Instructions on setting up the `.env` file for the production Docker environment. This includes specifying the Compose file and defining essential URLs for the application.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/tools/oneline.md#_snippet_6

LANGUAGE: bash
CODE:
```
# Set production compose file as default
COMPOSE_FILE=docker-compose-production.yml

# Define base URL for the frontend
BASE_URL=https://frontend.example.com/

# Define base URL for the admin interface
ADMIN_URL=https://backend.example.com/

# Optional: Define admin host name if not hardcoded in Caddyfile
# ADMIN_HOST_NAME=backend.example.com

# Relative path to the OpenMage root directory
# SRC_DIR=./pub

# Relative path for custom static files
# STATIC_DIR=./static
```

----------------------------------------

TITLE: DDEV Add-on Management and Container Execution
DESCRIPTION: Commands for getting DDEV add-ons and executing commands within service containers.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/tools/ddev.md#_snippet_19

LANGUAGE: bash
CODE:
```
ddev get --list
ddev get drud/ddev/cron
ddev mysql
ddev php
ddev ssh
ddev exec
```

----------------------------------------

TITLE: Install Cm_Diehard
DESCRIPTION: Installs the Cm_Diehard Fast Process Cache (FPC) module for OpenMage using Composer.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/cache.md#_snippet_0

LANGUAGE: bash
CODE:
```
composer require colinmollenhour/cm_diehard
```

----------------------------------------

TITLE: File Copying for Composer Installations
DESCRIPTION: Instructions for users who have installed OpenMage via Composer. This involves copying configuration files from the `dev/openmage` directory to the project root to avoid conflicts during updates.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/tools/oneline.md#_snippet_9

LANGUAGE: bash
CODE:
```
# Copy Docker Compose file
cp dev/openmage/docker-compose-production.yml docker-compose.yml

# Copy Nginx configuration files (if using Nginx instead of Caddy)
# cp dev/openmage/nginx-admin.conf nginx-admin.conf
# cp dev/openmage/nginx-frontend.conf nginx-frontend.conf

# Copy Caddyfile sample and customize
cp dev/openmage/Caddyfile-sample Caddyfile
# Edit Caddyfile to match your domain names and store codes

# Map static asset directories
# cp -r pub/admin/ static/admin/
# cp pub/default/favicon.ico static/default/favicon.ico
# cp pub/default/robots.txt static/default/robots.txt
```

----------------------------------------

TITLE: Install Defer Javascripts Module
DESCRIPTION: Installs the OpenMage Defer Javascripts module, a maintained fork compatible with the latest OpenMage LTS versions. This module aids in the clean integration of Google reCaptcha.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/frontend.md#_snippet_1

LANGUAGE: bash
CODE:
```
composer require fballiano/openmage-defer-javascripts
```

----------------------------------------

TITLE: Install Google reCaptcha
DESCRIPTION: Installs the Google reCaptcha module for OpenMage LTS. Note: Installation is not available via packagist.org; users should add the repository to their composer.json.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/captcha.md#_snippet_2

LANGUAGE: bash
CODE:
```
composer require proxiblue/recaptcha
```

----------------------------------------

TITLE: Install CSS/JS Versioning Module
DESCRIPTION: Installs the CSS/JS versioning module for OpenMage using Composer. This module helps manage versions of CSS and JavaScript files.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/frontend.md#_snippet_0

LANGUAGE: bash
CODE:
```
composer require fballiano/openmage-cssjs-versioning
```

----------------------------------------

TITLE: OpenMage API HTTP Basic Authentication Example
DESCRIPTION: Provides a PHP example of how to use HTTP Basic Authentication to interact with the OpenMage API, bypassing the need for a separate login call by including credentials in the request header.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/api/jsonrpc/index.md#_snippet_1

LANGUAGE: php
CODE:
```
<?php
// PHP example using HTTP Basic Authorization
$jsonRpcUrl = 'https://your-magento-store.com/api/jsonrpc';
$apiUser = 'apiuser';
$apiKey = 'apikey123';

// Request data with null session ID
$requestData = array(
    'jsonrpc' => '2.0',
    'method' => 'call',
    'params' => [
        null, // Null session ID when using HTTP Basic Authorization
        'catalog_product.info',
        'product_sku_123'
    ],
    'id' => 1
);

$ch = curl_init($jsonRpcUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_USERPWD, "$apiUser:$apiKey"); // HTTP Basic Authorization
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
print_r($result['result']);
?>
```

----------------------------------------

TITLE: DDEV Configuration for OpenMage
DESCRIPTION: Example configuration for DDEV to set the PHP version and web server type.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/blog/posts/guides/2024-02-17-ddev-windows-10.md#_snippet_5

LANGUAGE: yaml
CODE:
```
php_version: "8.3"
webserver_type: apache-fpm
```

----------------------------------------

TITLE: Install Magento 1 WebP Support
DESCRIPTION: Adds WebP image format support to Magento 1 pages, improving image loading performance and efficiency.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/images.md#_snippet_2

LANGUAGE: bash
CODE:
```
composer require yireo/magento1-webp
```

----------------------------------------

TITLE: Magento API Filtering Parameters
DESCRIPTION: This section details the various GET parameters used for filtering, sorting, and pagination in the Magento LTS API. It covers common parameters and specific filter conditions with examples.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/api/rest/get_filters.md#_snippet_0

LANGUAGE: APIDOC
CODE:
```
API Filtering Parameters:

page: Specifies the page number for returned items.

order, dir: Specifies the sort order and direction ('asc' or 'dsc').
  Example: ?order=name&dir=dsc

limit: Limits the number of returned items (max 100).
  Example: ?limit=2

filter: Specifies filters for returned data. Can be used with various conditions:
  neq: 'not equal to'. Returns items where the attribute is not equal to the value.
    Example: ?filter[1][attribute]=entity_id&filter[1][neq]=3
  in: 'equals any of'. Returns items equal to the specified value(s).
    Example: ?filter[1][attribute]=entity_id&filter[1][in]=3
  nin: 'not equals any of'. Excludes items with the specified attribute value.
    Example: ?filter[1][attribute]=entity_id&filter[1][nin]=3
  gt: 'greater than'. Returns items where the attribute is greater than the value.
    Example: ?filter[1][attribute]=entity_id&filter[1][gt]=3
  lt: 'less than'. Returns items where the attribute is less than the value.
    Example: ?filter[1][attribute]=entity_id&filter[1][lt]=4
  from, to: Specifies a range for attributes.
    Example: ?filter[1][attribute]=entity_id&filter[1][from]=1&filter[1][to]=3

White-spaces in attribute values should be URL-encoded as '%20'.
  Example: ?filter[1][attribute]=name&filter[1][in]=BlackBerry%208100%20Pearl

Combined Example:
  To filter products with entity_id greater than 3 and name equal to 'Test Product':
  https://om.ddev.site/api/rest/products?filter[0][attribute]=entity_id&filter[0][gt]=3&filter[1][attribute]=name&filter[1][in]=Test%20Product
```

----------------------------------------

TITLE: Install FireGento Admin Monitoring
DESCRIPTION: Installs the firegento/firegento-adminmonitoring module, which logs backend save and delete operations to provide an overview of changes.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/log-debug.md#_snippet_1

LANGUAGE: bash
CODE:
```
composer require firegento/adminmonitoring
```

----------------------------------------

TITLE: Install FireGento Logger
DESCRIPTION: Installs the firegento/logger module, an advanced logger for sending messages and errors to multiple targets.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/log-debug.md#_snippet_2

LANGUAGE: bash
CODE:
```
composer require firegento/logger
```

----------------------------------------

TITLE: Install OpenMage Monaco Editor Module
DESCRIPTION: Installs the empiricompany/openmage-mm_monacoeditor module, which integrates Monaco Editor with Emmet and Tailwind CSS IntelliSense into Magento CMS Static Blocks and Pages.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/backend.md#_snippet_0

LANGUAGE: bash
CODE:
```
composer require empiricompany/openmage-mm_monacoeditor
```

----------------------------------------

TITLE: Reinstall Mage_Backup Module
DESCRIPTION: Installs the Mage_Backup module using Composer. This is a command-line operation.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/changelog/index.md#_snippet_0

LANGUAGE: bash
CODE:
```
composer require openmage/module-mage-backup
```

----------------------------------------

TITLE: Install Perfect Watermarks
DESCRIPTION: Installs the Perfect_Watermarks module, which replaces the default GD2 image adapter with ImageMagick for enhanced image processing capabilities.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/images.md#_snippet_0

LANGUAGE: bash
CODE:
```
composer require colinmollenhour/perfect_watermarks
```

----------------------------------------

TITLE: Start OpenMage Cron Task
DESCRIPTION: Starts the cron task for the OpenMage Docker Compose environment. This command specifically targets the 'cron' service.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/tools/oneline.md#_snippet_3

LANGUAGE: bash
CODE:
```
docker compose up -d cron
```

----------------------------------------

TITLE: Customer API Methods
DESCRIPTION: Provides comprehensive details on the Customer API methods available in OpenMage, including 'customer.list' and 'customer.info'. It outlines parameters, return structures, example requests and responses, and potential errors for each method.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/api/jsonrpc/resources/customer.md#_snippet_0

LANGUAGE: APIDOC
CODE:
```
Customer API:

  customer.list(filters: object|array = null, store: string|int = null)
    Retrieves a list of customers with basic information.
    Parameters:
      filters (object|array, optional): Filters to apply to the list. Can include customer_id, email, firstname, lastname, created_at, updated_at, website_id, group_id, or other attributes.
      store (string|int, optional): Store ID or code to filter by.
    Returns:
      array: An array of customer objects, each containing customer_id, email, firstname, lastname, created_at, updated_at, website_id, and group_id.
    Example Request:
      {
        "jsonrpc": "2.0",
        "method": "call",
        "params": [
          "session_id",
          "customer.list",
          [{"group_id": 1}]
        ],
        "id": 1
      }
    Example Response:
      {
        "jsonrpc": "2.0",
        "result": [
          {
            "customer_id": 1,
            "email": "john.doe@example.com",
            "firstname": "John",
            "lastname": "Doe",
            "created_at": "2023-01-15 14:30:12",
            "updated_at": "2023-01-15 14:30:12",
            "website_id": 1,
            "group_id": 1
          }
        ],
        "id": 1
      }
    Possible Errors:
      - filters_invalid: Invalid filters provided.
      - store_not_exists: Requested store does not exist.

  customer.info(customerId: int, store: string|int = null)
    Retrieves detailed information for a specific customer.
    Parameters:
      customerId (int, required): The ID of the customer to retrieve.
      store (string|int, optional): Store ID or code.
    Returns:
      object: A customer object containing detailed information including customer_id, email, firstname, lastname, middlename, prefix, suffix, created_at, updated_at, website_id, group_id, dob, taxvat, confirmation, created_in, default_billing, default_shipping, and is_active.
    Example Request:
      {
        "jsonrpc": "2.0",
        "method": "call",
        "params": [
          "session_id",
          "customer.info",
          [1, "default"]
        ],
        "id": 1
      }
    Example Response:
      {
        "jsonrpc": "2.0",
        "result": {
          "customer_id": 1,
          "email": "john.doe@example.com",
          "firstname": "John",
          "lastname": "Doe",
          "middlename": "",
          "prefix": "Mr",
          "suffix": "",
          "created_at": "2023-01-15 14:30:12",
          "updated_at": "2023-01-15 14:30:12",
          "website_id": 1,
          "group_id": 1,
          "dob": "1980-01-01",
          "taxvat": "123456789",
          "confirmation": null,
          "created_in": "Default Store View",
          "default_billing": "1",
          "default_shipping": "1",
          "is_active": 1
        },
        "id": 1
      }
    Possible Errors:
      - customer_not_exists: Customer does not exist.
      - store_not_exists: Requested store does not exist.
```

----------------------------------------

TITLE: Install vianetz/matomo-magento1
DESCRIPTION: This snippet shows the Composer command to install the vianetz/matomo-magento1 Magento 1 extension, which integrates e-commerce data with Matomo analytics.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/analytics.md#_snippet_0

LANGUAGE: bash
CODE:
```
composer require vianetz/matomo-magento1
```

----------------------------------------

TITLE: Install Cloudflare Turnstile
DESCRIPTION: Installs the Cloudflare Turnstile CAPTCHA module for OpenMage using Composer.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/captcha.md#_snippet_0

LANGUAGE: bash
CODE:
```
composer require fballiano/openmage-cloudflare-turnstile
```

----------------------------------------

TITLE: DDEV: n98-magerun Support for Test Environment
DESCRIPTION: Adds support for `n98-magerun` within the DDEV test environment. This enables easier command-line management and interaction with Magento instances in the testing setup.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/blog/posts/releases/2024-12-20-release-20-11-0.md#_snippet_52

LANGUAGE: bash
CODE:
```
# [DDEV] Adding n98-magerun support for the test environment [#4107]
```

----------------------------------------

TITLE: Optimize Composer Autoloader
DESCRIPTION: Optimizes Composer's autoloader for faster class lookup. This command is recommended for production deployments.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/install/use-composer.md#_snippet_5

LANGUAGE: bash
CODE:
```
composer dump-autoload --optimize
```

----------------------------------------

TITLE: Install PayPal Pay Later Banner Info
DESCRIPTION: Installs the empiricompany/openmage-paypal-pay-later-banner-info module using Composer. This module renders PayPal Pay Later messages on product and cart pages.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/payment.md#_snippet_0

LANGUAGE: bash
CODE:
```
composer require empiricompany/openmage-paypal-pay-later-banner-info
```

----------------------------------------

TITLE: Windows Installation Fix
DESCRIPTION: Addresses an issue with installing all font variants in parallel on Windows.

SOURCE: https://github.com/openmage/magento-lts/blob/main/lib/LinLibertineFont/ChangeLog.txt#_snippet_42

LANGUAGE: text
CODE:
```
Oh, yes, the problem with in stalling all variants paralelly on windows is fixed
```

----------------------------------------

TITLE: Require OpenMage Magento LTS
DESCRIPTION: Installs the `openmage/magento-lts` package. This snippet shows how to require specific versions or branches, including the latest v20, legacy v19, and development branches.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/install/use-composer.md#_snippet_4

LANGUAGE: bash
CODE:
```
composer require "openmage/magento-lts":"^20.0.0"
```

LANGUAGE: bash
CODE:
```
composer require "openmage/magento-lts":"^19.4.0"
```

LANGUAGE: bash
CODE:
```
composer require "openmage/magento-lts":"dev-main"
```

LANGUAGE: bash
CODE:
```
composer require "openmage/magento-lts":"dev-next"
```

----------------------------------------

TITLE: Install HoneySpam Module
DESCRIPTION: Installs the HoneySpam module for OpenMage, which provides spam protection for various forms using honey pots, via Composer.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/captcha.md#_snippet_1

LANGUAGE: bash
CODE:
```
composer require magento-hackathon/honeyspam
```

----------------------------------------

TITLE: Launch phpMyAdmin
DESCRIPTION: Launches the phpMyAdmin web interface in the browser, allowing direct database management.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/tools/ddev.md#_snippet_1

LANGUAGE: bash
CODE:
```
ddev phpmyadmin
```

----------------------------------------

TITLE: Nginx Configuration for Multistore Setups
DESCRIPTION: Placeholder for Nginx configuration related to multistore setups. The provided text indicates that settings should be added here, but specific directives are not detailed.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/error-pages.md#_snippet_2

LANGUAGE: nginx
CODE:
```
!!! bug ""

    The setting should be added for multistore setups.
```

----------------------------------------

TITLE: 'pub/' Folder Structure (Optional)
DESCRIPTION: An optional 'pub/' folder structure has been introduced to enhance installation security. However, users should be aware of potential compatibility issues with composer-managed projects.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/blog/posts/releases/2023-08-02-release-20-1-0.md#_snippet_5

LANGUAGE: APIDOC
CODE:
```
'pub/' Folder Structure:
  - Optional structure introduced for enhanced security.
  - Known issue: Does not work with composer-managed projects due to composer-magento-plugin limitations with symbolic links in 'pub/' (Issue #1210).
```

----------------------------------------

TITLE: OpenMage Catalog Category API Reference
DESCRIPTION: This section details the methods available in the OpenMage Catalog Category API. It covers setting the current store, retrieving category trees and levels, and fetching category information. Each method includes its parameters, return values, and example requests/responses.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/api/jsonrpc/resources/catalog_category.md#_snippet_0

LANGUAGE: APIDOC
CODE:
```
Catalog Category API

Introduction:
The Catalog Category API allows you to manage product categories in your OpenMage store. You can retrieve category information, create new categories, update existing ones, move categories within the category tree, and manage product assignments to categories.

Available Methods:

currentStore:
  Sets the current store for category operations.
  Method Name: catalog_category.currentStore
  Parameters:
    - store (string|int, required) - Store ID or code
  Return:
    - (int) - Current store ID
  Example Request:
    {
      "jsonrpc": "2.0",
      "method": "call",
      "params": [
        "session_id",
        "catalog_category.currentStore",
        "default"
      ],
      "id": 1
    }
  Example Response:
    {
      "jsonrpc": "2.0",
      "result": 1,
      "id": 1
    }

tree:
  Retrieve category tree.
  Method Name: catalog_category.tree
  Parameters:
    - parentId (int, optional) - Parent category ID (default: 1 - root category)
    - store (string|int, optional) - Store ID or code
  Return:
    - (array) - Category tree with the following structure:
      - category_id (int) - Category ID
      - parent_id (int) - Parent category ID
      - name (string) - Category name
      - is_active (boolean) - Whether the category is active
      - position (int) - Position
      - level (int) - Level in the category tree
      - children (array) - Array of child categories with the same structure
  Example Request:
    {
      "jsonrpc": "2.0",
      "method": "call",
      "params": [
        "session_id",
        "catalog_category.tree",
        [1, "default"]
      ],
      "id": 1
    }
  Example Response:
    {
      "jsonrpc": "2.0",
      "result": {
        "category_id": 1,
        "parent_id": 0,
        "name": "Root",
        "is_active": true,
        "position": 0,
        "level": 0,
        "children": [
          {
            "category_id": 2,
            "parent_id": 1,
            "name": "Default Category",
            "is_active": true,
            "position": 1,
            "level": 1,
            "children": []
          }
        ]
      },
      "id": 1
    }

level:
  Retrieve level of categories for category/store view/website.
  Method Name: catalog_category.level
  Parameters:
    - website (string|int, optional) - Website ID or code
    - store (string|int, optional) - Store ID or code
    - categoryId (int, optional) - Category ID
  Return:
    - (array) - Array of categories with the following structure:
      - category_id (int) - Category ID
      - parent_id (int) - Parent category ID
      - name (string) - Category name
      - is_active (boolean) - Whether the category is active
      - position (int) - Position
      - level (int) - Level in the category tree
  Example Request:
    {
      "jsonrpc": "2.0",
      "method": "call",
      "params": [
        "session_id",
        "catalog_category.level",
        [null, "default", 2]
      ],
      "id": 1
    }
  Example Response:
    {
      "jsonrpc": "2.0",
      "result": [
        {
          "category_id": 3,
          "parent_id": 2,
          "name": "Furniture",
          "is_active": true,
          "position": 1,
          "level": 2
        },
        {
          "category_id": 4,
          "parent_id": 2,
          "name": "Electronics",
          "is_active": true,
          "position": 2,
          "level": 2
        }
      ],
      "id": 1
    }

info:
  Retrieve category data.
  Method Name: catalog_category.info
  Parameters:
    - categoryId (int, required) - Category ID
    - store (string|int, optional) - Store ID or code
    - attributes (array, optional) - Array of attributes to return
  Return:
    - (array) - Category data with the following structure:
      - category_id (int) - Category ID
      - is_active (boolean) - Whether the category is active
      - position (int) - Position
      - level (int) - Level in the category tree
      - Additional attributes as requested
      - parent_id (int) - Parent category ID
      - children (string) - Comma-separated list of child category IDs
      - all_children (string) - Comma-separated list of all child category IDs
  Example Request:
    {
      "jsonrpc": "2.0",
      "method": "call",
      "params": [
        "session_id",
        "catalog_category.info",
        [3, "default", ["name", "description", "url_key"]]
      ],
      "id": 1
    }
  Example Response:
    {
      "jsonrpc": "2.0",
      "result": {
        "category_id": 3,
        "is_active": true,
        "position": 1,
        "level": 2,
        "name": "Furniture",
        "description": "Furniture category description",
        "url_key": "furniture",
        "parent_id": 2,
        "children": "5,6,7",
        "all_children": "5,6,7,8,9"
      },
      "id": 1
    }
```

----------------------------------------

TITLE: Install Mollie Magento Module
DESCRIPTION: Installs the Mollie Magento module using Composer. This module enables various payment methods including iDEAL, Creditcard, Bancontact/Mister Cash, SOFORT, Bank transfer, Bitcoin, PayPal, and paysafecard.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/payment.md#_snippet_1

LANGUAGE: bash
CODE:
```
composer require mollie/magento
```

----------------------------------------

TITLE: Install OpenMage Image Cleaner
DESCRIPTION: Installs the image cleaner module for OpenMage and Magento 1.9, designed to help manage and clean up image files within the system.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/images.md#_snippet_1

LANGUAGE: bash
CODE:
```
composer require fballiano/openmage-image-cleaner
```

----------------------------------------

TITLE: Generating Patches with `symplify/vendor-patches`
DESCRIPTION: Steps to generate a patch file using the `symplify/vendor-patches` tool. This involves installing the tool, creating a backup, modifying the target file, generating the patch, and updating dependencies.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/blog/posts/guides/2023-05-01-customize-your-openmage.md#_snippet_2

LANGUAGE: sh
CODE:
```
composer require symplify/vendor-patches --dev
cp vendor/app/Mage.php vendor/app/Mage.php.old
# modify app/Mage.php to your liking
vendor/bin/vendor-patches generate
composer install
```

----------------------------------------

TITLE: Reinstall Mage_Sendfriend Module
DESCRIPTION: Installs the Mage_Sendfriend module using Composer. This is a command-line operation.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/changelog/index.md#_snippet_3

LANGUAGE: bash
CODE:
```
composer require openmage/module-mage-sendfriend
```

----------------------------------------

TITLE: MkDocs CLI Commands
DESCRIPTION: Common commands for interacting with the MkDocs command-line interface. Includes creating a new project, serving the documentation locally with live reloading, building the static site, and displaying help information.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/mkdocs.md#_snippet_1

LANGUAGE: bash
CODE:
```
mkdocs new [dir-name]
```

LANGUAGE: bash
CODE:
```
mkdocs serve
```

LANGUAGE: bash
CODE:
```
mkdocs build
```

LANGUAGE: bash
CODE:
```
mkdocs help
```

----------------------------------------

TITLE: JSON-RPC API with HTTP Basic Authentication
DESCRIPTION: Provides a new, faster JSON-RPC API for Magento. Supports installation and login via HTTP basic authentication.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/blog/posts/releases/2023-09-05-release-20-1-1.md#_snippet_0

LANGUAGE: APIDOC
CODE:
```
JSON-RPC API:
  Endpoint: /api/jsonrpc
  Authentication:
    - HTTP Basic Authentication for install-login
  Methods:
    - install(username, password, ...)
    - login(username, password)
  Performance:
    - Faster than SOAP API
```

----------------------------------------

TITLE: Command Line Tool Requirements
DESCRIPTION: Specifies the required command-line tools for Magento LTS installations. The 'patch' command version 2.7+ (or 'gpatch' on macOS/Homebrew) is necessary.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/requirements.md#_snippet_3

LANGUAGE: Shell
CODE:
```
Command `patch` 2.7+ (or `gpatch` on macOS/Homebrew)
```

----------------------------------------

TITLE: Install AOEpeople/Aoe_Scheduler
DESCRIPTION: Installs the AOEpeople/Aoe_Scheduler module using Composer. This module enhances Magento's default cron functionality.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/modules/cron.md#_snippet_0

LANGUAGE: bash
CODE:
```
composer require --dev aoepeople/aoe_scheduler
```

----------------------------------------

TITLE: Nginx URL Rewrite for API
DESCRIPTION: This Nginx configuration snippet shows how to rewrite requests to the API endpoint. It captures the type parameter from the URL and passes it to the `api.php` script.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/install/secure-install.md#_snippet_2

LANGUAGE: nginx
CODE:
```
rewrite ^/api/(\w+).*$ /api.php?type=$1 last;
```

----------------------------------------

TITLE: Apache URL Rewrite for API
DESCRIPTION: This Apache configuration snippet demonstrates how to rewrite incoming requests to the API endpoint to the actual API script (`api.php`). It ensures that requests to `/api/rest` are correctly routed.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/users/install/secure-install.md#_snippet_1

LANGUAGE: apache
CODE:
```
RewriteRule ^api/rest api.php?type=rest [QSA,L]
```

----------------------------------------

TITLE: Reinstall Mage_PageCache Module
DESCRIPTION: Installs the Mage_PageCache module using Composer. This is a command-line operation.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/developers/changelog/index.md#_snippet_1

LANGUAGE: bash
CODE:
```
composer require openmage/module-mage-pagecache
```

----------------------------------------

TITLE: Retrieve Product Information via OpenMage API (PHP)
DESCRIPTION: Shows a PHP example using cURL to fetch product details from the OpenMage API. It includes making a 'call' method request with a session ID and the specific resource method 'catalog_product.info'.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/api/jsonrpc/index.md#_snippet_6

LANGUAGE: php
CODE:
```
<?php
// PHP example of retrieving product information
$sessionId = '8b98a77a37f50d3d472302981e86aab2'; // From login response
$jsonRpcUrl = 'https://your-magento-store.com/api/jsonrpc';

$requestData = array(
    'jsonrpc' => '2.0',
    'method' => 'call',
    'params' => [
        $sessionId,
        'catalog_product.info',
        'product_sku_123'
    ],
    'id' => 2
);

$ch = curl_init($jsonRpcUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
print_r($result['result']);
?>
```

----------------------------------------

TITLE: Magento Category Management API
DESCRIPTION: This section details the API methods for managing product categories in Magento LTS. It covers creating, updating, moving, and deleting categories, as well as retrieving products assigned to a category. Each method includes parameter descriptions, return values, example requests, example responses, and potential errors.

SOURCE: https://github.com/openmage/magento-lts/blob/main/docs/content/api/jsonrpc/resources/catalog_category.md#_snippet_1

LANGUAGE: APIDOC
CODE:
```
catalog_category.create(parentId: int, categoryData: array, store: string|int = null)
  Creates a new category.
  Parameters:
    parentId (int, required): Parent category ID.
    categoryData (array, required): Category data including name, active status, position, sort options, menu inclusion, URL key, description, meta information, display mode, and custom attributes.
    store (string|int, optional): Store ID or code.
  Returns:
    int: ID of the created category.
  Possible Errors:
    data_invalid: Invalid data provided.
    not_exists: Parent category does not exist.
  Example Request:
    {
      "jsonrpc": "2.0",
      "method": "call",
      "params": [
        "session_id",
        "catalog_category.create",
        [
          2,
          {
            "name": "New Category",
            "is_active": true,
            "position": 3,
            "description": "New category description",
            "url_key": "new-category"
          },
          "default"
        ]
      ],
      "id": 1
    }
  Example Response:
    {
      "jsonrpc": "2.0",
      "result": 10,
      "id": 1
    }
```

LANGUAGE: APIDOC
CODE:
```
catalog_category.update(categoryId: int, categoryData: array, store: string|int = null)
  Updates category data.
  Parameters:
    categoryId (int, required): Category ID to update.
    categoryData (array, required): Category data to update (same structure as in create method).
    store (string|int, optional): Store ID or code.
  Returns:
    boolean: True on success.
  Possible Errors:
    data_invalid: Invalid data provided.
    not_exists: Category does not exist.
  Example Request:
    {
      "jsonrpc": "2.0",
      "method": "call",
      "params": [
        "session_id",
        "catalog_category.update",
        [
          3,
          {
            "name": "Updated Category Name",
            "description": "Updated description"
          },
          "default"
        ]
      ],
      "id": 1
    }
  Example Response:
    {
      "jsonrpc": "2.0",
      "result": true,
      "id": 1
    }
```

LANGUAGE: APIDOC
CODE:
```
catalog_category.move(categoryId: int, parentId: int, afterId: int = null)
  Moves a category within the category tree.
  Parameters:
    categoryId (int, required): The ID of the category to move.
    parentId (int, required): The new parent category ID.
    afterId (int, optional): The ID of the category to place the moved category after. If null, it will be placed at the end.
  Returns:
    boolean: True on success.
  Possible Errors:
    not_moved: Category could not be moved.
    not_exists: Category does not exist.
  Example Request:
    {
      "jsonrpc": "2.0",
      "method": "call",
      "params": [
        "session_id",
        "catalog_category.move",
        [3, 4, null]
      ],
      "id": 1
    }
  Example Response:
    {
      "jsonrpc": "2.0",
      "result": true,
      "id": 1
    }
```

LANGUAGE: APIDOC
CODE:
```
catalog_category.delete(categoryId: int)
  Deletes a category.
  Parameters:
    categoryId (int, required): The ID of the category to delete.
  Returns:
    boolean: True on success.
  Possible Errors:
    not_deleted: Category could not be deleted.
    not_exists: Category does not exist.
  Example Request:
    {
      "jsonrpc": "2.0",
      "method": "call",
      "params": [
        "session_id",
        "catalog_category.delete",
        3
      ],
      "id": 1
    }
  Example Response:
    {
      "jsonrpc": "2.0",
      "result": true,
      "id": 1
    }
```

LANGUAGE: APIDOC
CODE:
```
catalog_category.assignedProducts(categoryId: int, store: string|int = null)
  Retrieves a list of products assigned to a category.
  Parameters:
    categoryId (int, required): The ID of the category.
    store (string|int, optional): Store ID or code.
  Returns:
    array: An array of products, each with product_id, type, set, sku, and position.
  Example Request:
    {
      "jsonrpc": "2.0",
      "method": "call",
      "params": [
        "session_id",
        "catalog_category.assignedProducts",
        [3, "default"]
      ],
      "id": 1
    }
  Example Response:
    {
      "jsonrpc": "2.0",
      "result": [
        {
          "product_id": 14,
          "type": "simple",
          "set": 4,
          "sku": "product1",
          "position": 1
        },
        {
          "product_id": 15,
          "type": "simple",
          "set": 4,
          "sku": "product2",
          "position": 2
        }
      ],
      "id": 1
    }
```