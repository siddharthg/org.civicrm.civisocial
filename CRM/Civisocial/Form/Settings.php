<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Civisocial_Form_Settings extends CRM_Core_Form {
  private $_settingFilter = array('group' => 'civisocial');

  private $_submittedValues = array();
  private $_settings = array();

  function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Civisocial OAuth Credential Preferences'));

    $settings = $this->getFormSettings();
    foreach ($settings as $name => $setting) {
      if (isset($setting['quick_form_type'])) {
        $add = 'add' . $setting['quick_form_type'];
        if ($add == 'addElement') {
          $this->$add($setting['html_type'], $name, ts($setting['title']), CRM_Utils_Array::value('html_attributes', $setting, array ()));
        }
        else {
          $this->$add($name, ts($setting['title']));
        }
        $this->assign("{$setting['description']}_description", ts('description'));
      }
    }
    $this->addButtons(array(
      array (
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      )
    ));
    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }
  function postProcess() {
    $this->_submittedValues = $this->exportValues();
    $this->saveSettings();
    parent::postProcess();
  }
  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons". These
    // items don't have labels. We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }
  /**
   * Get the settings we are going to allow to be set on this form.
   *
   * @return array
   */
  function getFormSettings() {
    if (empty($this->_settings)) {
      $settings = civicrm_api3('setting', 'getfields', array('filters' => $this->_settingFilter));
    }
    // $extraSettings = civicrm_api3('setting', 'getfields', array('filters' => array('group' => 'accountsync')));
    // $settings = $settings['values'] + $extraSettings['values'];
    return $settings['values'];
  }
  /**
   * Get the settings we are going to allow to be set on this form.
   *
   * @return array
   */
  function saveSettings() {
    $settings = $this->getFormSettings();
    $values = array_intersect_key($this->_submittedValues, $settings);
    civicrm_api3('setting', 'create', $values);
  }
  /**
   * Set defaults for form.
   *
   * @see CRM_Core_Form::setDefaultValues()
   */
  function setDefaultValues() {
    $existing = civicrm_api3('setting', 'get', array('return' => array_keys($this->getFormSettings())));
    $defaults = array();
    $domainID = CRM_Core_Config::domainID();
    foreach ($existing['values'][$domainID] as $name => $value) {
      $defaults[$name] = $value;
    }
    return $defaults;
  }

  // function buildQuickForm() {

  //   // add form elements
  //   $this->add(
  //     'select', // field type
  //     'favorite_color', // field name
  //     'Favorite Color', // field label
  //     $this->getColorOptions(), // list of options
  //     true // is required
  //   );
  //   $this->addButtons(array(
  //     array(
  //       'type' => 'submit',
  //       'name' => ts('Submit'),
  //       'isDefault' => TRUE,
  //     ),
  //   ));

  //   // export form elements
  //   $this->assign('elementNames', $this->getRenderableElementNames());
  //   parent::buildQuickForm();
  // }

  // function postProcess() {
  //   $values = $this->exportValues();
  //   $options = $this->getColorOptions();
  //   CRM_Core_Session::setStatus(ts('You picked color "%1"', array(
  //     1 => $options[$values['favorite_color']]
  //   )));
  //   parent::postProcess();
  // }

  // function getColorOptions() {
  //   $options = array(
  //     '' => ts('- select -'),
  //     '#f00' => ts('Red'),
  //     '#0f0' => ts('Green'),
  //     '#00f' => ts('Blue'),
  //     '#f0f' => ts('Purple'),
  //   );
  //   foreach (array('1','2','3','4','5','6','7','8','9','a','b','c','d','e') as $f) {
  //     $options["#{$f}{$f}{$f}"] = ts('Grey (%1)', array(1 => $f));
  //   }
  //   return $options;
  // }

  // /**
  //  * Get the fields/elements defined in this form.
  //  *
  //  * @return array (string)
  //  */
  // function getRenderableElementNames() {
  //   // The _elements list includes some items which should not be
  //   // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
  //   // items don't have labels.  We'll identify renderable by filtering on
  //   // the 'label'.
  //   $elementNames = array();
  //   foreach ($this->_elements as $element) {
  //     /** @var HTML_QuickForm_Element $element */
  //     $label = $element->getLabel();
  //     if (!empty($label)) {
  //       $elementNames[] = $element->getName();
  //     }
  //   }
  //   return $elementNames;
  // }
}
