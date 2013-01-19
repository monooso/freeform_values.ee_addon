<?php if ( ! defined('BASEPATH')) exit('Invalid file request');

/**
 * Freeform Values model tests.
 *
 * @author          Stephen Lewis (http://github.com/experience/)
 * @copyright       Experience Internet
 * @package         Freeform_values
 */

require_once PATH_THIRD .'freeform_values/models/freeform_values_model.php';

class Test_freeform_values_model extends Testee_unit_test_case {

  private $_extension_class;
  private $_module_class;
  private $_namespace;
  private $_package_name;
  private $_package_title;
  private $_package_version;
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

    $this->_namespace       = 'com.example';
    $this->_package_name    = 'MY_package';
    $this->_package_title   = 'My Package';
    $this->_package_version = '1.0.0';

    $this->_extension_class = 'My_package_ext';
    $this->_module_class    = 'My_package';

    $this->_subject = new Freeform_values_model($this->_package_name,
      $this->_package_title, $this->_package_version, $this->_namespace);
  }


  /* --------------------------------------------------------------
   * PACKAGE TESTS
   * ------------------------------------------------------------ */
  
  public function test__get_package_theme_url__pre_240_works()
  {
    if (defined('URL_THIRD_THEMES'))
    {
      $this->pass();
      return;
    }

    $package    = strtolower($this->_package_name);
    $theme_url  = 'http://example.com/themes/';
    $full_url   = $theme_url .'third_party/' .$package .'/';

    $this->EE->config->expectOnce('slash_item', array('theme_folder_url'));
    $this->EE->config->setReturnValue('slash_item', $theme_url);

    $this->assertIdentical($full_url, $this->_subject->get_package_theme_url());
  }


  public function test__get_site_id__returns_site_id_as_integer()
  {
    $site_id = '100';

    $this->EE->config->expectOnce('item', array('site_id'));
    $this->EE->config->setReturnValue('item', $site_id);

    $this->assertIdentical((int) $site_id, $this->_subject->get_site_id());
  }


  public function test__update_array_from_input__ignores_unknown_keys_and_updates_known_keys_and_preserves_unaltered_keys()
  {
    $base_array = array(
      'first_name'  => 'John',
      'last_name'   => 'Doe',
      'gender'      => 'Male',
      'occupation'  => 'Unknown'
    );

    $update_array = array(
      'dob'         => '1941-05-24',
      'first_name'  => 'Bob',
      'last_name'   => 'Dylan',
      'occupation'  => 'Writer'
    );

    $expected_result = array(
      'first_name'  => 'Bob',
      'last_name'   => 'Dylan',
      'gender'      => 'Male',
      'occupation'  => 'Writer'
    );

    $this->assertIdentical($expected_result,
      $this->_subject->update_array_from_input($base_array, $update_array));
  }


  /* --------------------------------------------------------------
   * EXTENSION TESTS
   * ------------------------------------------------------------ */

  public function test__delete_flashdata__deletes_flashdata_from_database_and_returns_true()
  {
    $row_id = 123;

    $this->EE->db->expectOnce('delete',
      array('freeform_values_flashdata', array('fv_id' => $row_id)));

    $this->assertIdentical(TRUE, $this->_subject->delete_flashdata($row_id));
  }


  public function test__delete_flashdata__does_nothing_and_returns_false_if_passed_an_invalid_row_id()
  {
    $this->EE->db->expectNever('delete');

    $this->assertIdentical(FALSE, $this->_subject->delete_flashdata(NULL));
    $this->assertIdentical(FALSE, $this->_subject->delete_flashdata(array()));
    $this->assertIdentical(FALSE, $this->_subject->delete_flashdata(new StdClass));
    $this->assertIdentical(FALSE, $this->_subject->delete_flashdata('Wibble'));
    $this->assertIdentical(FALSE, $this->_subject->delete_flashdata(-123));
    $this->assertIdentical(FALSE, $this->_subject->delete_flashdata(0));
  }


  public function test__get_flashdata__retrieves_flashdata_from_the_database_given_a_valid_id()
  {
    $post_data = array(
      'heisenberg'    => 'Walter White',
      'capn-cook'     => 'Jesse Pinkman',
      'generalissimo' => 'Gustavo Fring'
    );

    $db_result = $this->_get_mock('db_query');
    $db_row    = array('post_data' => json_encode($post_data));
    $row_id    = 123;

    $this->EE->db->expectOnce('select', array('post_data'));

    $this->EE->db->expectOnce('get_where', array(
      array('fv_id' => $row_id), 1));

    $this->EE->db->returns('get_where', $db_result);

    $db_result->expectOnce('row_array');
    $db_result->returns('row_array', $db_row);
  
    // Run the tests.
    $this->assertIdentical($post_data, $this->_subject->get_flashdata($row_id));
  }


  public function test__get_flashdata__returns_empty_array_if_passed_an_invalid_id()
  {
    $this->assertIdentical(array(), $this->_subject->get_flashdata(NULL));
    $this->assertIdentical(array(), $this->_subject->get_flashdata(array()));
    $this->assertIdentical(array(), $this->_subject->get_flashdata(new StdClass));
    $this->assertIdentical(array(), $this->_subject->get_flashdata('Wibble'));
    $this->assertIdentical(array(), $this->_subject->get_flashdata(-123));
    $this->assertIdentical(array(), $this->_subject->get_flashdata(0));
  }


  public function test__get_flashdata__returns_empty_array_if_row_not_found()
  {
    $row_id    = 123;
    $db_result = $this->_get_mock('db_query');
    
    $this->EE->db->returns('get_where', $db_result);
    $db_result->returns('row_array', array());
  
    // Run the tests.
    $this->assertIdentical(array(), $this->_subject->get_flashdata($row_id));
  }
  
  
  public function test__install_extension__installs_extension_hooks()
  {
    $hooks    = array('hook_a', 'hook_b', 'hook_c');
    $version  = '1.2.3';

    $this->EE->db->expectCallCount('insert', count($hooks));

    $default_insert_data = array(
      'class'     => $this->_extension_class,
      'enabled'   => 'y',
      'hook'      => '',
      'method'    => '',
      'priority'  => '5',
      'settings'  => '',
      'version'   => $version
    );

    for ($count = 0, $length = count($hooks); $count < $length; $count++)
    {
      $insert_data = array_merge($default_insert_data,
        array('hook' => $hooks[$count], 'method' => 'on_' .$hooks[$count]));

      $this->EE->db->expectAt($count, 'insert',
        array('extensions', $insert_data));
    }

    $this->_subject->install_extension($version, $hooks);
  }


  public function test__install_extension__does_nothing_with_invalid_data()
  {
    $hooks    = array('hook_a', 'hook_b', 'hook_c');
    $version  = '1.2.3';

    $this->EE->db->expectNever('insert');

    // Missing data.
    $this->_subject->install_extension('', $hooks);
    $this->_subject->install_extension($version, array());

    // Invalid data.
    $this->_subject->install_extension(new StdClass(), $hooks);
  }


  public function test__install_extension__creates_flashdata_database_table()
  {
    $hooks   = array('hook_a');
    $version = '1.2.3';

    $fields = array(
      'fv_id' => array(
        'auto_increment' => TRUE,
        'constraint'     => 10,
        'type'           => 'INT',
        'unsigned'       => TRUE
      ),
      'timestamp' => array(
        'constraint' => 10,
        'type'       => 'INT',
        'unsigned'   => TRUE
      ),
      'post_data' => array(
        'type' => 'TEXT'
      )
    );

    $this->EE->load->expectOnce('dbforge');
    $this->EE->dbforge->expectOnce('add_field', array($fields));
    $this->EE->dbforge->expectOnce('add_key', array('fv_id', TRUE));
    $this->EE->dbforge->expectOnce('create_table', array('freeform_values_flashdata', TRUE));

    $this->_subject->install_extension($version, $hooks);
  }


  public function test__save_flashdata__converts_array_to_json()
  {
    $data = array(
      'location' => 'Barstow',
      'region'   => 'Edge of the desert',
      'event'    => 'Drugs began to take hold'
    );

    $insert_data = array(
      'timestamp' => time(),
      'post_data' => json_encode($data)
    );

    $row_id = 911;

    $this->EE->db->expectOnce('insert',
      array('freeform_values_flashdata', $insert_data));

    $this->EE->db->expectOnce('insert_id');
    $this->EE->db->returns('insert_id', $row_id);
  
    // Run the tests.
    $this->assertIdentical($row_id, $this->_subject->save_flashdata($data));
  }
  


  public function test__uninstall_extension__deletes_extension_from_database()
  {
    $this->EE->db->expectOnce('delete',
      array('extensions', array('class' => $this->_extension_class)));

    $this->_subject->uninstall_extension();
  }


  public function test__uninstall_extension__drops_flashdata_database_table()
  {
    $this->EE->load->expectOnce('dbforge');
    $this->EE->dbforge->expectOnce('drop_table', array('freeform_values_flashdata'));

    $this->_subject->uninstall_extension();
  }


}


/* End of file      : test.freeform_values_model.php */
/* File location    : third_party/freeform_values/tests/test.freeform_values_model.php */
