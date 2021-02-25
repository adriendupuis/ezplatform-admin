Adrien Dupuis' eZ Platform Admin Extension Bundle
=================================================

Bundle to
- extended eZ Platform Admin UI
- add command line administration tools


Install
-------

1. Add to [composer.json `repositories`](https://getcomposer.org/doc/04-schema.md#repositories): `{ "type": "vcs", "url": "https://github.com/adriendupuis/ezplatform-admin.git" }`
1. Execute `composer require adriendupuis/ezplatform-admin;`
1. Add to config/bundles.php: `AdrienDupuis\EzPlatformAdminBundle\AdrienDupuisEzPlatformAdminBundle::class => ['all' => true],`
1. Execute `cp vendor/adriendupuis/ezplatform-admin/src/bundle/Resources/config/routes/adriendupuis_admin.yaml config/routes/adriendupuis_admin.yaml;`
1. Pick ideas from [parameters.yaml](src/bundle/Resources/config/parameters.yaml) or fully import it; from, for example, config/services.yaml:
```yaml
imports:
    - { resource: ../vendor/adriendupuis/ezplatform-admin/src/bundle/Resources/config/parameters.yaml }
```


Features
--------

* Content Usage
  - Content Type Usage: Content count per content type.
  - Field Usage (Example Finder): Find best and bad content examples for each field of a content type, and report field usage statistics.
  - Language Usage: Content count per language.
  - Landing Page Usage: Layout usage count and block usage count.
* Admin
  - Identification: Find content, location, content type or content type field definitions from an ID or an identifier (like `1`, `folder`, `user*` or `ez*text`)
* Navigation
  - Tab Opener: Open a tab according to URL hash. Examples: Right-click on a tab and open it in a new window, the tab is active; Reload a page, tab is still active.
* Commands
  - `ezuser:create` to create a user
  - `ezuser:password` to change a user password
  - `ezuser:enable` to enable a user

Contribute
----------

### Translations

English example:
```shell
bin/console translation:extract en \
  --bundle AdrienDupuisEzPlatformAdminBundle \
  --output-dir vendor/adriendupuis/ezplatform-admin/src/bundle/Resources/translations/ \
  --domain ad_admin_content_usage --domain ad_admin_identification \
;
```

### TODO

* Features
* Developments
  * Quality
    - Unit tests
