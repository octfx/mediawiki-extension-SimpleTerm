# SimpleTerms

A partial rewrite of Extension Lingo.

## Configuration
| Key                                 | Description                                                                                                                                         | Example                         | Default      |
|-------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------|---------------------------------|--------------|
| $wgSimpleTermsPage                  | Name of the glossary wiki page                                                                                                                      | Glossary                        | "Glossary"   |
| $wgSimpleTermsNamespaces            | Namespaces SimpleTerms is active                                                                                                                    | [0]                             | [0]          |
| $wgSimpleTermsDisabledElements      | List of ignores html elements                                                                                                                       | ['table']                       | []           |
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
