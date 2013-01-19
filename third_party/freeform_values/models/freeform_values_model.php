<?php if ( ! defined('BASEPATH')) exit('Direct script access not allowed');

/**
 * Freeform Values 'Package' model.
 *
 * @author          Stephen Lewis (http://github.com/experience/)
 * @copyright       Experience Internet
 * @package         Freeform_values
 * @version         0.1.0
 */

class Freeform_values_model extends CI_Model {

  protected $EE;
  protected $_namespace;
  protected $_package_name;
  protected $_package_title;
  protected $_package_version;
  protected $_sanitized_extension_class;
  protected $_site_id;


  /* --------------------------------------------------------------
   * PUBLIC METHODS
   * ------------------------------------------------------------ */

  /**
   * Constructor.
   *
   * @access  public
   * @param   string    $package_name       Package name. Used for testing.
   * @param   string    $package_title      Package title. Used for testing.
   * @param   string    $package_version    Package version. Used for testing.
   * @param   string    $namespace          Session namespace. Used for testing.
   * @return  void
   */
  public function __construct($package_name = '', $package_title = '',
    $package_version = '', $namespace = ''
  )
  {
    parent::__construct();

    $this->EE =& get_instance();

    // Load the number helper.
    $this->EE->load->helper('EI_number_helper');

    // Load the OmniLogger class.
    if (file_exists(PATH_THIRD .'omnilog/classes/omnilogger.php'))
    {
      include_once PATH_THIRD .'omnilog/classes/omnilogger.php';
    }

    $this->_namespace = $namespace ? strtolower($namespace) : 'experience';
    $this->_package_name = $package_name ? $package_name : 'Freeform_values';
    $this->_package_title = $package_title ? $package_title : 'Freeform Values';
    $this->_package_version = $package_version ? $package_version : '0.1.0';

    // ExpressionEngine is very picky about capitalisation.
    $this->_sanitized_extension_class
      = ucfirst(strtolower($this->_package_name)) .'_ext';

    // Initialise the add-on cache.
    if ( ! array_key_exists($this->_namespace, $this->EE->session->cache))
    {
      $this->EE->session->cache[$this->_namespace] = array();
    }

    if ( ! array_key_exists($this->_package_name,
      $this->EE->session->cache[$this->_namespace]))
    {
      $this->EE->session->cache[$this->_namespace]
        [$this->_package_name] = array();
    }
  }



  /* --------------------------------------------------------------
   * PUBLIC PACKAGE METHODS
   * ------------------------------------------------------------ */
  
  /**
   * Returns the package name.
   *
   * @access  public
   * @return  string
   */
  public function get_package_name()
  {
    return $this->_package_name;
  }


  /**
   * Returns the package theme URL.
   *
   * @access  public
   * @return  string
   */
  public function get_package_theme_url()
  {
    // Much easier as of EE 2.4.0.
    if (defined('URL_THIRD_THEMES'))
    {
      return URL_THIRD_THEMES .$this->get_package_name() .'/';
    }

    return $this->EE->config->slash_item('theme_folder_url')
      .'third_party/' .$this->get_package_name() .'/';
  }


  /**
   * Returns the package title.
   *
   * @access  public
   * @return  string
   */
  public function get_package_title()
  {
    return $this->_package_title;
  }


  /**
   * Returns the package version.
   *
   * @access  public
   * @return  string
   */
  public function get_package_version()
  {
    return $this->_package_version;
  }


  /**
   * Returns the site ID.
   *
   * @access  public
   * @return  int
   */
  public function get_site_id()
  {
    if ( ! $this->_site_id)
    {
      $this->_site_id = (int) $this->EE->config->item('site_id');
    }

    return $this->_site_id;
  }


  /**
   * Logs a message to OmniLog.
   *
   * @access  public
   * @param   string      $message        The log entry message.
   * @param   int         $severity       The log entry 'level'.
   * @return  void
   */
  public function log_message($message, $severity = 1)
  {
    if (class_exists('Omnilog_entry') && class_exists('Omnilogger'))
    {
      switch ($severity)
      {
        case 3:
          $notify = TRUE;
          $type   = Omnilog_entry::ERROR;
          break;

        case 2:
          $notify = FALSE;
          $type   = Omnilog_entry::WARNING;
          break;

        case 1:
        default:
          $notify = FALSE;
          $type   = Omnilog_entry::NOTICE;
          break;
      }

      $omnilog_entry = new Omnilog_entry(array(
        'addon_name'    => 'Freeform_values',
        'date'          => time(),
        'message'       => $message,
        'notify_admin'  => $notify,
        'type'          => $type
      ));

      Omnilogger::log($omnilog_entry);
    }
  }


