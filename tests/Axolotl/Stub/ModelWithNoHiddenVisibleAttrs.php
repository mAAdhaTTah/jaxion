<?php
namespace Intraxia\Jaxion\Test\Axolotl\Stub;

class ModelWithNoHiddenVisibleAttrs extends PostAndMetaModel {
	protected $hidden = array();

	protected $visible = array();
}
