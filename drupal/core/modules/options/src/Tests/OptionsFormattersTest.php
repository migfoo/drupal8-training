<?php

/**
 * @file
 * Contains \Drupal\options\Tests\OptionsFormattersTest.
 */

namespace Drupal\options\Tests;

/**
 * Tests the formatters provided by the options module.
 *
 * @see \Drupal\options\Plugin\field\formatter\OptionsDefaultFormatter
 * @see \Drupal\options\Plugin\field\formatter\OptionsKeyFormatter
 */
class OptionsFormattersTest extends OptionsFieldUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Options field formatters',
      'description' => 'Test the Options field type formatters.',
      'group' => 'Field types',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests the formatters.
   */
  public function testFormatter() {
    $entity = entity_create('entity_test');
    $entity->{$this->fieldName}->value = 1;

    $items = $entity->get($this->fieldName);

    $build = $items->view();
    $this->assertEqual($build['#formatter'], 'list_default', 'Ensure to fall back to the default formatter.');
    $this->assertEqual($build[0]['#markup'], 'One');

    $build = $items->view(array('type' => 'list_key'));
    $this->assertEqual($build['#formatter'], 'list_key', 'The chosen formatter is used.');
    $this->assertEqual($build[0]['#markup'], 1);
  }

}
