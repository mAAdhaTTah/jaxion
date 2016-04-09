<?php
namespace Intraxia\Jaxion\Test\Axolotl\Stub\Relationship;

use Intraxia\Jaxion\Contract\Axolotl\HasEagerRelationships;
use Intraxia\Jaxion\Test\Axolotl\Stub\PostAndMetaModel;

class PostAndTableWithHasManyByParentIDModel
	extends PostAndMetaModel
	implements HasEagerRelationships {
	protected $visible = array(
		'title',
		'text',
		'url',
		'children',
	);

	public static function get_eager_relationships() {
		return array( 'children', 'category' );
	}

	public function related_children() {
		return $this->has_many( 'Intraxia\Jaxion\Test\Axolotl\Stub\Relationship\PostAndTableWithBelongsToOneByParentIDModel', 'object', 'post_parent' );
	}

	public function related_category() {
		return $this->has_many( 'Intraxia\Jaxion\Test\Axolotl\Stub\TaxonomyModel', 'object', 'post_id' );
	}
}