  /**
   * Updates a 'base' array with data contained in an 'update' array. Both
   * arrays are assumed to be associative.
   *
   * - Elements that exist in both the base array and the update array are
   *   updated to use the 'update' data.
   * - Elements that exist in the update array but not the base array are
   *   ignored.
   * - Elements that exist in the base array but not the update array are
   *   preserved.
   *
   * @access public
   * @param  array  $base   The 'base' array.
   * @param  array  $update The 'update' array.
   * @return array
   */
  public function update_array_from_input(Array $base, Array $update)
  {
    return array_merge($base, array_intersect_key($update, $base));
  }


  /**
   * Updates the package. Called from the 'update' methods of any package 
   * add-ons (module, extension, etc.), to ensure that everything gets updated 
   * at the same time.
   *
   * @access  public
   * @param   string    $installed_version    The installed version.
   * @return  bool
   */
  public function update_package($installed_version = '')
  {
    // Can't do anything without valid data.
    if ( ! is_string($installed_version) OR $installed_version == '')
    {
      return FALSE;
    }

    $package_version = $this->get_package_version();

    // Up to date?
    if (version_compare($installed_version, $package_version, '>='))
    {
      return FALSE;
    }

    // Update the extension version number in the database.
    $this->EE->db->update('extensions', array('version' => $package_version),
      array('class' => $this->get_sanitized_extension_class()));

    return TRUE;
  }


  /* --------------------------------------------------------------
   * PUBLIC EXTENSION METHODS
   * ------------------------------------------------------------ */

  /**
   * Deletes the 'flashdata' with the given ID.
   *
   * @access  public
   * @param   int|string    $id     The row ID.
   * @return  bool
   */
  public function delete_flashdata($id)
  {
    
  }


  /**
   * Retrieves the 'flashdata' with the given ID.
   *
   * @access  public
   * @param   int|string    $id     The row ID.
   * @return  array
   */
  public function get_flashdata($id)
  {
    if ( ! valid_int($id, 1))
    {
      return array();
    }

    $db_result = $this->EE->db
      ->select('post_data')
      ->get_where(array('fv_id' => $id), 1);

    return ($db_row = $db_result->row_array())
      ? json_decode($db_row['post_data'], TRUE)
      : array();
  }


  /**
   * Returns the correctly-capitalised 'extension' class.
   *
   * @access  public
   * @return  string
   */
  public function get_sanitized_extension_class()
  {
    return $this->_sanitized_extension_class;
  }


  /**
   * Installs the extension.
   *
   * @access  public
   * @param   string    $version    The extension version.
   * @param   array     $hooks      The extension hooks.
   * @return  void
   */
  public function install_extension($version, Array $hooks)
  {
    // Guard against nonsense.
    if ( ! is_string($version) OR $version == '' OR ! $hooks)
    {
      return;
    }

    $class = $this->get_sanitized_extension_class();

    $default_hook_data = array(
      'class'     => $class,
      'enabled'   => 'y',
      'hook'      => '',
      'method'    => '',
      'priority'  => '5',
      'settings'  => '',
      'version'   => $version
    );

    // Register the hooks.
    foreach ($hooks AS $hook)
    {
      if ( ! is_string($hook) OR $hook == '')
      {
        continue;
      }

      $this->EE->db->insert('extensions', array_merge(
        $default_hook_data, array('hook' => $hook, 'method' => 'on_' .$hook)));
    }

    // Create the database table.
    $this->EE->load->dbforge();

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

    $this->EE->dbforge->add_field($fields);
    $this->EE->dbforge->add_key('fv_id', TRUE);
    $this->EE->dbforge->create_table('freeform_values_flashdata', TRUE);
  }


  /**
   * Saves the given 'flashdata' to the database, and returns the row ID.
   *
   * @access  public
   * @param   array   $data  The 'flashdata'.
   * @return  int
   */
  public function save_flashdata(Array $data)
  {
    $insert_data = array(
      'timestamp' => time(),
      'post_data' => json_encode($data)
    );

    /**
     * We assume the insert works. Not the end of the world if it doesn't, and 
     * we have no fallback position anyway.
     */

    $this->EE->db->insert('freeform_values_flashdata', $insert_data);
    return $this->EE->db->insert_id();
  }


  /**
   * Uninstalls the extension.
   *
   * @access    public
   * @return    void
   */
  public function uninstall_extension()
  {
    // Delete the hooks.
    $this->EE->db->delete('extensions',
      array('class' => $this->get_sanitized_extension_class()));

    // Drop the database table.
    $this->EE->load->dbforge();
    $this->EE->dbforge->drop_table('freeform_values_flashdata');
  }



  /* --------------------------------------------------------------
   * PROTECTED PACKAGE METHODS
   * ------------------------------------------------------------ */

  /**
   * Returns a references to the package cache. Should be called
   * as follows: $cache =& $this->_get_package_cache();
   *
   * @access  protected
   * @return  array
   */
  protected function &_get_package_cache()
  {
    return $this->EE->session->cache[$this->_namespace][$this->_package_name];
  }


}


/* End of file      : freeform_values_model.php */
/* File location    : third_party/freeform_values/models/freeform_values_model.php */
