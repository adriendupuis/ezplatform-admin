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
  - User:
    - `ezuser:create` to create a user
    - `ezuser:password` to change a user password
    - `ezuser:enable` to enable a user
  - Database & Storage Integrity:
    - `integrity:check` to run all command from this name space
    - `integrity:check:language` to check
      - language declared in config files against language declared in database
      - language used in content object against language declared in database
    - `integrity:check:tree` to check content tree consistency
      - find location which parent is missing
      - find location which content is missing
    - `integrity:check:storage` to find file missing from storage and storage unused files
    - `integrity:fix:remove-unused-files` to remove from storage file unused by a field.
    - `integrity:check:config` to check miscellaneous PHP and eZ Platform configurations
      - check that `upload_max_filesize` and `post_max_size` are coherent with usage of field types like `ezbinaryfile`.

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

### Run unit tests

```shell
bin/phpunit vendor/adriendupuis/ezplatform-admin/tests/;
```

### TODO

* Features
* Developments
  * Quality
    - Unit tests
