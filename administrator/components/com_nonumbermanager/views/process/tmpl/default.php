<?php
/**
 * @package         NoNumber Extension Manager
 * @version         4.2.2
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2013 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

$task = JFactory::getApplication()->input->get('task');

JHtml::_('behavior.mootools');

JFactory::getApplication()->set('_messageQueue', '');

$config = JComponentHelper::getParams('com_nonumbermanager');

JHtml::stylesheet('nnframework/style.min.css', false, true);
JHtml::script('nnframework/script.min.js', false, true);

$script = "
	/* NoNumber Extension Manager variable */
	var NNEM_IDS =   [ '" . implode("', '", array_keys($this->items)) . "' ];
	var NNEM_TOKEN = '" . JSession::getFormToken() . "';
";
JFactory::getDocument()->addScriptDeclaration($script);

JHtml::stylesheet('nonumbermanager/process.min.css', false, true);
JHtml::script('nonumbermanager/process.min.js', false, true);

JFactory::getDocument()->addStyleDeclaration('html, body{ height: auto !important; overflow-y: auto !important; }');
?>

<div class="titles">
	<div class="title pre">
		<h1><?php echo JText::_('NNEM_TITLE_' . strtoupper($task)); ?>:</h1>
		<a href="javascript://" onclick="nnManagerProcess.process('<?php echo $task; ?>');"
			class="nonumbermanager_button">
			<?php echo JText::_('NN_START'); ?>
		</a>
	</div>
	<div class="title processing" style="display:none;">
		<h1><?php echo JText::sprintf('NNEM_PROCESS_' . strtoupper($task), '...'); ?></h1>
	</div>
	<div class="title done" style="display:none;">
		<?php if ($task != 'uninstall') : ?>
			<div class="nnem_message"><?php echo JText::_('NNEM_CLEAN_CACHE'); ?></div>
		<?php endif; ?>
		<h1><?php echo JText::_('NNEM_TITLE_FINISHED'); ?></h1>
	</div>
	<div class="title failed" style="display:none;">
		<h1><?php echo JText::_('NNEM_TITLE_' . strtoupper($task)); ?>:</h1>
		<a href="javascript://" onclick="nnManagerProcess.process('<?php echo $task; ?>');"
			class="nonumbermanager_button">
			<?php echo JText::_('NNEM_TITLE_RETRY'); ?>
		</a>
	</div>
</div>

<div style="clear:both;"></div>

<table class="processlist">
	<tbody>
		<?php foreach ($this->items as $item) : ?>
			<tr id="row_<?php echo $item->id; ?>">
				<td width="1%" nowrap="nowrap" class="ext_name">
					<span class="icon-nonumber icon-<?php echo $item->id; ?>"></span>
					<?php echo JText::_($item->name); ?>
				</td>
				<td class="statuses">
					<input type="hidden" id="url_<?php echo $item->id; ?>" value="<?php echo $item->url; ?>" />

					<div id="queue_<?php echo $item->id; ?>" class="status queued">
						<span><?php echo JText::_('NNEM_QUEUED'); ?></span>
					</div>
					<div id="processing_<?php echo $item->id; ?>" class="status processing" style="display:none;">
						<span>
							<progress><?php echo JText::sprintf('NNEM_PROCESS_' . strtoupper($task), '...'); ?></progress>
						</span>
					</div>

					<div id="success_<?php echo $item->id; ?>" class="status success" style="display:none;">
						<span><?php echo JText::_(($task == 'uninstall') ? 'NNEM_UNINSTALLED' : 'NNEM_INSTALLED'); ?></span>
					</div>
					<div id="failed_<?php echo $item->id; ?>" class="status failed" style="display:none;">
						<span><?php echo JText::_('NNEM_INSTALLATION_FAILED'); ?></span>
					</div>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
