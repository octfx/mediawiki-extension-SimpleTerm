# SimpleTerms

A partial rewrite of Extension Lingo.

Local tests with 2000 Terms and definition lengths of ~200 chars caused no noticeable slowdowns, even when running the replacements on each page view.

## Configuration
| Key                                 | Description                                                                                                                                         | Example                         | Default      |
|-------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------|---------------------------------|--------------|
| $wgSimpleTermsPage                  | Name of the glossary wiki page                                                                                                                      | Glossary                        | "Glossary"   |
| $wgSimpleTermsNamespaces            | Namespaces SimpleTerms is active                                                                                                                    | [0]                             | [0]          |
| $wgSimpleTermsDisabledElements      | List of ignores html elements                                                                                                                       | ['table']                       | [h1-6]       |
| $wgSimpleTermsEnableApprovedRevs    | Use the approved version of the SimpleTerms page. Requires Extension:ApprovedRevs to be active.                                                     | true                            | false        |
| $wgSimpleTermsDisplayOnce           | Only replace the first term in each page                                                                                                            | false                           | false        |
| $wgSimpleTermsRunOnPageView         | Replace the terms in the page on each page view                                                                                                     | false                           | true         |
| $wgSimpleTermsWriteIntoParserOutput | Replace the terms in the parser output, which gets saved into the cache                                                                             | true                            | false        |
| $wgSimpleTermsBackend               | The backend to use. Defaults to 'BasicBackend', which loads the glossary from the defined wiki page.                                                |                                 | BasicBackend |
| $wgSimpleTermsCacheType             | Type of cache to use, if set to null the main cache will be used. Used as ObjectCache::getInstance( $cacheType )                                    |                                 | null         |
| $wgSimpleTermsCacheExpiry           | Expiry of DefinitionList in cache                                                                                                                   | 2592000                         | 2592000      |


## Example
```
;FTP
:File Transfer Protocol

;AAAAA
:American Association Against Acronym Abuse

;ACK
:Acknowledge
:Acklington railway station

;U.S.A.
;USA
:United States of America
```

## Example LocalSettings
```php
wfLoadExtension('SimpleTerms');
$wgSimpleTermsPage = 'Glossary';
$wgSimpleTermsNamespaces = [
    NS_MAIN
];
$wgSimpleTermsDisabledElements = [
    'table',
    'h1',
    'h2',
];
$wgSimpleTermsAllowHtml = false;
$wgSimpleTermsEnableApprovedRevs = false;
$wgSimpleTermsDisplayOnce = false;
$wgSimpleTermsRunOnPageView = false;
$wgSimpleTermsWriteIntoParserOutput = true;
$wgSimpleTermsBackend = 'BasicBackend';

// See: https://atomiks.github.io/tippyjs/v6/all-props/
// This _needs_ to be valid JSON, else it will be ignored
$wgSimpleTermsTippyConfig = <<<TIPPY
{
  "duration": 0,
  "arrow": false,
  "delay": [1000, 200]
}
TIPPY;
```