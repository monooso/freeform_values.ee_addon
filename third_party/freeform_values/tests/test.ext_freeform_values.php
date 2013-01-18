<?php if ( ! defined('BASEPATH')) exit('Invalid file request');

/**
 * Freeform Values extension tests.
 *
 * @author          Stephen Lewis (http://github.com/experience/)
 * @copyright       Experience Internet
 * @package         Freeform_values
 */

require_once PATH_THIRD .'freeform_values/ext.freeform_values.php';
require_once PATH_THIRD .'freeform_values/models/freeform_values_model.php';

class Test_freeform_values_ext extends Testee_unit_test_case {

  private $_model;
  private $_pkg_version;
  private $_subject;


  /* --------------------------------------------------------------
   * PUBLIC METHODS
   * ------------------------------------------------------------ */

  /**
   * Constructor.
   *
   * @access  public
   * @return  void
   */
  public function setUp()
  {
    parent::setUp();

    // Generate the mock model.
    Mock::generate('Freeform_values_model', get_class($this) .'_mock_model');

    /**
     * The subject loads the models using $this->EE->load->model().
     * Because the Loader class is mocked, that does nothing, so we
     * can just assign the mock models here.
     */

    $this->EE->freeform_values_model = $this->_get_mock('model');
    $this->_model = $this->EE->freeform_values_model;

    // Called in the constructor.
    $this->_pkg_version = '2.3.4';
    $this->_model->setReturnValue('get_package_version', $this->_pkg_version);

    $this->_subject = new Freeform_values_ext();
  }


  public function test__activate_extension__calls_model_install_method_with_correct_arguments()
  {
    $hooks = array(
      'freeform_module_pre_form_parse',
      'freeform_module_validate_end'
    );

    $this->_model->expectOnce('install_extension',
      array($this->_pkg_version, $hooks));

    $this->_subject->activate_extension();
  }


  public function test__disable_extension__calls_model_uninstall_method_with_correct_arguments()
  {
    $this->_model->expectOnce('uninstall_extension');
    $this->_subject->disable_extension();
  }


  public function test__update_extension__calls_model_update_method_with_correct_arguments_and_honors_return_value()
  {
    $installed  = '1.2.3';
    $result     = 'Ciao a tutti!';    // Could be anything.

    $this->_model->expectOnce('update_package', array($installed));
    $this->_model->setReturnValue('update_package', $result);

    $this->assertIdentical($result,
      $this->_subject->update_extension($installed));
  }


}


/* End of file      : test.ext_freeform_values.php */
/* File location    : third_party/freeform_values/tests/test.ext_freeform_values.php */
