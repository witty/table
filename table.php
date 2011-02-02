<?php
/**
 * Table Util
 *
 * @author ExpressionEngine-Dev-Team http://codeigniter.com
 * @homepage https://github.com/witty/table
 * @version 0.1.0
 */
class Table extends Witty_Base {

	protected $_rows = array();
	protected $_heading = array();
	protected $_auto_heading = TRUE;
	protected $_caption = NULL;
	protected $_template = NULL;
	protected $_newline = "\n";
	protected $_empty_cells = "";
	protected $_function = FALSE;

	/**
	 * Set the template
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	public function set_template($template)
	{
		if (!is_array($template))
		{
			return FALSE;
		}

		$this->_template = $template;
	}

	/**
	 * Set the table heading
	 *
	 * Can be passed as an array or discreet params
	 *
	 * @access	public
	 * @param	mixed
	 * @return	void
	 */
	public function set_heading()
	{
		$args = func_get_args();
		$this->_heading = $this->_prep_args($args);
	}

	/**
	 * Set columns.  Takes a one-dimensional array as input and creates
	 * a multi-dimensional array with a depth equal to the number of
	 * columns.  This allows a single array with many elements to  be
	 * displayed in a table that has a fixed column count.
	 *
	 * @access	public
	 * @param	array
	 * @param	int
	 * @return	void
	 */
	public function make_columns($array = array(), $col_limit = 0)
	{
		if (!is_array($array) || count($array) == 0)
		{
			return FALSE;
		}

		// Turn off the auto-heading feature since it's doubtful we
		// will want headings from a one-dimensional array
		$this->_auto_heading = FALSE;

		if ($col_limit == 0)
		{
			return $array;
		}

		$new = array();
		while(count($array) > 0)
		{
			$temp = array_splice($array, 0, $col_limit);

			if (count($temp) < $col_limit)
			{
				for ($i = count($temp); $i < $col_limit; $i++)
				{
					$temp[] = '&nbsp;';
				}
			}

			$new[] = $temp;
		}

		return $new;
	}

	/**
	 * Set "empty" cells
	 *
	 * Can be passed as an array or discreet params
	 *
	 * @access	public
	 * @param	mixed
	 * @return	void
	 */
	public function set_empty($value)
	{
		$this->_empty_cells = $value;
	}

	/**
	 * Add a table row
	 *
	 * Can be passed as an array or discreet params
	 *
	 * @access	public
	 * @param	mixed
	 * @return	void
	 */
	public function add_row()
	{
		$args = func_get_args();
		$this->_rows[] = $this->_prep_args($args);
	}

	/**
	 * Prep Args
	 *
	 * Ensures a standard associative array format for all cell data
	 *
	 * @access	public
	 * @param	type
	 * @return	type
	 */
	public function _prep_args($args)
	{
		// If there is no $args[0], skip this and treat as an associative array
		// This can happen if there is only a single key, for example this is passed to table->generate
		// array(array('foo'=>'bar'))
		if (isset($args[0]) AND (count($args) == 1 && is_array($args[0])))
		{
			// args sent as indexed array
			if ( ! isset($args[0]['data']))
			{
				foreach ($args[0] as $key => $val)
				{
					if (is_array($val) && isset($val['data']))
					{
						$args[$key] = $val;
					}
					else
					{
						$args[$key] = array('data' => $val);
					}
				}
			}
		}
		else
		{
			foreach ($args as $key => $val)
			{
				if ( ! is_array($val))
				{
					$args[$key] = array('data' => $val);
				}
			}
		}

		return $args;
	}

