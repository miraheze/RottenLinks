## ChangeLog for RottenLinks

### 1.0.19 (27-09-2022)
* Don't use Maintenance::$mDescription directly

### 1.0.18 (07-05-2022)
* Fix for URLs containing more than one :// such as
  https://web.archive.org/web/20100205034127/https://github.com/
* Fix for websites that don't support HEAD requests
* Fix for non-ASCII domain names such as bücher.de
* Add wgRottenLinksUserAgent config setting

### 1.0.17 (13-08-2021)
* Use MultiHttpClient
* Lower minimum required MediaWiki version to 1.35.3

### 1.0.16 (15-06-2021)
* Fix sql patches for case when rottenlinks.rl_externallink is primary key

### 1.0.15 (15-06-2021)
* Require MediaWiki 1.36.0
* DB_MASTER -> DB_PRIMARY

### 1.0.14 (05-06-2021)
* Make protocols lowercase before making request

### 1.0.13 (13-05-2021)
* Use HttpRequestFactory

### 1.0.12 (26-02-2021)
* Set url to lowercase for ExcludeProtocols

### 1.0.11 (15-02-2021)
* Fix the schema change errors and some possible vulnerabilities.

### 1.0.10 (09-10-2020)
* Fix primary key by introducing rottenlinks.rl_id and making it the primary key.
  Also revert rl_externallink back to a blob.

### 1.0.9 (08-09-2020)
* Added Primary Key to database table.

### 1.0.8 (22-03-2020)
* Fixed handling of protocol independent links (//meta.miraheze.org/).
* Introduced excluding a website from being checked.
* Add link to MediaWiki docs to form the basis of a help page (for now?).
* Converted to MediaWiki Config Registry.
* Limit display length of URLs to 50 characters (for now?).

### 1.0.7 (08-03-2019)
* Fix date handling.

### 1.0.6 (05-02-2019)
* Remove namespace exclusion.
* Fix limit selection.
* Show HTTP code on statistics.

### 1.0.5 (03-02-2019)
* Introduce ability to exclude namespaces ($wgRottenLinksExcludeNamespaces).
* Allow page limit to be configurable by end user.
* Allow controlling of how links open using a config variable.
* Add viewing of link statistics to Special:RottenLinks.
* Run time statistics.
* Link colourisation.

### 1.0.4 (01-11-2018)
* Fix path of LinkSearch.

### 1.0.3 (01-11-2018)
* Introduce the ability to exclude special protocols ($wgRottenLinksExcludeProtocols)
* Reduce page limit from 50 to 25.
* Show a links usage on the wiki.
* Added the ability to filter out good HTTP responses (what is bad is considered by $wgRottenLinksBadCodes, will be used later for other purposes).

### 1.0.2 (29-10-2018)
* Make cURL timeout configuable  with "wgRottenLinksCurlTimeout"
* Fixes a issue when curl was not timing out.

### 1.0.1 (16-10-2018)
* Lowered cURL to 30 seconds from PHP standard 300.
* Added a code 0 text input as standard MediaWiki doesn't recognise code 0 responses.
* Added qqq.json i18n.

### 1.0.0 (14-10-2018)
* Initial commit of code.
