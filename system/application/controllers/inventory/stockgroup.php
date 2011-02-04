<?php

class StockGroup extends Controller {

	function StockGroup()
	{
		parent::Controller();
		$this->load->model('Stock_Group_model');
		return;
	}

	function index()
	{
		redirect('inventory/stockgroup/add');
		return;
	}

	function add()
	{
		$this->template->set('page_title', 'New Stock Group');

		/* Check access */
		if ( ! check_access('create stock group'))
		{
			$this->messages->add('Permission denied.', 'error');
			redirect('inventory/account');
			return;
		}

		/* Check for account lock */
		if ($this->config->item('account_locked') == 1)
		{
			$this->messages->add('Account is locked.', 'error');
			redirect('account');
			return;
		}

		/* Form fields */
		$data['stock_group_name'] = array(
			'name' => 'stock_group_name',
			'id' => 'stock_group_name',
			'maxlength' => '100',
			'size' => '40',
			'value' => '',
		);
		$data['stock_group_parents'] = $this->Stock_Group_model->get_all_stock_groups();
		$data['stock_group_parent_active'] = 0;

		/* Form validations */
		$this->form_validation->set_rules('stock_group_name', 'Stock group name', 'trim|required|min_length[2]|max_length[100]|unique[stock_groups.name]');
		$this->form_validation->set_rules('stock_group_parent', 'Parent stock group', 'trim|required|is_natural');

		/* Re-populating form */
		if ($_POST)
		{
			$data['stock_group_name']['value'] = $this->input->post('stock_group_name', TRUE);
			$data['stock_group_parent_active'] = $this->input->post('stock_group_parent', TRUE);
		}

		if ($this->form_validation->run() == FALSE)
		{
			$this->messages->add(validation_errors(), 'error');
			$this->template->load('template', 'inventory/stockgroup/add', $data);
			return;
		}
		else
		{
			$data_stock_group_name = $this->input->post('stock_group_name', TRUE);
			$data_stock_group_parent_id = $this->input->post('stock_group_parent', TRUE);

			/* Check if parent group id present */
			if ($data_stock_group_parent_id > 0)
			{
				$this->db->select('id')->from('stock_groups')->where('id', $data_stock_group_parent_id);
				if ($this->db->get()->num_rows() < 1)
				{
					$this->messages->add('Invalid parent stock group.', 'error');
					$this->template->load('template', 'inventory/stockgroup/add', $data);
					return;
				}
			}

			$this->db->trans_start();
			$insert_data = array(
				'name' => $data_stock_group_name,
				'parent_id' => $data_stock_group_parent_id,
			);
			if ( ! $this->db->insert('stock_groups', $insert_data))
			{
				$this->db->trans_rollback();
				$this->messages->add('Error addding Stock Group - ' . $data_stock_group_name . '.', 'error');
				$this->logger->write_message("error", "Error adding Stock Group named " . $data_stock_group_name);
				$this->template->load('template', 'inventory/stockgroup/add', $data);
				return;
			} else {
				$this->db->trans_complete();
				$this->messages->add('Added Stock Group - ' . $data_stock_group_name . '.', 'success');
				$this->logger->write_message("success", "Added Stock Group named " . $data_stock_group_name);
				redirect('inventory/account');
				return;
			}
		}
		return;
	}

