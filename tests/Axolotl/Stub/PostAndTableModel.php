<?php
/**
 * Created by PhpStorm.
 * User: jdigioia
 * Date: 2/28/16
 * Time: 1:22 PM
 */

namespace Intraxia\Jaxion\Test\Axolotl\Stub;

use Intraxia\Jaxion\Contract\Axolotl\UsesCustomTable;

class PostAndTableModel extends PostAndMetaModel implements UsesCustomTable {
	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public static function get_table_name() {
		return 'custom';
	}
}
