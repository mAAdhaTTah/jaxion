<?php
namespace Intraxia\Jaxion\Test\Axolotl\Stub;

class ModelWithHiddenAttrs extends PostAndMetaModel {
	protected $visible = array();

	protected $hidden = array( 'ID' );
}
