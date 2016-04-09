<?php
namespace Intraxia\Jaxion\Contract\Axolotl;

interface UsesCustomTable {
	/**
	 * Returns the custom table name used by the model.
	 *
	 * @return string
	 */
	public static function get_table_name();
}
