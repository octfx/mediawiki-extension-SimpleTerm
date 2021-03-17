# SimpleTerms

A partial rewrite of Extension Lingo.

## Configuration
| Key                                 | Description                                                                                                                                         | Example                         | Default      |
|-------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------|---------------------------------|--------------|
| $wgSimpleTermsPage                  | Name of the glossary wiki page                                                                                                                      | Glossary                        | "Glossary"   |
| $wgSimpleTermsNamespaces            | Namespaces SimpleTerms is active                                                                                                                    | [0]                             | [0]          |
| $wgSimpleTermsBackend               | The backend to use                                                                                                                                  |                                 | BasicBackend |
| $wgSimpleTermsDisplayOnce           | Only replace the first term in each page                                                                                                            | false                           | false        |
| $wgSimpleTermsUseCache              | Cache the definition list, this needs to be true.                                                                                                   | true                            | false        |
| $wgSimpleTermsCacheType             | Type of cache to use, if null use main cache                                                                                                        | true                            | false        |
| $wgSimpleTermsEnableApprovedRevs    | Use the approved version of the SimpleTerms page                                                                                                    | true                            | false        |
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
