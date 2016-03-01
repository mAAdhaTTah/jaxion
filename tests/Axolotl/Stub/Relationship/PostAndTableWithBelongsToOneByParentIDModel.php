<?php
namespace Intraxia\Jaxion\Test\Axolotl\Stub\Relationship;

use Intraxia\Jaxion\Contract\Axolotl\HasEagerRelationships;
use Intraxia\Jaxion\Test\Axolotl\Stub\PostAndMetaModel;

class PostAndTableWithBelongsToOneByParentIDModel
	extends PostAndMetaModel
	implements HasEagerRelationships
{
	public static function get_eager_relationships() {
		return array( 'parent' );
	}

	public function related_parent() {
		return $this->belongs_to_one( 'Intraxia\Jaxion\Test\Axolotl\Stub\Relationship\PostAndTableWithHasManyByParentIDModel', 'object', 'post_parent' );
	}
}
