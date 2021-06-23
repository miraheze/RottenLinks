Rotten Links extension for MediaWiki
==============================
Version: 1.0.16

Developed by John Lewis.

Licensed under the GPLv3 (or later) License.

This is the RottenLinks Extension to check the state of all external links on a MediaWiki install.

Minimum requirements
--------------------------------
Mediawiki 1.36+

Installation instructions
---------------------------------

Clone from git into the extensions folder using the following: (requires Git)

        git clone https://github.com/miraheze/RottenLinks.git

Edit your LocalSettings.php and, at the end of the file, add the following:

        wfLoadExtension( 'RottenLinks' );

Custom variables
------------------------

        $wgRottenLinksBadCodes

Holds a list of HTTP codes that are considered bad. Defaults to [ "0", "400", "401", "403", "404", "405", "502", "503", "504" ].

        $wgRottenLinksCurlTimeout

Sets the timeout for cURL in seconds. Defaults to 30.

        $wgRottenLinksExcludeProtocols

Holds a list of protocols that should not be checked for validity. Defaults to [ "tel", "mailto" ].

        $wgRottenLinksExternalLinkTarget

Sets the external link target (_self for the current tab or _blank for new tab). Defaults to _self.

        $wgRottenLinksExcludeWebsites

List of websites to blacklist checking of response codes for. Defaults to false. Omit the protocol, e.g. use $wgRottenLinksExcludeWebsites = [ "www.example.com" ];
