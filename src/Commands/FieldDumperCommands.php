<?php declare(strict_types = 1);

namespace Drupal\field_definition_dumper\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Field\WidgetPluginManager;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * Defines field dumper Drush commands.
 */
final class FieldDumperCommands extends DrushCommands {

  public function __construct(
    private FieldTypePluginManagerInterface $typePluginManager,
    private WidgetPluginManager $widgetPluginManager,
    private FormatterPluginManager $formatterPluginManager,
  ) {
    parent::__construct();
  }

  #[CLI\Command(name: 'field:dump:types')]
  #[CLI\FieldLabels(
    labels: [
      'id' => 'ID',
      'label' => 'Label',
      'default_widget' => 'Default Widget',
      'default_formatter' => 'Default Formatter',
      'settings' => 'Settings',
    ],
  )]
  #[CLI\Help(
    description: 'Dump definitions of field types.',
    synopsis: 'drush field:dump:types [options]',
  )]
  #[CLI\FilterDefaultField(field: 'id')]
  public function dumpTypes(array $options = ['format' => 'yaml']): RowsOfFields {
    $processor = fn(array $definition): array => [
      'id' => $definition['id'],
      'label' => (string) $definition['label'],
      'default_widget' => $definition['default_widget'] ?? NULL,
      'default_formatter' => $definition['default_formatter'] ?? NULL,
      'settings' => BaseFieldDefinition::create($definition['id'])->getSettings(),
    ];
    $definitions = \array_map($processor, $this->typePluginManager->getDefinitions());
    $definitions = self::encodeDefinitions($definitions, $options['format']);
    return new RowsOfFields($definitions);
  }

  #[CLI\Command(name: 'field:dump:widgets')]
  #[CLI\Option(name: 'field-type', description: 'Applicable field type')]
  #[CLI\FieldLabels(
    labels: [
      'id' => 'ID',
      'label' => 'Label',
      'default_settings' => 'Default Settings',
      'field_types' => 'Field types',
      'settings' => 'Settings',
      'class' => 'Class',
      'provider' => 'Provider',
    ],
  )]
  #[CLI\DefaultFields(fields: ['id', 'label', 'default_settings', 'field_types'])]
  #[CLI\Help(
    description: 'Dump definitions of field widgets.',
    synopsis: 'drush field:dump:widgets [options]',
  )]
  #[CLI\FilterDefaultField(field: 'id')]
  public function dumpWidgets(array $options = ['format' => 'yaml', 'field-type' => NULL]): RowsOfFields {
    $processor = fn(array $definition): array => [
      'id' => $definition['id'],
      'label' => (string) $definition['label'],
      'default_settings' => $definition['class']::defaultSettings(),
      'field_types' => $definition['field_types'],
    ];
    $definitions = \array_map($processor, $this->widgetPluginManager->getDefinitions());
    if ($options['field-type']) {
      $definitions = self::filterByFieldType($definitions, $options['field-type']);
    }
    $definitions = self::encodeDefinitions($definitions, $options['format']);
    return new RowsOfFields($definitions);
  }

  #[CLI\Command(name: 'field:dump:formatters')]
  #[CLI\Option(name: 'field-type', description: 'Applicable field type')]
  #[CLI\FieldLabels(
    labels: [
      'id' => 'ID',
      'label' => 'Label',
      'default_settings' => 'Default Settings',
      'field_types' => 'Field types',
      'settings' => 'Settings',
      'class' => 'Class',
      'provider' => 'Provider',
    ],
  )]
  #[CLI\DefaultFields(fields: ['id', 'label', 'default_settings', 'field_types'])]
  #[CLI\Help(
    description: 'Dump definitions of field formatters.',
    synopsis: 'drush field:dump:formatters [options]',
  )]
  #[CLI\FilterDefaultField(field: 'id')]
  public function dumpFormatters(array $options = ['format' => 'yaml', 'field-type' => NULL]): RowsOfFields {
    $processor = fn(array $definition): array => [
      'id' => $definition['id'],
      'label' => (string) $definition['label'],
      'default_settings' => $definition['class']::defaultSettings(),
      'field_types' => $definition['field_types'],
      'class' => $definition['class'],
      'provider' => $definition['provider'],
    ];
    $definitions = \array_map($processor, $this->formatterPluginManager->getDefinitions());
    if ($options['field-type']) {
      $definitions = self::filterByFieldType($definitions, $options['field-type']);
    }
    $definitions = self::encodeDefinitions($definitions, $options['format']);
    return new RowsOfFields($definitions);
  }

  /**
   * Encodes rows.
   *
   * Some output formats i.e. 'table' expect that each row to be a scalar value.
   */
  private static function encodeDefinitions(array $definitions, string $format): mixed {
    $scalar_formats = ['table', 'csv', 'tsv', 'string'];
    $encode_data = static fn (mixed $data): mixed
      => \is_array($data) && \in_array($format, $scalar_formats) ? \json_encode($data) : $data;
    $encode_definition = static fn (array $definition): mixed => \array_map($encode_data, $definition);
    return \array_map($encode_definition, $definitions);
  }

  /**
   * Filters definitions by applicable field types.
   */
  private static function filterByFieldType(array $definitions, string $search): array {
    $match = static fn (string $field_type): bool => \str_contains($field_type, $search);
    $total_matches = static fn (array $field_types): int => \count(\array_filter($field_types, $match));
    $has_matches = static fn (array $definition): bool => $total_matches($definition['field_types']) > 0;
    return \array_filter($definitions, $has_matches);
  }

}

