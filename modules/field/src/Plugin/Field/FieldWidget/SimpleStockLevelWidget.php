<?php

namespace Drupal\commerce_stock_field\Plugin\Field\FieldWidget;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Plugin implementation of the 'commerce_stock_level' widget.
 *
 * @FieldWidget(
 *   id = "commerce_stock_level_simple",
 *   module = "commerce_stock_field",
 *   label = @Translation("Simple stock level widget"),
 *   field_types = {
 *     "commerce_stock_level"
 *   }
 * )
 */
class SimpleStockLevelWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'transaction_note' => FALSE,
      'entry_system' => 'simple',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Entry system: @entry_system', ['@entry_system' => $this->getSetting('entry_system')]);
    if ($this->getSetting('entry_system') != 'transactions') {
      $summary[] = $this->t('Transaction note: @transaction_note', ['@transaction_note' => $this->getSetting('transaction_note') ? 'Yes' : 'No']);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $element['entry_system'] = [
      '#type' => 'select',
      '#title' => $this->t('Entry system'),
      '#options' => [
        'simple' => $this->t('Simple (absolute stock level)'),
        'basic' => $this->t('Basic transactions'),
        'transactions' => $this->t('Link to transactions form'),
      ],
      '#default_value' => $this->getSetting('entry_system'),
    ];
    $element['transaction_note'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Provide note'),
      '#default_value' => $this->getSetting('transaction_note'),
      '#description' => $this->t('Provide an input box for a transaction note.'),
      '#states' => [
        'invisible' => [
          'select[name="fields[field_stock_level][settings_edit_form][settings][entry_system]"]' => ['value' => 'transactions']
        ]
      ]
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field = $items->first();
    $entity = $items->getEntity();
    if ($entity instanceof ProductVariationInterface) {
      // Get the available stock level.
      $level = $field->available_stock;
    }
    else {
      // No stock if this is not a product variation.
      return [];
    }
    $elements = [];
    $entry_system = $this->getSetting('entry_system');
    $elements['stock'] = [
      '#type' => 'fieldgroup',
      '#title' => $this->t('Stock'),
    ];
    // Set the entry system so we know how to set the value
    // @see StockLevel::setValue().
    $elements['stock']['entry_system'] = [
      '#type' => 'value',
      '#value' => $entry_system
    ];
    if (empty($entity->id())) {
      // We don't have a product ID as yet.
      $elements['stock']['stock_label'] = [
        '#type' => 'html_tag',
        '#tag' => 'strong',
        '#value' => $this->t('In order to set the stock level you need to save the product first!'),
      ];


    }
    else {
      $elements['stock']['stocked_variation_id'] = [
        '#type' => 'value',
        '#value' => $entity->id(),
      ];
      if ($entry_system == 'simple') {
        $elements['stock']['value'] = [
          '#description' => $this->t('Total stock level available for this item.'),
          '#type' => 'textfield',
          '#default_value' => $level,
          '#size' => 10,
          '#maxlength' => 12,
        ];
      }
      elseif ($entry_system == 'basic') {
        $elements['stock']['stock_label'] = [
          '#type' => 'html_tag',
          '#tag' => 'strong',
          '#value' => $this->t('Stock level: @stock_level', ['@stock_level' => $level]),
        ];
        $elements['stock']['adjustment'] = [
          '#title' => $this->t('Transaction'),
          '#description' => $this->t('Valid options: [number] (for a new stock level), +[number] (to add to existing stock), -[number] (to remove from existing stock).'),
          '#type' => 'textfield',
          '#default_value' => '',
          '#size' => 7,
          '#maxlength' => 7,
        ];
      }
      elseif ($entry_system == 'transactions') {
        $elements['stock']['stock_label'] = [
          '#type' => 'html_tag',
          '#tag' => 'strong',
          '#value' => $this->t('Stock level: @stock_level', ['@stock_level' => $level]),
        ];
        $link = Link::createFromRoute(
          $this->t('New transaction'),
          'commerce_stock_ui.stock_transactions2',
          ['commerce_product_v_id' => $entity->id()],
          ['attributes' => ['target' => '_blank']]
        )->toString();
        $elements['stock']['stock_transactions_label'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('Please use the @transaction page to make changes.', ['@transaction' => $link]),
        ];
      }
      if ($this->getSetting('transaction_note') && ($entry_system != 'transactions')) {
        $elements['stock']['stock_transaction_note'] = [
          '#title' => $this->t('Transaction note'),
          '#description' => $this->t('Add a note to this transaction.'),
          '#type' => 'textfield',
          '#default_value' => '',
          '#size' => 20,
        ];
      }
    }

    return $elements;
  }

  /**
   * Simple stock form - Used to update the stock level.
   *
   * @todo: This is not go live ready code,
   */
  public function validateSimple($element, FormStateInterface $form_state) {
    if (!is_numeric($element['#value'])) {
      $form_state->setError($element, $this->t('Stock must be a number.'));
      return;
    }
    // @todo Needs to mark element as needing updating? Updated qty??
  }

  /**
   * Validates a basic stock field widget form.
   */
  public function validateBasic($element, FormStateInterface $form_state) {
    // @to do.
    return TRUE;
  }

  /**
   * Submits the form.
   */
  public static function closeForm($form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    drupal_set_message('updated STOCK');
  }

  /**
   * Submits the form.
   */
  public function submitAll(array &$form, FormStateInterface $form_state) {
    drupal_set_message('updated STOCK!!');
  }

}
