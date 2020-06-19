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
  - Example Finder: Find best and bad content examples for each field of a content type.
  - Language Usage: Content count per language.
* Navigation
  - Tab Opener: Open a tab according to URL hash. Examples: Right-click on a tab and open it in a new window, the tab is active; Reload a page, tab is still active.
* Commands
  - `ezuser:create` to create an user from command line

TODO
----

* Features
  * Users
    - Password update command
* Developments
  * Quality
    - Unit tests