	/**
	 * Add a table caption
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function set_caption($caption)
	{
		$this->_caption = $caption;
	}

	/**
	 * Generate the table
	 *
	 * @access	public
	 * @param	mixed
	 * @return	string
	 */
	public function generate($table_data = NULL)
	{
		// The table data can optionally be passed to this function
		// either as a database result object or an array
		if ( ! is_null($table_data))
		{
			if (is_object($table_data))
			{
				$this->_set_from_object($table_data);
			}
			elseif (is_array($table_data))
			{
				$set_heading = (count($this->_heading) == 0 && $this->_auto_heading == FALSE) ? FALSE : TRUE;
				$this->_set_from_array($table_data, $set_heading);
			}
		}

		// Is there anything to display?  No?  Smite them!
		if (count($this->_heading) == 0 && count($this->_rows) == 0)
		{
			return 'Undefined table data';
		}

		// Compile and validate the template date
		$this->_compile_template();

		// set a custom cell manipulation function to a locally scoped variable so its callable
		$function = $this->_function;

		// Build the table!

		$out = $this->_template['table_open'];
		$out .= $this->_newline;

		// Add any caption here
		if ($this->_caption)
		{
			$out .= $this->_newline;
			$out .= '<caption>' . $this->_caption . '</caption>';
			$out .= $this->_newline;
		}

		// Is there a table heading to display?
		if (count($this->_heading) > 0)
		{
			$out .= $this->_template['thead_open'];
			$out .= $this->_newline;
			$out .= $this->_template['heading_row_start'];
			$out .= $this->_newline;

			foreach($this->_heading as $heading)
			{
				$temp = $this->_template['heading_cell_start'];

				foreach ($heading as $key => $val)
				{
					if ($key != 'data')
					{
						$temp = str_replace('<th', "<th $key='$val'", $temp);
					}
				}

				$out .= $temp;
				$out .= isset($heading['data']) ? $heading['data'] : '';
				$out .= $this->_template['heading_cell_end'];
			}

			$out .= $this->_template['heading_row_end'];
			$out .= $this->_newline;
			$out .= $this->_template['thead_close'];
			$out .= $this->_newline;
		}

		// Build the table rows
		if (count($this->_rows) > 0)
		{
			$out .= $this->_template['tbody_open'];
			$out .= $this->_newline;

			$i = 1;
			foreach($this->_rows as $row)
			{
				if ( ! is_array($row))
				{
					break;
				}

				// We use modulus to alternate the row colors
				$name = (fmod($i++, 2)) ? '' : 'alt_';

				$out .= $this->_template['row_'.$name.'start'];
				$out .= $this->_newline;

				foreach($row as $cell)
				{
					$temp = $this->_template['cell_'.$name.'start'];

					foreach ($cell as $key => $val)
					{
						if ($key != 'data')
						{
							$temp = str_replace('<td', "<td $key='$val'", $temp);
						}
					}

					$cell = isset($cell['data']) ? $cell['data'] : '';
					$out .= $temp;

					if ($cell === "" OR $cell === NULL)
					{
						$out .= $this->_empty_cells;
					}
					else
					{
						if ($function !== FALSE && is_callable($function))
						{
							$out .= call_user_func($function, $cell);
						}
						else
						{
							$out .= $cell;
						}
					}

					$out .= $this->_template['cell_'.$name.'end'];
				}

				$out .= $this->_template['row_'.$name.'end'];
				$out .= $this->_newline;
			}

			$out .= $this->_template['tbody_close'];
			$out .= $this->_newline;
		}

		$out .= $this->_template['table_close'];

		return $out;
	}

	/**
	 * Clears the table arrays.  Useful if multiple tables are being generated
	 *
	 * @access	public
	 * @return	void
	 */
	public function clear()
	{
		$this->_rows				= array();
		$this->_heading			= array();
		$this->_auto_heading		= TRUE;
	}

	/**
	 * Set table data from a database result object
	 *
	 * @access	public
	 * @param	object
	 * @return	void
	 */
	protected function _set_from_object($query)
	{
		if (!is_object($query))
		{
			return FALSE;
		}

		// First generate the headings from the table column names
		if (count($this->_heading) == 0)
		{
			if ( ! method_exists($query, 'list_fields'))
			{
				return FALSE;
			}

			$this->_heading = $this->_prep_args($query->list_fields());
		}

		// Next blast through the result array and build out the rows

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$this->_rows[] = $this->_prep_args($row);
			}
		}
	}

	/**
	 * Set table data from an array
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	protected function _set_from_array($data, $set_heading = TRUE)
	{
		if (!is_array($data) || count($data) == 0)
		{
			return FALSE;
		}

		$i = 0;
		foreach ($data as $row)
		{
			// If a heading hasn't already been set we'll use the first row of the array as the heading
			if ($i == 0 && count($data) > 1 && count($this->_heading) == 0 && $set_heading == TRUE)
			{
				$this->_heading = $this->_prep_args($row);
			}
			else
			{
				$this->_rows[] = $this->_prep_args($row);
			}

			$i++;
		}
	}

	/**
	 * Compile Template
	 *
	 * @access	private
	 * @return	void
	 */
	protected function _compile_template()
	{
		if ($this->_template == NULL)
		{
			$this->_template = $this->_default_template();
			return;
		}

		$this->temp = $this->_default_template();
		foreach (array('table_open', 'thead_open', 'thead_close', 'heading_row_start', 'heading_row_end', 'heading_cell_start', 'heading_cell_end', 'tbody_open', 'tbody_close', 'row_start', 'row_end', 'cell_start', 'cell_end', 'row_alt_start', 'row_alt_end', 'cell_alt_start', 'cell_alt_end', 'table_close') as $val)
		{
			if ( ! isset($this->_template[$val]))
			{
				$this->_template[$val] = $this->temp[$val];
			}
		}
	}

	/**
	 * Default Template
	 *
	 * @access	private
	 * @return	void
	 */
	protected function _default_template()
	{
		return  array (
			'table_open'			=> '<table border="0" cellpadding="4" cellspacing="0">',

			'thead_open'			=> '<thead>',
			'thead_close'			=> '</thead>',

			'heading_row_start'		=> '<tr>',
			'heading_row_end'		=> '</tr>',
			'heading_cell_start'	=> '<th>',
			'heading_cell_end'		=> '</th>',

			'tbody_open'			=> '<tbody>',
			'tbody_close'			=> '</tbody>',

			'row_start'				=> '<tr>',
			'row_end'				=> '</tr>',
			'cell_start'			=> '<td>',
			'cell_end'				=> '</td>',

			'row_alt_start'		=> '<tr>',
			'row_alt_end'			=> '</tr>',
			'cell_alt_start'		=> '<td>',
			'cell_alt_end'			=> '</td>',

			'table_close'			=> '</table>'
		);
	}

}
