<?php
// Wright v.3 Override: Joomla 2.5.14
/**
 * @package		Joomla.Site
 * @subpackage	com_newsfeeds
 * @copyright	Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');

?>
<div class="categories-list<?php echo $this->pageclass_sfx;?>">
<?php if ($this->params->get('show_page_heading')) : ?>
<div class="page-header">  <?php // Wright v.3: Added page header ?>
	<h1>
		<?php echo $this->escape($this->params->get('page_heading')); ?>
	</h1>
</div>  <?php // Wright v.3: Added page header ?>
<?php endif; ?>

	<?php if ($this->params->get('show_base_description')) : ?>
	<?php 	//If there is a description in the menu parameters use that; ?>
	       		<?php if($this->params->get('categories_description')) : ?>
		 <div class="category-desc base-desc">
			<?php echo  JHtml::_('content.prepare', $this->params->get('categories_description'), '', 'com_newsfeeds.categories'); ?>
			</div>
		<?php  else: ?>
			<?php //Otherwise get one from the database if it exists. ?>
			<?php  if ($this->parent->description) : ?>
				<div class="category-desc  base-desc">
					<?php  echo JHtml::_('content.prepare', $this->parent->description, '', 'com_newsfeeds.categories'); ?>
				</div>
			<?php  endif; ?>
		<?php  endif; ?>
	<?php endif; ?>
<?php
echo $this->loadTemplate('items');
?>
</div>
