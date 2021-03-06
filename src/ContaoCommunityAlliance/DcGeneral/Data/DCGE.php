<?php
/**
 * PHP version 5
 * @package    generalDriver
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace ContaoCommunityAlliance\DcGeneral\Data;

/**
 * This class is a holder for all const vars.
 */
class DCGE
{
	/**
	 * Single language
	 */
	const LANGUAGE_SL = 1;

	/**
	 * Multi language
	 */
	const LANGUAGE_ML = 2;

	/**
	 * Move/Insert after Start
	 */
	const INSERT_AFTER_START = 'start';

	/**
	 * Move/Insert after End
	 */
	const INSERT_AFTER_END = 'end';

	/**
	 * Move/Insert into root
	 */
	const INSERT_INTO_ROOT = 'root';

	/**
	 * DataProvider sorting order asc
	 */
	const MODEL_SORTING_ASC = 'ASC';

	/**
	 * DataProvider sorting order desc
	 */
	const MODEL_SORTING_DESC = 'DESC';

	/**
	 * Sorting unsorted.
	 */
	const MODE_NON_SORTING = 0;

	/**
	 * Sorting by a fixed field.
	 */
	const MODE_FIXED_FIELD = 1;

	/**
	 * Sorting by a variable field.
	 */
	const MODE_VARIABLE_FIELD = 2;

	/**
	 * Sorting by the parent record.
	 */
	const MODE_PARENT_VIEW = 3;

	// TODO: SH: CS: mode 4 missing, no idea for a good name.
	/**
	 * Sorting as a simple tree.
	 */
	const MODE_SIMPLE_TREEVIEW = 5;

	/**
	 * Sorting as a parented tree.
	 */
	const MODE_PARENT_TREEVIEW = 6;

	/**
	 * Title of an item in a tree view.
	 */
	const TREE_VIEW_TITLE = 'dc_gen_tv_title';

	/**
	 * The current level in a tree view.
	 */
	const TREE_VIEW_LEVEL = 'dc_gen_tv_level';

	/**
	 * Is the tree item open.
	 */
	const TREE_VIEW_IS_OPEN = 'dc_gen_tv_open';

	/**
	 * Child Collection.
	 */
	const TREE_VIEW_CHILD_COLLECTION = 'dc_gen_children_collection';

	/**
	 * @deprecated Use ModelInterface::HAS_CHILDREN
	 * @see ModelInterface::HAS_CHILDREN
	 */
	const TREE_VIEW_HAS_CHILDS = ModelInterface::HAS_CHILDREN;

	/**
	 * @deprecated Use ModelInterface::OPERATION_BUTTONS
	 * @see ModelInterface::OPERATION_BUTTONS
	 */
	const MODEL_BUTTONS = ModelInterface::OPERATION_BUTTONS;

	/**
	 * @deprecated Use ModelInterface::LABEL_ARGS
	 * @see ModelInterface::LABEL_ARGS
	 */
	const MODEL_LABEL_ARGS = ModelInterface::LABEL_ARGS;

	/**
	 * @deprecated Use ModelInterface::LABEL_VALUE
	 * @see ModelInterface::LABEL_VALUE
	 */
	const MODEL_LABEL_VALUE = ModelInterface::LABEL_VALUE;

	/**
	 * @deprecated Use ModelInterface::GROUP_HEADER
	 * @see ModelInterface::GROUP_HEADER
	 */
	const MODEL_GROUP_HEADER = ModelInterface::GROUP_HEADER;

	/**
	 * @deprecated Use ModelInterface::GROUP_VALUE
	 * @see ModelInterface::GROUP_VALUE
	 */
	const MODEL_GROUP_VALUE = ModelInterface::GROUP_VALUE;

	/**
	 * @deprecated Use ModelInterface::CSS_CLASS
	 * @see ModelInterface::CSS_CLASS
	 */
	const MODEL_CLASS = ModelInterface::CSS_CLASS;

	/**
	 * @deprecated Use ModelInterface::IS_CHANGED
	 * @see ModelInterface::IS_CHANGED
	 */
	const MODEL_IS_CHANGED = ModelInterface::IS_CHANGED;

	/**
	 * @deprecated Use ModelInterface::CSS_ROW_CLASS
	 * @see ModelInterface::CSS_ROW_CLASS
	 */
	const MODEL_EVEN_ODD_CLASS = ModelInterface::CSS_ROW_CLASS;

	/**
	 * @deprecated Use ModelInterface::PARENT_ID
	 * @see ModelInterface::PARENT_ID
	 */
	const MODEL_PID = ModelInterface::PARENT_ID;

	/**
	 * @deprecated Use ModelInterface::PARENT_PROVIDER_NAME
	 * @see ModelInterface::PARENT_PROVIDER_NAME
	 */
	const MODEL_PTABLE = ModelInterface::PARENT_PROVIDER_NAME;
}
