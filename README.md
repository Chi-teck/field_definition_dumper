**Outdated: These commands have been moved to Drush core (https://github.com/drush-ops/drush/pull/5517).**

# Field Definition Dumper

Drush commands to dump definitions of Drupal fields, widgets and formatters.

Field display options in Drupal are not well documented. That makes it hard
to find correct values for them when fields are defined in code. For instance,
when you specify base field definitions for a custom entity type.

```php
$fields['uid'] = BaseFieldDefinition::create('entity_reference')
  ->setLabel(t('Authored by'))
  ->setSetting('target_type', 'user')
  ->setDisplayOptions('form', [
    'type' => 'entity_reference_autocomplete',
    'settings' => [
      'match_operator' => 'CONTAINS',
      'size' => '30',
      'placeholder' => '',
    ],
  ])
  ->setDisplayOptions('view', [
    'label' => 'hidden',
    'type' => 'author',
  ]);
```

Another use case is rendering a field with specific formatter and settings.
```php
$display_options = [
  'type' => 'entity_reference_label',
  'settings' => ['link' => FALSE],
];
$build = $node->uid->view($display_options);
```

```twig
{# Assuming Twig Tweak module is installed #}
{% set display_options = { type: 'entity_reference_label', settings: {link: false} } %}
{{ node.uid|view(display_options) }}
```

These Drush commands will save you from searching for the appropriate options
in the source code of widgets and formatters.

## Installation
```
composer require chi-teck/field_definition_dumper --dev
drush en field_definition_dumper
```

Given that the module is intended for local development, it's recommended you
exclude it from the configuration by adding the following line to your local
settings.php file.
```php
$settings['config_exclude_modules'] = ['field_definition_dumper'];
```

## Usage
```
drush field:dump:types --help
drush field:dump:widgets --help
drush field:dump:formatters --help
```

## License
GNU General Public License, version 2 or later.