	function edit($id)
	{
		$this->template->set('page_title', 'Edit Stock Group');

		/* Check access */
		if ( ! check_access('edit stock group'))
		{
			$this->messages->add('Permission denied.', 'error');
			redirect('inventory/account');
			return;
		}

		/* Check for account lock */
		if ($this->config->item('account_locked') == 1)
		{
			$this->messages->add('Account is locked.', 'error');
			redirect('inventory/account');
			return;
		}

		/* Checking for valid data */
		$id = $this->input->xss_clean($id);
		$id = (int)$id;
		if ($id < 1) {
			$this->messages->add('Invalid Stock Group.', 'error');
			redirect('inventory/account');
			return;
		}

		/* Loading current group */
		$this->db->from('stock_groups')->where('id', $id);
		$stock_group_data_q = $this->db->get();
		if ($stock_group_data_q->num_rows() < 1)
		{
			$this->messages->add('Invalid Stock Group.', 'error');
			redirect('inventory/account');
			return;
		}
		$stock_group_data = $stock_group_data_q->row();

		/* Form fields */
		$data['stock_group_name'] = array(
			'name' => 'stock_group_name',
			'id' => 'stock_group_name',
			'maxlength' => '100',
			'size' => '40',
			'value' => $stock_group_data->name,
		);
		$data['stock_group_parents'] = $this->Stock_Group_model->get_all_stock_groups($stock_group_data->id);
		$data['stock_group_parent_active'] = $stock_group_data->parent_id;
		$data['stock_group_id'] = $id;

		/* Form validations */
		$this->form_validation->set_rules('stock_group_name', 'Stock group name', 'trim|required|min_length[2]|max_length[100]|uniquewithid[stock_groups.name.' . $id . ']');
		$this->form_validation->set_rules('stock_group_parent', 'Parent stock group', 'trim|required|is_natural');

		/* Re-populating form */
		if ($_POST)
		{
			$data['stock_group_name']['value'] = $this->input->post('stock_group_name', TRUE);
			$data['stock_group_parent_active'] = $this->input->post('stock_group_parent', TRUE);
		}

		if ($this->form_validation->run() == FALSE)
		{
			$this->messages->add(validation_errors(), 'error');
			$this->template->load('template', 'inventory/stockgroup/edit', $data);
			return;
		}
		else
		{
			$data_stock_group_name = $this->input->post('stock_group_name', TRUE);
			$data_stock_group_parent_id = $this->input->post('stock_group_parent', TRUE);
			$data_id = $id;

			/* Check if parent group id present */
			if ($data_stock_group_parent_id > 0)
			{
				$this->db->select('id')->from('stock_groups')->where('id', $data_stock_group_parent_id);
				if ($this->db->get()->num_rows() < 1)
				{
					$this->messages->add('Invalid parent stock group.', 'error');
					$this->template->load('template', 'inventory/stockgroup/edit', $data);
					return;
				}
			}

			/* Check if parent group same as current group id */
			if ($data_stock_group_parent_id > 0)
			{
				if ($data_stock_group_parent_id == $id)
				{
					$this->messages->add('Invalid Parent stock group', 'error');
					$this->template->load('template', 'inventory/stockgroup/edit', $data);
					return;
				}
			}

			$this->db->trans_start();
			$update_data = array(
				'name' => $data_stock_group_name,
				'parent_id' => $data_stock_group_parent_id,
			);
			if ( ! $this->db->where('id', $data_id)->update('stock_groups', $update_data))
			{
				$this->db->trans_rollback();
				$this->messages->add('Error updating Stock Group - ' . $data_stock_group_name . '.', 'error');
				$this->logger->write_message("error", "Error updating Stock Group named " . $data_stock_group_name . " [id:" . $data_id . "]");
				$this->template->load('template', 'inventory/stockgroup/edit', $data);
				return;
			} else {
				$this->db->trans_complete();
				$this->messages->add('Updated Stock Group - ' . $data_stock_group_name . '.', 'success');
				$this->logger->write_message("success", "Updated Stock Group named " . $data_stock_group_name . " [id:" . $data_id . "]");
				redirect('inventory/account');
				return;
			}
		}
		return;
	}

	function delete($id)
	{
		/* Check access */
		if ( ! check_access('delete stock group'))
		{
			$this->messages->add('Permission denied.', 'error');
			redirect('inventory/account');
			return;
		}

		/* Check for account lock */
		if ($this->config->item('account_locked') == 1)
		{
			$this->messages->add('Account is locked.', 'error');
			redirect('inventory/account');
			return;
		}

		/* Checking for valid data */
		$id = $this->input->xss_clean($id);
		$id = (int)$id;
		if ($id < 1) {
			$this->messages->add('Invalid Stock Group.', 'error');
			redirect('inventory/account');
			return;
		}

		$this->db->from('stock_groups')->where('parent_id', $id);
		if ($this->db->get()->num_rows() > 0)
		{
			$this->messages->add('Cannot delete non-empty Stock Group.', 'error');
			redirect('inventory/account');
			return;
		}
		$this->db->from('stock_items')->where('group_id', $id);
		if ($this->db->get()->num_rows() > 0)
		{
			$this->messages->add('Cannot delete non-empty Stock Group.', 'error');
			redirect('inventory/account');
			return;
		}

		/* Get the group details */
		$this->db->from('stock_groups')->where('id', $id);
		$stock_group_q = $this->db->get();
		if ($stock_group_q->num_rows() < 1)
		{
			$this->messages->add('Invalid Stock Group.', 'error');
			redirect('inventory/account');
			return;
		} else {
			$stock_group_data = $stock_group_q->row();
		}

		/* Deleting group */
		$this->db->trans_start();
		if ( ! $this->db->delete('stock_groups', array('id' => $id)))
		{
			$this->db->trans_rollback();
			$this->messages->add('Error deleting Stock Group - ' . $stock_group_data->name . '.', 'error');
			$this->logger->write_message("error", "Error deleting Stock Group named " . $stock_group_data->name . " [id:" . $id . "]");
			redirect('inventory/account');
			return;
		} else {
			$this->db->trans_complete();
			$this->messages->add('Deleted Stock Group - ' . $stock_group_data->name . '.', 'success');
			$this->logger->write_message("success", "Deleted Stock Group named " . $stock_group_data->name . " [id:" . $id . "]");
			redirect('inventory/account');
			return;
		}
		return;
	}
}

/* End of file stockgroup.php */
/* Location: ./system/application/controllers/inventory/stockgroup.php */