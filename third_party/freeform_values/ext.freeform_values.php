<?php if ( ! defined('BASEPATH')) exit('Direct script access not allowed');

/**
 * Freeform Values extension.
 *
 * @author          Stephen Lewis (http://github.com/experience/)
 * @copyright       Experience Internet
 * @package         Freeform_values
 */

class Freeform_values_ext {

  private $EE;
  private $_model;

  public $description;
  public $docs_url;
  public $name;
  public $settings;
  public $settings_exist;
  public $version;


  /* --------------------------------------------------------------
   * PUBLIC METHODS
   * ------------------------------------------------------------ */

  /**
   * Constructor.
   *
   * @access  public
   * @param   mixed     $settings     Extension settings.
   * @return  void
   */
  public function __construct($settings = '')
  {
    $this->EE =& get_instance();

    $this->EE->load->add_package_path(PATH_THIRD .'freeform_values/');

    // Still need to specify the package...
    $this->EE->lang->loadfile('freeform_values_ext', 'freeform_values');

    $this->EE->load->model('freeform_values_model');
    $this->_model = $this->EE->freeform_values_model;

    // Set the public properties.
    $this->description = $this->EE->lang->line(
      'freeform_values_extension_description');

    $this->docs_url = 'https://github.com/experience';
    $this->name     = $this->EE->lang->line('freeform_values_extension_name');
    $this->settings = $settings;
    $this->settings_exist = 'n';
    $this->version  = $this->_model->get_package_version();
  }


  /**
   * Activates the extension.
   *
   * @access  public
   * @return  void
   */
  public function activate_extension()
  {
    $hooks = array(
      'freeform_module_insert_end',
      'freeform_module_pre_form_parse',
      'freeform_module_validate_end'
    );

    $this->_model->install_extension($this->version, $hooks);
  }


  /**
   * Disables the extension.
   *
   * @access  public
   * @return  void
   */
  public function disable_extension()
  {
    $this->_model->uninstall_extension();
  }


  /**
   * Handles the freeform_module_insert_end extension hook. Deletes the 
   * 'flashdata' row from the database, as it's no longer required.
   *
   * @access  public
   * @param   array   $field_data   The field data.
   * @param   int     $entry_id     The entry ID.
   * @param   int     $form_id      The form ID.
   * @param   object  $freeform     The Freeform instance.
   * @return  void
   */
  public function on_freeform_module_insert_end(Array $field_data, $entry_id,
    $form_id, $freeform
  )
  {
    $this->_model->delete_flashdata(
      $this->EE->session->flashdata('freeform_values_flashdata_id'));
  }


  /**
   * Handles the freeform_module_pre_form_parse extension hook.
   *
   * @access  public
   * @param   string  $tagdata    The form tagdata.
   * @param   object  $freeform   The Freeform instance.
   * @return  string
   */
  public function on_freeform_module_pre_form_parse($tagdata, $freeform)
  {
    if (($last_call = $this->EE->extensions->last_call) !== FALSE)
    {
      $tagdata = $last_call;
    }

    // Retrieve the previous POST data.
    $post_data = $this->_model->get_and_delete_flashdata(
      $this->EE->session->flashdata('freeform_values_flashdata_id'));

    // If there is no POST data, we're done.
    if ( ! $post_data)
    {
      return $tagdata;
    }

    // Retrieve the field names. Every field has a label, so we look for that.
    $pattern = 'freeform:label:';
    $pattern_length = strlen($pattern);

    foreach ($freeform->variables AS $key => $value)
    {
      if ( ! strstr($key, $pattern))
      {
        continue;
      }

      $field_name = substr($key, $pattern_length);

      $freeform->variables['freeform:value:' .$field_name]
        = array_key_exists($field_name, $post_data)
          ? $post_data[$field_name] : '';
    }

    return $tagdata;
  }


  /**
   * Handles the freeform_module_validate_end extension hook.
   *
   * @access  public
   * @param   array   $errors     Errors array.
   * @param   object  $freeform   The Freeform instance.
   * @return  void
   */
  public function on_freeform_module_validate_end(Array $errors, $freeform)
  {
    if (($last_call = $this->EE->extensions->last_call) !== FALSE)
    {
      $errors = $last_call;
    }

    $post_values = array();

    foreach ($_POST AS $key => $value)
    {
      $post_values[$key] = $this->EE->input->post($key, TRUE);    // Sanitize.
    }

    /**
     * TRICKY:
     * If there are any problems, Freeform redirects us to an error page 
     * (usually the same as the form page). We keep track of the submitted form 
     * data by storing it in the database, and saving the row ID in the Session 
     * flashdata.
     */

    $this->EE->session->set_flashdata('freeform_values_flashdata_id',
      $this->_model->save_flashdata($post_values));

    // Don't forget to return the errors.
    return $errors;
  }


  /**
   * Updates the extension.
   *
   * @access  public
   * @param   string    $installed_version    The installed version.
   * @return  mixed
   */
  public function update_extension($installed_version = '')
  {
    return $this->_model->update_package($installed_version);
  }


}


/* End of file      : ext.freeform_values.php */
/* File location    : third_party/freeform_values/ext.freeform_values.php */
