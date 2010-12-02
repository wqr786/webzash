<?php

class Manage extends Controller {

	function Manage()
	{
		parent::Controller();
		return;
	}
	
	function index()
	{
		$this->load->helper('file');
		$this->template->set('page_title', 'Manage webzash accounts');
		$this->template->set('nav_links', array('admin/manage/add' => 'New account'));

		$active_accounts = read_file('system/application/controllers/admin/activeaccount.inc');
		$data['accounts'] = explode(';', $active_accounts);
		if (count($data['accounts']) > 1)
			array_pop($data['accounts']);

		$this->template->load('admin_template', 'admin/manage/index', $data);
		return;
	}

	function add()
	{
		$this->template->set('page_title', 'Add a webzash account');

		/* Form fields */
		$data['database_label'] = array(
			'name' => 'database_label',
			'id' => 'database_label',
			'maxlength' => '30',
			'size' => '30',
			'value' => '',
		);

		$data['database_name'] = array(
			'name' => 'database_name',
			'id' => 'database_name',
			'maxlength' => '100',
			'size' => '40',
			'value' => '',
		);

		$data['database_username'] = array(
			'name' => 'database_username',
			'id' => 'database_username',
			'maxlength' => '100',
			'size' => '40',
			'value' => '',
		);

		$data['database_password'] = array(
			'name' => 'database_password',
			'id' => 'database_password',
			'maxlength' => '100',
			'size' => '40',
			'value' => '',
		);

		$data['database_host'] = array(
			'name' => 'database_host',
			'id' => 'database_host',
			'maxlength' => '100',
			'size' => '40',
			'value' => 'localhost',
		);

		$data['database_port'] = array(
			'name' => 'database_port',
			'id' => 'database_port',
			'maxlength' => '100',
			'size' => '40',
			'value' => '3306',
		);

		/* Repopulating form */
		if ($_POST)
		{
			$data['database_label']['value'] = $this->input->post('database_label', TRUE);
			$data['database_name']['value'] = $this->input->post('database_name', TRUE);
			$data['database_username']['value'] = $this->input->post('database_username', TRUE);
			$data['database_password']['value'] = $this->input->post('database_password', TRUE);
			$data['database_host']['value'] = $this->input->post('database_host', TRUE);
			$data['database_port']['value'] = $this->input->post('database_port', TRUE);
		}

		/* Form validations */
		$this->form_validation->set_rules('database_label', 'Label', 'trim|required|min_length[2]|max_length[30]|alpha_numeric');
		$this->form_validation->set_rules('database_name', 'Database Name', 'trim|required');

		/* Validating form */
		if ($this->form_validation->run() == FALSE)
		{
			$this->messages->add(validation_errors(), 'error');
			$this->template->load('admin_template', 'admin/manage/add', $data);
			return;
		}
		else
		{
			$data_database_label = $this->input->post('database_label', TRUE);
			$data_database_host = $this->input->post('database_host', TRUE);
			$data_database_port = $this->input->post('database_port', TRUE);
			$data_database_name = $this->input->post('database_name', TRUE);
			$data_database_username = $this->input->post('database_username', TRUE);
			$data_database_password = $this->input->post('database_password', TRUE);

			$ini_file = "system/application/config/accounts/" . $data_database_label . ".ini";

			/* Check if database ini file exists */
			if (get_file_info($ini_file))
			{
				$this->messages->add("Account with same label already exists", 'error');
				$this->template->load('admin_template', 'admin/manage/add', $data);
				return;
			}

			$con_details = '[database]\ndb_hostname = "' . $data_database_host . '"\ndb_port = "' . $data_database_port . '"\ndb_name = "' . $data_database_name . '"\ndb_username = "' . $data_database_username . '"\ndb_password = "' . $data_database_password . '"\n';

			$con_details_html = '[database]<br />db_hostname = "' . $data_database_host . '"<br />db_port = "' . $data_database_port . '"<br />db_name = "' . $data_database_name . '"<br />db_username = "' . $data_database_username . '"<br />db_password = "' . $data_database_password . '"<br />';

			/* Writing the connection string to end of file - writing in 'a' append mode */
			if ( ! write_file($ini_file, $con_details))
			{
				$this->messages->add("Failed to add account. Please check if \"" . $ini_file . "\" file is writable", 'error');
				$this->messages->add("You can manually create a text file \"" . $ini_file . "\" with the following content :<br /><br />" . $con_details_html, 'error');
				$this->template->load('admin_template', 'admin/manage/add', $data);
				return;
			} else {
				$this->messages->add("Successfully added webzash account to list of active accounts", 'success');
				redirect('admin/manage');
				return;
			}

			
		}
		return;
	}
}

/* End of file manage.php */
/* Location: ./system/application/controllers/admin/manage.php */
