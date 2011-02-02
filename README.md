## Table Creation

### Basic usage

	$table = Witty::instance('Table');

	$table->set_heading('Name', 'Color', 'Size');
	$table->add_row('Fred', 'Blue', 'Small');
	$table->add_row('Mary', 'Red', 'Large');
	$table->add_row('John', 'Green', 'Medium');

	echo $table->generate();

detail: http://codeigniter.com/user_guide/libraries/table.html
