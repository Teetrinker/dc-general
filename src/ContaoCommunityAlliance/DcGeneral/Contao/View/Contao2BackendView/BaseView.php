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

namespace ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView;

use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Backend\AddToUrlEvent;
use ContaoCommunityAlliance\Contao\Bindings\Events\Backend\GetThemeEvent;
use ContaoCommunityAlliance\Contao\Bindings\Events\Controller\RedirectEvent;
use ContaoCommunityAlliance\Contao\Bindings\Events\Controller\ReloadEvent;
use ContaoCommunityAlliance\Contao\Bindings\Events\Date\ParseDateEvent;
use ContaoCommunityAlliance\Contao\Bindings\Events\Image\GenerateHtmlEvent;
use ContaoCommunityAlliance\Contao\Bindings\Events\System\GetReferrerEvent;
use ContaoCommunityAlliance\Contao\Bindings\Events\System\LogEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\Compatibility\DcCompat;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ModelToLabelEvent;
use ContaoCommunityAlliance\DcGeneral\Controller\Ajax2X;
use ContaoCommunityAlliance\DcGeneral\Controller\Ajax3X;
use ContaoCommunityAlliance\DcGeneral\Data\CollectionInterface;
use ContaoCommunityAlliance\DcGeneral\Data\ModelInterface;
use ContaoCommunityAlliance\DcGeneral\Data\MultiLanguageDataProviderInterface;
use ContaoCommunityAlliance\DcGeneral\Data\PropertyValueBag;
use ContaoCommunityAlliance\DcGeneral\Contao\DataDefinition\Definition\Contao2BackendViewDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\ContainerInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\BasicDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\Properties\PropertyInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\View\Command;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\View\CommandInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\View\CopyCommandInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\View\CutCommandInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\View\GroupAndSortingDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\View\GroupAndSortingInformationInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\View\ListingConfigInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\View\ToggleCommandInterface;
use ContaoCommunityAlliance\DcGeneral\DcGeneralEvents;
use ContaoCommunityAlliance\DcGeneral\EnvironmentInterface;
use ContaoCommunityAlliance\DcGeneral\Event\ActionEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PostCreateModelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PostDeleteModelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PostDuplicateModelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PostPasteModelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PostPersistModelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PreCreateModelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PreDeleteModelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PreDuplicateModelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PreEditModelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PrePasteModelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PrePersistModelEvent;
use ContaoCommunityAlliance\DcGeneral\Exception\DcGeneralInvalidArgumentException;
use ContaoCommunityAlliance\DcGeneral\Exception\DcGeneralRuntimeException;
use ContaoCommunityAlliance\DcGeneral\Panel\FilterElementInterface;
use ContaoCommunityAlliance\DcGeneral\Panel\LimitElementInterface;
use ContaoCommunityAlliance\DcGeneral\Panel\PanelContainerInterface;
use ContaoCommunityAlliance\DcGeneral\Panel\PanelElementInterface;
use ContaoCommunityAlliance\DcGeneral\Panel\PanelInterface;
use ContaoCommunityAlliance\DcGeneral\Panel\SearchElementInterface;
use ContaoCommunityAlliance\DcGeneral\Panel\SortElementInterface;
use ContaoCommunityAlliance\DcGeneral\Panel\SubmitElementInterface;
use ContaoCommunityAlliance\DcGeneral\View\Event\RenderReadablePropertyValueEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetBreadcrumbEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetEditModeButtonsEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetGlobalButtonEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetGlobalButtonsEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetGroupHeaderEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetOperationButtonEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPasteButtonEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetSelectModeButtonsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class BaseView.
 *
 * This class is the base class for the different backend view mode sub classes.
 *
 * @package DcGeneral\Contao\View\Contao2BackendView
 */
class BaseView implements BackendViewInterface, EventSubscriberInterface
{
	/**
	 * The error message format string to use when a method is not implemented.
	 *
	 * @var string
	 */
	protected $notImplMsg =
		'<div style="text-align:center; font-weight:bold; padding:40px;">
		The function/view &quot;%s&quot; is not implemented.
		</div>';

	/**
	 * The attached environment.
	 *
	 * @var EnvironmentInterface
	 */
	protected $environment;

	/**
	 * The panel container in use.
	 *
	 * @var PanelContainerInterface
	 */
	protected $panel;

	/**
	 * {@inheritDoc}
	 */
	public static function getSubscribedEvents()
	{
		return array(
			DcGeneralEvents::ACTION => array('handleAction', -100),
		);
	}

	/**
	 * Handle the given action.
	 *
	 * @param ActionEvent $event The event.
	 *
	 * @return void
	 */
	// @codingStandardsIgnoreStart - Even if the cyclomatic complexity is high due to the switch, it is dead simple.
	public function handleAction(ActionEvent $event)
	{
		$GLOBALS['TL_CSS'][] = 'system/modules/dc-general/html/css/generalDriver.css';

		if ($event->getEnvironment()->getDataDefinition()->getName() !== $this->environment->getDataDefinition()->getName()
			|| $event->getResponse() !== null
		)
		{
			return;
		}

		$action = $event->getAction();
		$name   = $action->getName();

		switch ($name)
		{
			case 'copy':
			case 'copyAll':
			case 'create':
			case 'cut':
			case 'cutAll':
			case 'paste':
			case 'delete':
			case 'move':
			case 'undo':
			case 'edit':
			case 'show':
			case 'showAll':
			case 'toggle':
				$response = call_user_func_array(
					array($this, $name),
					$action->getArguments()
				);
				$event->setResponse($response);
				break;

			default:
		}

		if ($this->getViewSection()->getModelCommands()->hasCommandNamed($name))
		{
			$command = $this->getViewSection()->getModelCommands()->getCommandNamed($name);

			if ($command instanceof ToggleCommandInterface)
			{
				$this->toggle($name);
			}
		}
	}
	// @codingStandardsIgnoreEnd

	/**
	 * {@inheritDoc}
	 */
	public function setEnvironment(EnvironmentInterface $environment)
	{
		if ($this->environment)
		{
			$this->environment->getEventPropagator()->removeSubscriber($this);
		}

		$this->environment = $environment;
		$this->environment->getEventPropagator()->addSubscriber($this);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getEnvironment()
	{
		return $this->environment;
	}

	/**
	 * Retrieve the data definition from the environment.
	 *
	 * @return ContainerInterface
	 */
	protected function getDataDefinition()
	{
		return $this->getEnvironment()->getDataDefinition();
	}

	/**
	 * Translate a string via the translator.
	 *
	 * @param string      $path    The path within the translation where the string can be found.
	 *
	 * @param string|null $section The section from which the translation shall be retrieved.
	 *
	 * @return string
	 */
	protected function translate($path, $section = null)
	{
		return $this->getEnvironment()->getTranslator()->translate($path, $section);
	}

	/**
	 * Add the value to the template.
	 *
	 * @param string    $name     Name of the value.
	 *
	 * @param mixed     $value    The value to add to the template.
	 *
	 * @param \Template $template The template to add the value to.
	 *
	 * @return BaseView
	 */
	protected function addToTemplate($name, $value, $template)
	{
		$template->$name = $value;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setPanel($panelContainer)
	{
		$this->panel = $panelContainer;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPanel()
	{
		return $this->panel;
	}

	/**
	 * Retrieve the view section for this view.
	 *
	 * @return Contao2BackendViewDefinitionInterface
	 */
	protected function getViewSection()
	{
		return $this->getDataDefinition()->getDefinition(Contao2BackendViewDefinitionInterface::NAME);
	}

	/**
	 * Redirects to the real back end module.
	 *
	 * @return void
	 */
	protected function redirectHome()
	{
		$environment = $this->getEnvironment();
		$input       = $environment->getInputProvider();

		if ($input->hasParameter('table') && $input->hasParameter('pid'))
		{
			if ($input->hasParameter('pid'))
			{
				$event = new RedirectEvent(sprintf(
						'contao/main.php?do=%s&table=%s&pid=%s',
						$input->getParameter('do'),
						$input->getParameter('table'),
						$input->getParameter('pid')
				));
			}
			else
			{
				$event = new RedirectEvent(sprintf(
					'contao/main.php?do=%s&table=%s',
					$input->getParameter('do'),
					$input->getParameter('table')
				));
			}
		}
		else
		{
			$event = new RedirectEvent(sprintf(
				'contao/main.php?do=%s',
				$input->getParameter('do')
			));
		}

		$environment->getEventPropagator()->propagate(ContaoEvents::CONTROLLER_REDIRECT, $event);
	}

	/**
	 * Determines if this view is opened in a popup frame.
	 *
	 * @return bool
	 */
	protected function isPopup()
	{
		return \Input::getInstance()->get('popup');
	}

	/**
	 * Determine if the select mode is currently active or not.
	 *
	 * @return bool
	 */
	protected function isSelectModeActive()
	{
		return \Input::getInstance()->get('act') == 'select';
	}

	/**
	 * Retrieve the currently active
	 *
	 *  @return GroupAndSortingDefinitionInterface
	 */
	protected function getCurrentSorting()
	{
		$sorting = null;

		foreach ($this->getPanel() as $panel)
		{
			/** @var PanelInterface $panel */
			$sort = $panel->getElement('sort');
			if ($sort)
			{
				/** @var SortElementInterface $sort */
				return $sort->getSelectedDefinition();
			}
		}

		$definition = $this->getViewSection()->getListingConfig()->getGroupAndSortingDefinition();
		if ($definition->hasDefault())
		{
			return $definition->getDefault();
		}

		return null;
	}

	/**
	 * Retrieve the currently active grouping mode.
	 *
	 * @return array|null
	 *
	 * @see    ListingConfigInterface
	 */
	protected function getGroupingMode()
	{
		$sorting = $this->getCurrentSorting();
		// If no sorting defined, exit.
		if ((!$sorting)
			|| (!$sorting->getCount())
			|| $sorting->get(0)->getSortingMode() === GroupAndSortingInformationInterface::SORT_RANDOM)
		{
			return null;
		}
		$firstSorting = $sorting->get(0);

		// Use the information from the property, if given.
		if ($firstSorting->getGroupingMode() != '')
		{
			$groupMode   = $firstSorting->getGroupingMode();
			$groupLength = $firstSorting->getGroupingLength();
		}
		// No sorting? No grouping!
		else
		{
			$groupMode   = GroupAndSortingInformationInterface::GROUP_NONE;
			$groupLength = 0;
		}

		return array
		(
			'mode'     => $groupMode,
			'length'   => $groupLength,
			'property' => $firstSorting->getProperty(),
			'sorting'  => $sorting
		);
	}

	/**
	 * Return the formatted value for use in group headers as string.
	 *
	 * @param string         $field       The name of the property to format.
	 *
	 * @param ModelInterface $model       The model from which the value shall be taken from.
	 *
	 * @param string         $groupMode   The grouping mode in use.
	 *
	 * @param int            $groupLength The length of the value to use for grouping (only used when grouping mode is
	 *                                    ListingConfigInterface::GROUP_CHAR).
	 *
	 * @return string
	 */
	public function formatCurrentValue($field, $model, $groupMode, $groupLength)
	{
		$value      = $model->getProperty($field);
		$property   = $this->getDataDefinition()->getPropertiesDefinition()->getProperty($field);
		$propagator = $this->getEnvironment()->getEventPropagator();
		$propExtra  = $property->getExtra();

		// No property? Get out!
		if (!$property)
		{
			return '-';
		}

		$evaluation = $property->getExtra();
		$remoteNew  = '';

		if ($property->getWidgetType() == 'checkbox' && !$evaluation['multiple'])
		{
			$remoteNew = ($value != '') ? ucfirst($this->translate('MSC.yes')) : ucfirst($this->translate('MSC.no'));
		}
		// TODO: refactor foreignKey is yet undefined.
		elseif (false && $property->getForeignKey())
		{
			// TODO: case handling.
			if ($objParentModel->hasProperties())
			{
				$remoteNew = $objParentModel->getProperty('value');
			}
		}
		elseif ($groupMode != GroupAndSortingInformationInterface::GROUP_NONE)
		{
			switch ($groupMode)
			{
				case GroupAndSortingInformationInterface::GROUP_CHAR:
					$remoteNew = ($value != '') ? ucfirst(utf8_substr($value, 0, $groupLength)) : '-';
					break;

				case GroupAndSortingInformationInterface::GROUP_DAY:
					if ($value instanceof \DateTime)
					{
						$value = $value->getTimestamp();
					}

					$event = new ParseDateEvent($value, $GLOBALS['TL_CONFIG']['dateFormat']);
					$propagator->propagate(ContaoEvents::DATE_PARSE, $event);

					$remoteNew = ($value != '') ? $event->getResult() : '-';
					break;

				case GroupAndSortingInformationInterface::GROUP_MONTH:
					if ($value instanceof \DateTime)
					{
						$value = $value->getTimestamp();
					}

					$remoteNew = ($value != '') ? date('Y-m', $value) : '-';
					$intMonth  = ($value != '') ? (date('m', $value) - 1) : '-';

					if ($month = $this->translate('MONTHS' . $intMonth))
					{
						$remoteNew = ($value != '') ? $month . ' ' . date('Y', $value) : '-';
					}
					break;

				case GroupAndSortingInformationInterface::GROUP_YEAR:
					if ($value instanceof \DateTime)
					{
						$value = $value->getTimestamp();
					}

					$remoteNew = ($value != '') ? date('Y', $value) : '-';
					break;

				default:
			}
		}
		else
		{
			if ($property->getWidgetType() == 'checkbox' && !$evaluation['multiple'])
			{
				$remoteNew = ($value != '') ? $field : '';
			}
			elseif (isset($propExtra['reference']))
			{
				$remoteNew = $propExtra['reference'][$value];
			}
			elseif (array_is_assoc($property->getOptions()))
			{
				$options   = $property->getOptions();
				$remoteNew = $options[$value];
			}
			else
			{
				$remoteNew = $value;
			}

			if (is_array($remoteNew))
			{
				$remoteNew = $remoteNew[0];
			}

			if (empty($remoteNew))
			{
				$remoteNew = '-';
			}
		}

		$event = new GetGroupHeaderEvent($this->getEnvironment(), $model, $field, $remoteNew, $groupMode);

		$propagator->propagate(
			$event::NAME,
			$event,
			array($this->getEnvironment()->getDataDefinition()->getName())
		);

		$remoteNew = $event->getValue();

		return $remoteNew;
	}

	/**
	 * Get the label for a button from the translator.
	 *
	 * The fallback is as follows:
	 * 1. Try to translate the button via the data definition name as translation section.
	 * 2. Try to translate the button name with the prefix 'MSC.'.
	 * 3. Return the input value as nothing worked out.
	 *
	 * @param string $strButton The non translated label for the button.
	 *
	 * @return string
	 */
	protected function getButtonLabel($strButton)
	{
		$definition = $this->getEnvironment()->getDataDefinition();
		if (($label = $this->translate($strButton, $definition->getName())) !== $strButton)
		{
			return $label;
		}
		elseif (($label = $this->translate('MSC.' . $strButton)) !== $strButton)
		{
			return $label;
		}

		// Fallback, just return the key as is it.
		return $strButton;
	}

	/**
	 * Retrieve a list of html buttons to use in the bottom panel (submit area).
	 *
	 * @return array
	 */
	protected function getEditButtons()
	{
		$buttons         = array();
		$definition      = $this->getEnvironment()->getDataDefinition();
		$basicDefinition = $definition->getBasicDefinition();

		$buttons['save'] = sprintf(
			'<input type="submit" name="save" id="save" class="tl_submit" accesskey="s" value="%s" />',
			$this->getButtonLabel('save')
		);

		$buttons['saveNclose'] = sprintf(
			'<input type="submit" name="saveNclose" id="saveNclose" class="tl_submit" accesskey="c" value="%s" />',
			$this->getButtonLabel('saveNclose')
		);

		if (!$this->isPopup() && $basicDefinition->isCreatable())
		{
			$buttons['saveNcreate'] = sprintf(
				'<input type="submit" name="saveNcreate" id="saveNcreate" class="tl_submit" accesskey="n" value="%s" />',
				$this->getButtonLabel('saveNcreate')
			);
		}

		// TODO: unknown input param s2e - I guess it means "switch to edit" but from which view used?
		if ($this->getEnvironment()->getInputProvider()->hasParameter('s2e'))
		{
			$buttons['saveNedit'] = sprintf(
				'<input type="submit" name="saveNedit" id="saveNedit" class="tl_submit" accesskey="e" value="%s" />',
				$this->getButtonLabel('saveNedit')
			);
		}
		elseif(!$this->isPopup()
			&& (($basicDefinition->getMode() == BasicDefinitionInterface::MODE_PARENTEDLIST)
				|| strlen($basicDefinition->getParentDataProvider())
				|| $basicDefinition->isSwitchToEditEnabled()
			)
		)
		{
			$buttons['saveNback'] = sprintf(
				'<input type="submit" name="saveNback" id="saveNback" class="tl_submit" accesskey="g" value="%s" />',
				$this->getButtonLabel('saveNback')
			);
		}

		$event = new GetEditModeButtonsEvent($this->getEnvironment());
		$event->setButtons($buttons);

		$this->getEnvironment()->getEventPropagator()->propagate(
			$event::NAME,
			$event,
			array($definition->getName())
		);

		return $event->getButtons();
	}

	/**
	 * Retrieve a list of html buttons to use in the bottom panel (submit area).
	 *
	 * @return array
	 */
	protected function getSelectButtons()
	{
		$definition      = $this->getDataDefinition();
		$basicDefinition = $definition->getBasicDefinition();
		$buttons         = array();

		if ($basicDefinition->isDeletable())
		{
			$buttons['delete'] = sprintf(
				'<input
				type="submit"
				name="delete"
				id="delete"
				class="tl_submit"
				accesskey="d"
				onclick="return confirm(\'%s\')"
				value="%s" />',
				$GLOBALS['TL_LANG']['MSC']['delAllConfirm'],
				specialchars($this->translate('MSC.deleteSelected'))
			);
		}

		if ($basicDefinition->isEditable())
		{
			$buttons['cut'] = sprintf(
				'<input type="submit" name="cut" id="cut" class="tl_submit" accesskey="x" value="%s">',
				specialchars($this->translate('MSC.moveSelected'))
			);
		}

		if ($basicDefinition->isCreatable())
		{
			$buttons['copy'] = sprintf(
				'<input type="submit" name="copy" id="copy" class="tl_submit" accesskey="c" value="%s">',
				specialchars($this->translate('MSC.copySelected'))
			);
		}

		if ($basicDefinition->isEditable())
		{
			$buttons['override'] = sprintf(
				'<input type="submit" name="override" id="override" class="tl_submit" accesskey="v" value="%s">',
				specialchars($this->translate('MSC.overrideSelected'))
			);

			$buttons['edit'] = sprintf(
				'<input type="submit" name="edit" id="edit" class="tl_submit" accesskey="s" value="%s">',
				specialchars($this->translate('MSC.editSelected'))
			);
		}

		$event = new GetSelectModeButtonsEvent($this->getEnvironment());
		$event->setButtons($buttons);

		$this->getEnvironment()->getEventPropagator()->propagate(
			$event::NAME,
			$event,
			array($this->getEnvironment()->getDataDefinition()->getName())
		);

		return $event->getButtons();
	}

	/**
	 * Update the clipboard in the Environment with data from the InputProvider.
	 *
	 * The following parameters have to be provided by the input provider:
	 *
	 * Name      Type   Description
	 * clipboard bool   Flag determining if the clipboard shall get cleared.
	 * act       string Action to perform, either paste, cut or create.
	 * id        mixed  The Id of the item to copy. In mode cut this is the id of the item to be moved.
	 *
	 * @param null|string $action The action to be executed or null.
	 *
	 * @return BaseView
	 */
	public function checkClipboard($action = null)
	{
		$objInput     = $this->getEnvironment()->getInputProvider();
		$objClipboard = $this->getEnvironment()->getClipboard();

		// Reset Clipboard.
		if ($objInput->getParameter('clipboard') == '1')
		{
			// Check clipboard from session.
			$objClipboard
				->loadFrom($this->getEnvironment())
				->clear()
				->saveTo($this->getEnvironment());

			$this->redirectHome();
		}
		// Push some entry into clipboard.
		elseif ($id = $objInput->getParameter('source'))
		{
			$idDetails   = IdSerializer::fromSerialized($id);
			$objDataProv = $this->getEnvironment()->getDataProvider($idDetails->getDataProviderName());

			if ($action && $action == 'cut' || $objInput->getParameter('act') == 'cut')
			{
				$arrIgnored = array($id);

				// We have to ignore all children of this element in mode 5 (to prevent circular references).
				if ($this->getDataDefinition()->getBasicDefinition()->getMode() == BasicDefinitionInterface::MODE_HIERARCHICAL)
				{
					$objModel  = $objDataProv->fetch($objDataProv->getEmptyConfig()->setId($idDetails->getId()));
					$ignoredId = IdSerializer::fromValues($objModel->getProviderName(), 0);

					// FIXME: this can return ids originating from another data provider, we have to alter this to
					// FIXME: return valid models instead of the ids or a tuple of data provider name and id.
					foreach ($this->getEnvironment()->getController()->assembleAllChildrenFrom($objModel) as $childId)
					{
						$arrIgnored[] = $ignoredId->setId($childId)->getSerialized();
					}
				}

				$objClipboard
					->clear()
					->cut($id)
					->setCircularIds($arrIgnored);

				// Let the clipboard save it's values persistent.
				$objClipboard->saveTo($this->getEnvironment());

				$this->redirectHome();
			}
			elseif ($action && $action == 'copy' || $objInput->getParameter('act') == 'copy')
			{
				$arrIgnored     = array($id);
				$objContainedId = trimsplit(',', $objInput->getParameter('children'));

				$objClipboard
					->clear()
					->copy($id)
					->setCircularIds($arrIgnored);

				if (is_array($objContainedId) && !empty($objContainedId))
				{
					$objClipboard->setContainedIds($objContainedId);
				}

				// Let the clipboard save it's values persistent.
				$objClipboard->saveTo($this->getEnvironment());

				$this->redirectHome();
			}
			elseif ($action && $action == 'create' || $objInput->getParameter('act') == 'create')
			{
				$arrIgnored     = array($id);
				$objContainedId = trimsplit(',', $objInput->getParameter('children'));

				$objClipboard
					->clear()
					->create($id)
					->setCircularIds($arrIgnored);

				if (is_array($objContainedId) && !empty($objContainedId))
				{
					$objClipboard->setContainedIds($objContainedId);
				}

				// Let the clipboard save it's values persistent.
				$objClipboard->saveTo($this->getEnvironment());

				$this->redirectHome();
			}
		}

		// Check clipboard from session.
		$objClipboard->loadFrom($this->getEnvironment());

		return $this;
	}

	/**
	 * Determine if we are currently working in multi language mode.
	 *
	 * @param mixed $mixId The id of the current model.
	 *
	 * @return bool
	 */
	protected function isMultiLanguage($mixId)
	{
		return count($this->getEnvironment()->getController()->getSupportedLanguages($mixId)) > 0;
	}

	/**
	 * Check if the data provider is multi language and prepare the data provider with the selected language.
	 *
	 * @return void
	 */
	protected function checkLanguage()
	{
		$environment     = $this->getEnvironment();
		$inputProvider   = $environment->getInputProvider();
		$objDataProvider = $environment->getDataProvider();
		$strProviderName = $environment->getDataDefinition()->getName();
		$idDetails       = $inputProvider->getParameter('id')
			? IdSerializer::fromSerialized($inputProvider->getParameter('id'))
			: null;
		$mixID           = $idDetails ? $idDetails->getId() : null;
		$arrLanguage     = $environment->getController()->getSupportedLanguages($mixID);

		if (!$arrLanguage)
		{
			return;
		}

		// Load language from Session.
		$arrSession = $inputProvider->getPersistentValue('dc_general');
		if (!is_array($arrSession))
		{
			$arrSession = array();
		}
		/** @var MultiLanguageDataProviderInterface $objDataProvider */

		// Try to get the language from session.
		if (isset($arrSession['ml_support'][$strProviderName][$mixID]))
		{
			$strCurrentLanguage = $arrSession['ml_support'][$strProviderName][$mixID];
		}
		else
		{
			$strCurrentLanguage = $GLOBALS['TL_LANGUAGE'];
		}

		// Get/Check the new language.
		if ((strlen($inputProvider->getValue('language')) != 0)
			&& ($inputProvider->getValue('FORM_SUBMIT') == 'language_switch'))
		{
			if (array_key_exists($inputProvider->getValue('language'), $arrLanguage))
			{
				$strCurrentLanguage = $inputProvider->getValue('language');
			}
		}

		if (!array_key_exists($strCurrentLanguage, $arrLanguage))
		{
			$strCurrentLanguage = $objDataProvider->getFallbackLanguage($mixID)->getLanguageCode();
		}

		$arrSession['ml_support'][$strProviderName][$mixID] = $strCurrentLanguage;
		$inputProvider->setPersistentValue('dc_general', $arrSession);

		$objDataProvider->setCurrentLanguage($strCurrentLanguage);
	}

	/**
	 * Create a new instance of ContaoBackendViewTemplate with the template file of the given name.
	 *
	 * @param string $strTemplate Name of the template to create.
	 *
	 * @return ContaoBackendViewTemplate
	 */
	protected function getTemplate($strTemplate)
	{
		return new ContaoBackendViewTemplate($strTemplate);
	}

	/**
	 * Handle an ajax call by passing it to the relevant handler class.
	 *
	 * The handler class might(!) exit the script.
	 *
	 * @return void
	 */
	public function handleAjaxCall()
	{
		/** @var \ContaoCommunityAlliance\DcGeneral\Controller\Ajax $handler */
		// Fallback to Contao for ajax requests we do not know.
		if (version_compare(VERSION, '3.0', '>='))
		{
			$handler = new Ajax3X();
		}
		else
		{
			$handler = new Ajax2X();
		}
		$handler->executePostActions(new DcCompat($this->getEnvironment()));
	}

	/**
	 * {@inheritDoc}
	 */
	public function copy()
	{
		if ($this->environment->getDataDefinition()->getBasicDefinition()->isEditOnlyMode())
		{
			return $this->edit();
		}

		// TODO: copy unimplemented.

		return vsprintf($this->notImplMsg, 'copy - Mode');
	}

	/**
	 * {@inheritDoc}
	 */
	public function copyAll()
	{
		if ($this->environment->getDataDefinition()->getBasicDefinition()->isEditOnlyMode())
		{
			return $this->edit();
		}

		// TODO: copyAll unimplemented.

		return vsprintf($this->notImplMsg, 'copyAll - Mode');
	}

	/**
	 * {@inheritDoc}
	 *
	 * @see edit()
	 */
	public function create()
	{
		if ($this->environment->getDataDefinition()->getBasicDefinition()->isEditOnlyMode())
		{
			return $this->edit();
		}

		$model = $this->createEmptyModelWithDefaults();

		$input = $this->environment->getInputProvider();
		if ($input->hasParameter('after'))
		{
			$after          = IdSerializer::fromSerialized($input->getParameter('after'));
			$dataProvider   = $this->environment->getDataProvider($after->getDataProviderName());
			$previous       = $dataProvider->fetch($dataProvider->getEmptyConfig()->setId($after->getId()));
			$dataDefinition = $this->environment->getDataDefinition();

			/** @var Contao2BackendViewDefinitionInterface $view */
			$view = $dataDefinition->getDefinition(Contao2BackendViewDefinitionInterface::NAME);
			foreach (array_keys($view->getListingConfig()->getDefaultSortingFields()) as $propertyName) {
				if ($propertyName != 'sorting') {
					$propertyValue = $previous->getProperty($propertyName);
					$model->setProperty($propertyName, $propertyValue);
				}
			}
		}

		$preFunction = function($environment, $model)
		{
			/** @var EnvironmentInterface $environment */
			$copyEvent = new PreCreateModelEvent($environment, $model);
			$environment->getEventPropagator()->propagate(
				$copyEvent::NAME,
				$copyEvent,
				array(
					$environment->getDataDefinition()->getName(),
				)
			);
		};

		$postFunction = function($environment, $model)
		{
			/** @var EnvironmentInterface $environment */
			$copyEvent = new PostCreateModelEvent($environment, $model);
			$environment->getEventPropagator()->propagate(
				$copyEvent::NAME,
				$copyEvent,
				array(
					$environment->getDataDefinition()->getName(),
				)
			);
		};

		return $this->createEditMask($model, null, $preFunction, $postFunction);
	}

	/**
	 * {@inheritDoc}
	 */
	public function cut()
	{
		if ($this->environment->getDataDefinition()->getBasicDefinition()->isEditOnlyMode())
		{
			return $this->edit();
		}

		return $this->showAll();
	}

	/**
	 * {@inheritDoc}
	 */
	public function cutAll()
	{
		// TODO: cutAll unimplemented.

		return vsprintf($this->notImplMsg, 'cutAll - Mode');
	}

	/**
	 * Get the name of the property to use for manual sorting (aka drag drop sorting).
	 *
	 * @return string
	 */
	protected function getManualSortingProperty()
	{
		// FIXME: provide an API method in the data definition for this.
		return $this->getEnvironment()->getDataProvider()->fieldExists('sorting') ? 'sorting': '';
	}

	/**
	 * Retrieve model instances for all ids contained in the clipboard.
	 *
	 * @param bool $clone True if the models shall be copied, false otherwise.
	 *
	 * @return CollectionInterface
	 */
	protected function getModelsFromClipboard($clone = false)
	{
		$environment  = $this->getEnvironment();
		$dataProvider = $environment->getDataProvider();
		$models       = $dataProvider->getEmptyCollection();
		$clipboard    = $this->getEnvironment()->getClipboard();

		foreach ($clipboard->getContainedIds() as $id)
		{
			if ($id === null)
			{
				$model = $this->createEmptyModelWithDefaults();
				$models->push($model);

				if ($parentId = $clipboard->getParent())
				{
					$id           = IdSerializer::fromSerialized($parentId);
					$dataProvider = $environment->getDataProvider($id->getDataProviderName());
					$parentModel  = $dataProvider->fetch($dataProvider->getEmptyConfig()->setId($id->getId()));
					$environment->getController()->setParent($model, $parentModel);
				}
			}
			elseif (is_string($id))
			{
				$id           = IdSerializer::fromSerialized($id);
				$dataProvider = $environment->getDataProvider($id->getDataProviderName());
				$model        = $dataProvider->fetch($dataProvider->getEmptyConfig()->setId($id->getId()));

				if ($model)
				{
					if ($clone)
					{
						// Trigger the pre duplicate event.
						$duplicateEvent = new PreDuplicateModelEvent($environment, $model);
						$environment->getEventPropagator()->propagate(
							$duplicateEvent::NAME,
							$duplicateEvent,
							array(
								$environment->getDataDefinition()->getName(),
							)
						);

						// Make a duplicate.
						$newModel = $environment->getController()->createClonedModel($model);

						// And trigger the post event for it.
						$duplicateEvent = new PostDuplicateModelEvent($environment, $newModel, $model);
						$environment->getEventPropagator()->propagate(
							$duplicateEvent::NAME,
							$duplicateEvent,
							array(
								$environment->getDataDefinition()->getName(),
							)
						);

						// Set the new model as the old one.
						$model = $newModel;
					}

					$models->push($model);
				}
			}
		}

		return $models;
	}

	/**
	 * Invoked for cut and copy.
	 *
	 * This performs redirectHome() upon successful execution and throws an exception otherwise.
	 *
	 * @return void
	 *
	 * @throws DcGeneralRuntimeException When invalid parameters are encountered.
	 */
	public function paste()
	{
		$this->checkClipboard();

		$environment = $this->getEnvironment();
		$input       = $environment->getInputProvider();
		$clipboard   = $environment->getClipboard();
		$source      = $input->getParameter('source')
			? IdSerializer::fromSerialized($input->getParameter('source'))
			: null;
		$after       = $input->getParameter('after')
			? IdSerializer::fromSerialized($input->getParameter('after'))
			: $input->getParameter('after');
		$into        = $input->getParameter('into')
			? IdSerializer::fromSerialized($input->getParameter('into'))
			: null;

		if ($input->getParameter('mode') == 'create')
		{
			$dataProvider = $environment->getDataProvider();

			$models = $dataProvider->getEmptyCollection();
			$models->push($dataProvider->getEmptyModel());

			$clipboard->create($input->getParameter('pid'))->saveTo($environment);

			$this->redirectHome();
		}

		if ($source)
		{
			$dataProvider = $environment->getDataProvider($source->getDataProviderName());

			$filterConfig = $dataProvider->getEmptyConfig();

			$filterConfig->setFilter(
				array(
					array(
						'operation' => '=',
						'property'  => 'id',
						'value'     => $source->getId()
					)
				)
			);

			$models = $dataProvider->fetchAll($filterConfig);
		}
		else
		{
			$models = $this->getModelsFromClipboard($clipboard->isCopy());

			if ($clipboard->isCopy())
			{
				// FIXME: recursive copy is not implemented yet!
			}
		}

		// Trigger for each model the pre persist event.
		foreach ($models as $model)
		{
			$event = new PrePasteModelEvent($environment, $model);
			$environment->getEventPropagator()->propagate(
				$event::NAME,
				$event,
				$environment->getDataDefinition()->getName()
			);
		}

		if ($after && $after->getId())
		{
			$dataProvider = $environment->getDataProvider($after->getDataProviderName());
			$previous     = $dataProvider->fetch($dataProvider->getEmptyConfig()->setId($after->getId()));
			$environment->getController()->pasteAfter($previous, $models, $this->getManualSortingProperty());
		}
		elseif ($into && $into->getId())
		{
			$dataProvider = $environment->getDataProvider($into->getDataProviderName());
			$parent       = $dataProvider->fetch($dataProvider->getEmptyConfig()->setId($into->getId()));
			$environment->getController()->pasteInto($parent, $models, $this->getManualSortingProperty());
		}
		elseif (($after && $after->getId() == '0') || ($into && $into->getId() == '0'))
		{
			$environment->getController()->pasteTop($models, $this->getManualSortingProperty());
		}
		else
		{
			throw new DcGeneralRuntimeException('Invalid parameters.');
		}

		// Trigger for each model the past persist event.
		foreach ($models as $model)
		{
			$event = new PostPasteModelEvent($environment, $model);
			$environment->getEventPropagator()->propagate(
				$event::NAME,
				$event,
				$environment->getDataDefinition()->getName()
			);
		}

		if (!$source)
		{
			$clipboard
				->clear()
				->saveTo($environment);
		}

		$this->redirectHome();

		throw new DcGeneralRuntimeException('Invalid paste operation parameters.');
	}

	/**
	 * Delete a model and redirect the user to the listing.
	 *
	 * NOTE: This method redirects the user to the listing and therefore the script will be ended.
	 *
	 * @return string
	 *
	 * @throws DcGeneralRuntimeException If the model to delete could not be loaded.
	 */
	public function delete()
	{
		if ($this->environment->getDataDefinition()->getBasicDefinition()->isEditOnlyMode())
		{
			return $this->edit();
		}

		// Check if is it allowed to delete a record.
		if (!$this->getEnvironment()->getDataDefinition()->getBasicDefinition()->isDeletable())
		{
			$this->getEnvironment()->getEventPropagator()->propagate(
				ContaoEvents::SYSTEM_LOG,
				new LogEvent(
					sprintf(
						'Table "%s" is not deletable', 'DC_General - DefaultController - delete()',
						$this->getEnvironment()->getDataDefinition()->getName()
					),
					__CLASS__ . '::delete()',
					TL_ERROR
				)
			);

			$this->getEnvironment()->getEventPropagator()->propagate(
				ContaoEvents::CONTROLLER_REDIRECT,
				new RedirectEvent('contao/main.php?act=error')
			);
		}

		$environment  = $this->getEnvironment();
		$modelId      = IdSerializer::fromSerialized($environment->getInputProvider()->getParameter('id'));
		$dataProvider = $environment->getDataProvider($modelId->getDataProviderName());
		$model        = $dataProvider->fetch($dataProvider->getEmptyConfig()->setId($modelId->getId()));

		if (!$model->getId())
		{
			throw new DcGeneralRuntimeException(
				'Could not load model with id ' . $environment->getInputProvider()->getParameter('id')
			);
		}

		// Trigger event before the model will be deleted.
		$event = new PreDeleteModelEvent($environment, $model);
		$environment->getEventPropagator()->propagate(
			$event::NAME,
			$event,
			array(
				$this->getEnvironment()->getDataDefinition()->getName(),
			)
		);

		// FIXME: See DefaultController::delete() - we need to delete the children of this item as well over all data providers.
		/*
		$arrDelIDs = array();

		// Delete record
		switch ($definition->getSortingMode())
		{
			case 0:
			case 1:
			case 2:
			case 3:
			case 4:
				$arrDelIDs = array();
				$arrDelIDs[] = $intRecordID;
				break;

			case 5:
				$arrDelIDs = $environment->getController()->fetchMode5ChildrenOf($environment->getCurrentModel(), $blnRecurse = true);
				$arrDelIDs[] = $intRecordID;
				break;
		}

		// Delete all entries
		foreach ($arrDelIDs as $intId)
		{
			$this->getEnvironment()->getDataProvider()->delete($intId);

			// Add a log entry unless we are deleting from tl_log itself
			if ($environment->getDataDefinition()->getName() != 'tl_log')
			{
				BackendBindings::log('DELETE FROM ' . $environment->getDataDefinition()->getName() . ' WHERE id=' . $intId, 'DC_General - DefaultController - delete()', TL_GENERAL);
			}
		}
		 */

		$dataProvider->delete($model);

		// Trigger event after the model is deleted.
		$event = new PostDeleteModelEvent($environment, $model);
		$environment->getEventPropagator()->propagate(
			$event::NAME,
			$event,
			array(
				$this->getEnvironment()->getDataDefinition()->getName(),
			)
		);

		$this->redirectHome();

		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function move()
	{
		if ($this->environment->getDataDefinition()->getBasicDefinition()->isEditOnlyMode())
		{
			return $this->edit();
		}

		// TODO: move unimplemented.
		return vsprintf($this->notImplMsg, 'move - Mode');
	}

	/**
	 * {@inheritDoc}
	 */
	public function undo()
	{
		if ($this->environment->getDataDefinition()->getBasicDefinition()->isEditOnlyMode())
		{
			return $this->edit();
		}

		// TODO: undo unimplemented.
		return vsprintf($this->notImplMsg, 'undo - Mode');
	}

	/**
	 * Handle the submit and determine which button has been triggered.
	 *
	 * This method will redirect the client.
	 *
	 * @param ModelInterface $model The model that has been submitted.
	 *
	 * @return void
	 */
	protected function handleSubmit(ModelInterface $model)
	{
		$environment   = $this->getEnvironment();
		$inputProvider = $environment->getInputProvider();

		if ($inputProvider->hasValue('save'))
		{
			$newUrlEvent = new AddToUrlEvent('act=edit&id=' . IdSerializer::fromModel($model)->getSerialized());
			$environment->getEventPropagator()->propagate(ContaoEvents::BACKEND_ADD_TO_URL, $newUrlEvent);
			$environment->getEventPropagator()->propagate(
				ContaoEvents::CONTROLLER_REDIRECT,
				new RedirectEvent($newUrlEvent->getUrl())
			);
		}
		elseif ($inputProvider->hasValue('saveNclose'))
		{
			setcookie('BE_PAGE_OFFSET', 0, 0, '/');

			// @codingStandardsIgnoreStart - we have to clear the messages - direct access to $_SESSION is ok.
			$_SESSION['TL_INFO']    = '';
			$_SESSION['TL_ERROR']   = '';
			$_SESSION['TL_CONFIRM'] = '';
			// @codingStandardsIgnoreEnd

			$newUrlEvent = new GetReferrerEvent();
			$environment->getEventPropagator()->propagate(ContaoEvents::SYSTEM_GET_REFERRER, $newUrlEvent);
			$environment->getEventPropagator()->propagate(
				ContaoEvents::CONTROLLER_REDIRECT,
				new RedirectEvent($newUrlEvent->getReferrerUrl())
			);
		}
		elseif ($inputProvider->hasValue('saveNcreate'))
		{
			setcookie('BE_PAGE_OFFSET', 0, 0, '/');

			// @codingStandardsIgnoreStart - we have to clear the messages - direct access to $_SESSION is ok.
			$_SESSION['TL_INFO']    = '';
			$_SESSION['TL_ERROR']   = '';
			$_SESSION['TL_CONFIRM'] = '';
			// @codingStandardsIgnoreEnd

			$after = IdSerializer::fromModel($model);

			$newUrlEvent = new AddToUrlEvent('act=create&id=&after=' . $after->getSerialized());
			$environment->getEventPropagator()->propagate(ContaoEvents::BACKEND_ADD_TO_URL, $newUrlEvent);
			$environment->getEventPropagator()->propagate(
				ContaoEvents::CONTROLLER_REDIRECT,
				new RedirectEvent($newUrlEvent->getUrl())
			);
		}
		elseif ($inputProvider->hasValue('saveNback'))
		{
			echo vsprintf($this->notImplMsg, 'Save and go back');
			exit;
		}
	}

	/**
	 * Check the submitted data if we want to restore a previous version of a model.
	 *
	 * If so, the model will get loaded and marked as active version in the data provider and the client will perform a
	 * reload of the page.
	 *
	 * @return void
	 *
	 * @throws DcGeneralRuntimeException When the requested version could not be located in the database.
	 */
	protected function checkRestoreVersion()
	{
		$environment   = $this->getEnvironment();
		$definition    = $environment->getDataDefinition();
		$inputProvider = $environment->getInputProvider();

		if (!$inputProvider->hasParameter('id'))
		{
			return;
		}

		$modelId                 = IdSerializer::fromSerialized($inputProvider->getParameter('id'));
		$dataProviderDefinition  = $definition->getDataProviderDefinition();
		$dataProvider            = $environment->getDataProvider($modelId->getDataProviderName());
		$dataProviderInformation = $dataProviderDefinition->getInformation($modelId->getDataProviderName());

		if ($dataProviderInformation->isVersioningEnabled()
			&& ($inputProvider->getValue('FORM_SUBMIT') === 'tl_version')
			&& ($modelVersion = $inputProvider->getValue('version')) !== null)
		{
			$model = $dataProvider->getVersion($modelId->getId(), $modelVersion);

			if ($model === null)
			{
				$message = sprintf(
					'Could not load version %s of record ID %s from %s',
					$modelVersion,
					$modelId->getId(),
					$modelId->getDataProviderName()
				);

				$environment->getEventPropagator()->propagate(
					ContaoEvents::SYSTEM_LOG,
					new LogEvent($message, TL_ERROR, 'DC_General - checkRestoreVersion()')
				);

				throw new DcGeneralRuntimeException($message);
			}

			$dataProvider->save($model);
			$dataProvider->setVersionActive($modelId->getId(), $modelVersion);
			$environment->getEventPropagator()->propagate(ContaoEvents::CONTROLLER_RELOAD, new ReloadEvent());
		}
	}

	/**
	 * Abstract method to be overridden in the certain child classes.
	 *
	 * This method will update the parent relationship between a model and the parent item.
	 *
	 * @param ModelInterface $model The model to be updated.
	 *
	 * @return void
	 */
	public function enforceModelRelationship($model)
	{
		// No op in this base class but implemented in subclasses to enforce parent<->child relationship.
	}

	/**
	 * Create an empty model using the default values from the definition.
	 *
	 * @return ModelInterface
	 */
	protected function createEmptyModelWithDefaults()
	{
		$environment        = $this->getEnvironment();
		$definition         = $environment->getDataDefinition();
		$environment        = $this->getEnvironment();
		$dataProvider       = $environment->getDataProvider();
		$propertyDefinition = $definition->getPropertiesDefinition();
		$properties         = $propertyDefinition->getProperties();
		$model              = $dataProvider->getEmptyModel();

		foreach ($properties as $property)
		{
			$propName = $property->getName();

			if ($property->getDefaultValue() !== null)
			{
				$model->setProperty($propName, $property->getDefaultValue());
			}
		}

		return $model;
	}

	/**
	 * Update the versioning information in the data provider for a given model (if necessary).
	 *
	 * @param ModelInterface $model The model to update.
	 *
	 * @return void
	 */
	protected function storeVersion(ModelInterface $model)
	{
		$modelId                 = $model->getId();
		$environment             = $this->getEnvironment();
		$definition              = $environment->getDataDefinition();
		$dataProvider            = $environment->getDataProvider($model->getProviderName());
		$dataProviderDefinition  = $definition->getDataProviderDefinition();
		$dataProviderInformation = $dataProviderDefinition->getInformation($model->getProviderName());

		if (!$dataProviderInformation->isVersioningEnabled())
		{
			return;
		}

		// Compare version and current record.
		$currentVersion = $dataProvider->getActiveVersion($modelId);
		if (!$currentVersion
			|| !$dataProvider->sameModels($model, $dataProvider->getVersion($modelId, $currentVersion))
		)
		{
			$user = \BackendUser::getInstance();

			$dataProvider->saveVersion($model, $user->username);
		}
	}

	/**
	 * Generate the view for edit.
	 *
	 * @return string
	 *
	 * @throws DcGeneralRuntimeException         When the current data definition is not editable or is closed.
	 *
	 * @throws DcGeneralInvalidArgumentException When an unknown property is mentioned in the palette.
	 */
	public function edit()
	{
		$this->checkLanguage();

		$environment   = $this->getEnvironment();
		$inputProvider = $environment->getInputProvider();
		$modelId       = $inputProvider->hasParameter('id')
			? IdSerializer::fromSerialized($inputProvider->getParameter('id'))
			: null;
		$dataProvider  = $environment->getDataProvider($modelId ? $modelId->getDataProviderName() : null);

		$this->checkRestoreVersion();

		if ($modelId && $modelId->getId())
		{
			$model = $dataProvider->fetch($dataProvider->getEmptyConfig()->setId($modelId->getId()));
		}
		else
		{
			$model = $this->createEmptyModelWithDefaults();
		}

		// We need to keep the original data here.
		$originalModel = clone $model;
		$originalModel->setId($model->getId());

		return $this->createEditMask($model, $originalModel, null, null);
	}

	/**
	 * Create the edit mask.
	 *
	 * @param ModelInterface $model         The model with the current data.
	 *
	 * @param ModelInterface $originalModel The data from the original data.
	 *
	 * @param callable       $preFunction   The function to call before saving an item.
	 *
	 * @param callable       $postFunction  The function to call after saving an item.
	 *
	 * @return string
	 *
	 * @throws DcGeneralRuntimeException         If the data container is not editable, closed.
	 *
	 * @throws DcGeneralInvalidArgumentException If an unknown property is encountered in the palette.
	 */
	protected function createEditMask($model, $originalModel, $preFunction, $postFunction)
	{
		$this->checkLanguage();

		$environment             = $this->getEnvironment();
		$definition              = $environment->getDataDefinition();
		$basicDefinition         = $definition->getBasicDefinition();
		$dataProvider            = $environment->getDataProvider($model->getProviderName());
		$dataProviderDefinition  = $definition->getDataProviderDefinition();
		$dataProviderInformation = $dataProviderDefinition->getInformation($model->getProviderName());
		$inputProvider           = $environment->getInputProvider();
		$palettesDefinition      = $definition->getPalettesDefinition();
		$modelId                 = $model->getId();
		$propertyDefinitions     = $definition->getPropertiesDefinition();
		$blnSubmitted            = ($inputProvider->getValue('FORM_SUBMIT') === $definition->getName());
		$blnIsAutoSubmit         = ($inputProvider->getValue('SUBMIT_TYPE') === 'auto');

		$widgetManager = new ContaoWidgetManager($environment, $model);

		$event = new PreEditModelEvent($this->environment, $model);
		$environment->getEventPropagator()->propagate(
			$event::NAME,
			$event,
			$definition->getName()
		);

		// Check if table is editable.
		if (!$basicDefinition->isEditable())
		{
			$message = 'DataContainer ' . $definition->getName() . ' is not editable';
			$environment->getEventPropagator()->propagate(
				ContaoEvents::SYSTEM_LOG,
				new LogEvent($message, TL_ERROR, 'DC_General - edit()')
			);
			throw new DcGeneralRuntimeException($message);
		}

		// Check if table is closed but we are adding a new item.
		if (empty($modelId) && !$basicDefinition->isCreatable())
		{
			$message = 'DataContainer ' . $definition->getName() . ' is closed';
			$environment->getEventPropagator()->propagate(
				ContaoEvents::SYSTEM_LOG,
				new LogEvent($message, TL_ERROR, 'DC_General - edit()')
			);
			throw new DcGeneralRuntimeException($message);
		}

		$this->enforceModelRelationship($model);

		// Pass 1: Get the palette for the values stored in the model.
		$palette = $palettesDefinition->findPalette($model);

		$propertyValues = $this->processInput($widgetManager);
		$errors         = array();
		if ($blnSubmitted && $propertyValues)
		{
			// Pass 2: Determine the real palette we want to work on if we have some data submitted.
			$palette = $palettesDefinition->findPalette($model, $propertyValues);

			// Update the model - the model might add some more errors to the propertyValueBag via exceptions.
			$this->getEnvironment()->getController()->updateModelFromPropertyBag($model, $propertyValues);
		}

		$arrFieldSets = array();
		$blnFirst     = true;
		foreach ($palette->getLegends() as $legend)
		{
			$legendName = $environment->getTranslator()->translate($legend->getName() . '_legend', $definition->getName());
			$fields     = array();
			$properties = $legend->getProperties($model, $propertyValues);

			if (!$properties)
			{
				continue;
			}

			foreach ($properties as $property)
			{
				if (!$propertyDefinitions->hasProperty($property->getName()))
				{
					throw new DcGeneralInvalidArgumentException(
						sprintf(
							'Property %s is mentioned in palette but not defined in propertyDefinition.',
							$property->getName()
						)
					);
				}

				// If this property is invalid, fetch the error.
				if ((!$blnIsAutoSubmit)
					&& $propertyValues
					&& $propertyValues->hasPropertyValue($property->getName())
					&& $propertyValues->isPropertyValueInvalid($property->getName())
				)
				{
					$errors = array_merge($errors, $propertyValues->getPropertyValueErrors($property->getName()));
				}

				$fields[] = $widgetManager->renderWidget($property->getName(), $blnIsAutoSubmit, $propertyValues);
			}

			$arrFieldSet['label']   = $legendName;
			$arrFieldSet['class']   = ($blnFirst) ? 'tl_tbox' : 'tl_box';
			$arrFieldSet['palette'] = implode('', $fields);
			$arrFieldSet['legend']  = $legend->getName();
			$arrFieldSets[]         = $arrFieldSet;

			$blnFirst = false;
		}

		if ((!$blnIsAutoSubmit) && $blnSubmitted && empty($errors))
		{
			if ($model->getMeta($model::IS_CHANGED))
			{
				// Trigger the event for post persists or create.
				if ($preFunction != null)
				{
					$preFunction($environment, $model, $originalModel);
				}

				$event = new PrePersistModelEvent($environment, $model, $originalModel);
				$environment->getEventPropagator()->propagate(
					$event::NAME,
					$event,
					array(
						$this->getEnvironment()->getDataDefinition()->getName(),
					)
				);

				if (!$model->getId() && $this->getManualSortingProperty())
				{
					$models = $dataProvider->getEmptyCollection();
					$models->push($model);

					$controller = $environment->getController();

					if ($inputProvider->hasParameter('after'))
					{
						$after = IdSerializer::fromSerialized($inputProvider->getParameter('after'));

						$previousDataProvider = $environment->getDataProvider($after->getDataProviderName());
						$previousFetchConfig  = $previousDataProvider->getEmptyConfig();
						$previousFetchConfig->setId($after->getId());
						$previousModel = $previousDataProvider->fetch($previousFetchConfig);

						if ($previousModel)
						{
							$controller->pasteAfter($previousModel, $models, $this->getManualSortingProperty());
						}
						else
						{
							$controller->pasteTop($models, $this->getManualSortingProperty());
						}
					}
					elseif ($inputProvider->hasParameter('into'))
					{
						$into = IdSerializer::fromSerialized($inputProvider->getParameter('into'));

						$parentDataProvider = $environment->getDataProvider($into->getDataProviderName());
						$parentFetchConfig  = $parentDataProvider->getEmptyConfig();
						$parentFetchConfig->setId($into->getId());
						$parentModel = $parentDataProvider->fetch($parentFetchConfig);

						if ($parentModel)
						{
							$controller->pasteInto($parentModel, $models, $this->getManualSortingProperty());
						}
						else
						{
							$controller->pasteTop($models, $this->getManualSortingProperty());
						}
					}
					else
					{
						$controller->pasteTop($models, $this->getManualSortingProperty());
					}

					$environment->getClipboard()->clear()->saveTo($environment);
				}
				else
				{
					// Save the model.
					$dataProvider->save($model);
				}

				// Trigger the event for post persists or create.
				if ($postFunction != null)
				{
					$postFunction($environment, $model, $originalModel);
				}

				$event = new PostPersistModelEvent($environment, $model, $originalModel);
				$environment->getEventPropagator()->propagate(
					$event::NAME,
					$event,
					array(
						$this->getEnvironment()->getDataDefinition()->getName(),
					)
				);

				$this->storeVersion($model);
			}

			$this->handleSubmit($model);
		}

		if ($model->getId())
		{
			$strHeadline = sprintf($this->translate('editRecord', $definition->getName()), 'ID ' . $model->getId());
			if ($strHeadline === 'editRecord')
			{
				$strHeadline = sprintf($this->translate('MSC.editRecord'), 'ID ' . $model->getId());
			}
		}
		else
		{
			$strHeadline = sprintf($this->translate('newRecord', $definition->getName()), 'ID ' . $model->getId());
			if ($strHeadline === 'newRecord')
			{
				$strHeadline = sprintf($this->translate('MSC.editRecord'), '');
			}
		}

		$objTemplate = $this->getTemplate('dcbe_general_edit');
		$objTemplate->setData(array(
				'fieldsets' => $arrFieldSets,
				'versions' => $dataProviderInformation->isVersioningEnabled() ? $dataProvider->getVersions($model->getId()) : null,
				'subHeadline' => $strHeadline,
				'table' => $definition->getName(),
				'enctype' => 'multipart/form-data',
				'error' => $errors,
				'editButtons' => $this->getEditButtons(),
				'noReload' => (bool)$errors
			));

		if ($this->isMultiLanguage($model->getId()))
		{
			/** @var MultiLanguageDataProviderInterface $dataProvider */
			$langsNative = array();
			// @codingStandardsIgnoreStart - We have to include it multiple times.
			require TL_ROOT . '/system/config/languages.php';
			// @codingStandardsIgnoreEnd

			$this
				->addToTemplate('languages', $environment->getController()->getSupportedLanguages($model->getId()), $objTemplate)
				->addToTemplate('language', $dataProvider->getCurrentLanguage(), $objTemplate)
				->addToTemplate('languageHeadline', $langsNative[$dataProvider->getCurrentLanguage()], $objTemplate);
		}
		else
		{
			$this
				->addToTemplate('languages', null, $objTemplate)
				->addToTemplate('languageHeadline', '', $objTemplate);
		}

		return $objTemplate->parse();
	}

	/**
	 * Calculate the label of a property to se in "show" view.
	 *
	 * @param PropertyInterface $property The property for which the label shall be calculated.
	 *
	 * @return string
	 */
	protected function getLabelForShow(PropertyInterface $property)
	{
		$environment = $this->getEnvironment();
		$definition  = $environment->getDataDefinition();

		$label = $environment->getTranslator()->translate($property->getLabel(), $definition->getName());

		if (!$label)
		{
			$label = $environment->getTranslator()->translate('MSC.' . $property->getName());
		}

		if (is_array($label))
		{
			$label = $label[0];
		}

		if (!$label)
		{
			$label = $property->getName();
		}

		return $label;
	}

	/**
	 * Show Information about a model.
	 *
	 * @return string
	 *
	 * @throws DcGeneralRuntimeException When an unknown property is mentioned in the palette.
	 */
	public function show()
	{
		if ($this->environment->getDataDefinition()->getBasicDefinition()->isEditOnlyMode())
		{
			return $this->edit();
		}

		// Load check multi language.
		$environment  = $this->getEnvironment();
		$definition   = $environment->getDataDefinition();
		$properties   = $definition->getPropertiesDefinition();
		$translator   = $environment->getTranslator();
		$modelId      = IdSerializer::fromSerialized($environment->getInputProvider()->getParameter('id'));
		$dataProvider = $environment->getDataProvider($modelId->getDataProviderName());

		// Select language in data provider.
		$this->checkLanguage();

		$objDBModel = $dataProvider->fetch($dataProvider->getEmptyConfig()->setId($modelId->getId()));

		if ($objDBModel == null)
		{
			$environment->getEventPropagator()->propagate(
				ContaoEvents::SYSTEM_LOG,
				new LogEvent(
					sprintf(
						'Could not find ID %s in %s.', 'DC_General show()',
						$modelId->getId(),
						$definition->getName()
					),
					__CLASS__ . '::' . __FUNCTION__,
					TL_ERROR
				)
			);

			$environment->getEventPropagator()->propagate(
				ContaoEvents::CONTROLLER_REDIRECT,
				new RedirectEvent('contao/main.php?act=error')
			);
		}

		$values = array();
		$labels = array();

		$palette = $definition->getPalettesDefinition()->findPalette($objDBModel);

		// Show only allowed fields.
		foreach ($palette->getVisibleProperties($objDBModel) as $paletteProperty)
		{
			$property = $properties->getProperty($paletteProperty->getName());

			if (!$property)
			{
				throw new DcGeneralRuntimeException('Unable to retrieve property ' . $paletteProperty->getName());
			}

			// Make it human readable.
			$values[$paletteProperty->getName()] = $this->getReadableFieldValue(
				$property,
				$objDBModel,
				$objDBModel->getProperty($paletteProperty->getName())
			);
			$labels[$paletteProperty->getName()] = $this->getLabelForShow($property);
		}

		$headline = $translator->translate(
			'MSC.showRecord',
			$definition->getName(),
			array($objDBModel->getId() ? 'ID ' . $objDBModel->getId() : '')
		);

		if ($headline == 'MSC.showRecord')
		{
			$headline = $translator->translate(
				'MSC.showRecord',
				null,
				array($objDBModel->getId() ? 'ID ' . $objDBModel->getId() : '')
			);
		}

		$template = $this->getTemplate('dcbe_general_show');
		$this
			->addToTemplate('headline', $headline, $template)
			->addToTemplate('arrFields', $values, $template)
			->addToTemplate('arrLabels', $labels, $template);

		if ($this->isMultiLanguage($objDBModel->getId()))
		{
			/** @var MultiLanguageDataProviderInterface $dataProvider */
			$this
				->addToTemplate('languages', $environment->getController()->getSupportedLanguages($objDBModel->getId()), $template)
				->addToTemplate('currentLanguage', $dataProvider->getCurrentLanguage(), $template)
				->addToTemplate('languageSubmit', specialchars($translator->translate('MSC.showSelected')), $template)
				->addToTemplate('backBT', specialchars($translator->translate('MSC.backBT')), $template);
		}
		else
		{
			$this->addToTemplate('language', null, $template);
		}

		return $template->parse();
	}

	/**
	 * Show all entries from one table.
	 *
	 * @return string
	 */
	public function showAll()
	{
		if ($this->environment->getDataDefinition()->getBasicDefinition()->isEditOnlyMode())
		{
			return $this->edit();
		}

		return sprintf(
			$this->notImplMsg,
			'showAll - Mode ' . $this->environment->getDataDefinition()->getBasicDefinition()->getMode()
		);
	}

	/**
	 * Handle the "toggle" action.
	 *
	 * @param string $name The command name (default: toggle).
	 *
	 * @return string
	 */
	public function toggle($name = 'toggle')
	{
		$environment = $this->getEnvironment();
		$input       = $environment->getInputProvider();

		if ($input->hasParameter('id'))
		{
			$serializedId = IdSerializer::fromSerialized($input->getParameter('id'));
		}

		if (!(isset($serializedId) && $serializedId->getDataProviderName() == $environment->getDataDefinition()->getName()))
		{
			return '';
		}

		/** @var ToggleCommandInterface $operation */
		$operation    = $this->getViewSection()->getModelCommands()->getCommandNamed($name);
		$dataProvider = $environment->getDataProvider();
		$newState     = $operation->isInverse()
			? $input->getParameter('state') == 1 ? '' : '1'
			: $input->getParameter('state') == 1 ? '1' : '';

		$model = $dataProvider->fetch($dataProvider->getEmptyConfig()->setId($serializedId->getId()));

		$model->setProperty($operation->getToggleProperty(), $newState);

		$dataProvider->save($model);

		return $this->showAll();
	}

	/**
	 * Create the "new" button.
	 *
	 * @return CommandInterface|null
	 */
	protected function getCreateModelCommand()
	{
		$environment     = $this->getEnvironment();
		$definition      = $environment->getDataDefinition();
		$basicDefinition = $definition->getBasicDefinition();
		$providerName    = $environment->getDataDefinition()->getName();
		$mode            = $basicDefinition->getMode();
		$config          = $this->getEnvironment()->getController()->getBaseConfig();
		$sorting         = $this->getManualSortingProperty();

		if ($serializedPid = $environment->getInputProvider()->getParameter('pid'))
		{
			$pid = IdSerializer::fromSerialized($serializedPid);
		}
		else
		{
			$pid = new IdSerializer();
		}

		if (!$basicDefinition->isCreatable())
		{
			return null;
		}

		$command    = new Command();
		$parameters = $command->getParameters();
		$extra      = $command->getExtra();

		$extra['class']      = 'header_new';
		$extra['accesskey']  = 'n';
		$extra['attributes'] = 'onclick="Backend.getScrollOffset();"';

		$command
			->setName('button_new')
			->setLabel($this->translate('new.0', $providerName))
			->setDescription($this->translate('new.1', $providerName));

		$this->getPanel()->initialize($config);

		// Add new button.
		if (($mode == BasicDefinitionInterface::MODE_FLAT)
			|| (($mode == BasicDefinitionInterface::MODE_PARENTEDLIST) && !$sorting))
		{
			$parameters['act'] = 'create';
			// Add new button.
			if ($pid->getDataProviderName() && $pid->getId())
			{
				$parameters['pid'] = $pid->getSerialized();
			}
		}
		elseif(($mode == BasicDefinitionInterface::MODE_PARENTEDLIST)
			|| ($mode == BasicDefinitionInterface::MODE_HIERARCHICAL))
		{
			if ($environment->getClipboard()->isNotEmpty())
			{
				return null;
			}

			$after = IdSerializer::fromValues($definition->getName(), 0);

			$parameters['act']  = 'paste';
			$parameters['mode'] = 'create';

			if ($mode == BasicDefinitionInterface::MODE_PARENTEDLIST)
			{
				$parameters['after'] = $after->getSerialized();
			}

			if ($pid->getDataProviderName() && $pid->getId())
			{
				$parameters['pid'] = $pid->getSerialized();
			}
		}

		return $command;
	}
	/**
	 * Create the "clear clipboard" button.
	 *
	 * @return CommandInterface|null
	 */
	protected function getClearClipboardCommand()
	{
		if ($this->getEnvironment()->getClipboard()->isEmpty())
		{
			return null;
		}
		$command             = new Command();
		$parameters          = $command->getParameters();
		$extra               = $command->getExtra();
		$extra['class']      = 'header_clipboard';
		$extra['accesskey']  = 'x';
		$extra['attributes'] = 'onclick="Backend.getScrollOffset();"';

		$parameters['clipboard'] = '1';

		$command
			->setName('button_clipboard')
			->setLabel($this->translate('MSC.clearClipboard'))
			->setDescription($this->translate('MSC.clearClipboard'));

		return $command;
	}

	/**
	 * Create the "back" button.
	 *
	 * @return CommandInterface|null
	 */
	protected function getBackCommand()
	{
		$environment = $this->getEnvironment();
		if (!($this->isSelectModeActive()
			|| $environment->getDataDefinition()->getBasicDefinition()->getParentDataProvider())
		)
		{
			return null;
		}

		/** @var GetReferrerEvent $event */
		if ($environment->getParentDataDefinition())
		{
			$event = $environment->getEventPropagator()->propagate(
				ContaoEvents::SYSTEM_GET_REFERRER,
				new GetReferrerEvent(true, $environment->getParentDataDefinition()->getName())
			);
		}
		else {
			$event = $environment->getEventPropagator()->propagate(
				ContaoEvents::SYSTEM_GET_REFERRER,
				new GetReferrerEvent(true, $environment->getDataDefinition()->getName())
			);
		}

		$command             = new Command();
		$extra               = $command->getExtra();
		$extra['class']      = 'header_back';
		$extra['accesskey']  = 'b';
		$extra['attributes'] = 'onclick="Backend.getScrollOffset();"';
		$extra['href']       = $event->getReferrerUrl();

		$command
			->setName('back_button')
			->setLabel($this->translate('MSC.backBT'))
			->setDescription($this->translate('MSC.backBT'));

		return $command;
	}

	/**
	 * Render a single header button.
	 *
	 * @param CommandInterface $command The command definition.
	 *
	 * @return string
	 */
	protected function generateHeaderButton(CommandInterface $command)
	{
		$environment = $this->getEnvironment();
		$extra       = $command->getExtra();
		$label       = $command->getLabel();

		if (isset($extra['href']))
		{
			$href = $extra['href'];
		}
		else
		{
			$href = '';
			foreach ($command->getParameters() as $key => $value)
			{
				$href .= '&' . $key . '=' . $value;
			}

			/** @var AddToUrlEvent $event */
			$event = $environment->getEventPropagator()->propagate(
				ContaoEvents::BACKEND_ADD_TO_URL,
				new AddToUrlEvent(
					$href
				)
			);

			$href = $event->getUrl();
		}

		if (!strlen($label))
		{
			$label = $command->getName();
		}

		$buttonEvent = new GetGlobalButtonEvent($this->getEnvironment());
		$buttonEvent
			->setAccessKey(isset($extra['accesskey']) ? trim($extra['accesskey']) : null)
			->setAttributes(' ' . ltrim($extra['attributes']))
			->setClass($extra['class'])
			->setKey($command->getName())
			->setHref($href)
			->setLabel($label)
			->setTitle($command->getDescription());

		$this->getEnvironment()->getEventPropagator()->propagate(
			$buttonEvent::NAME,
			$buttonEvent,
			array(
				$this->getEnvironment()->getDataDefinition()->getName(),
				$command->getName()
			)
		);

		// Allow to override the button entirely.
		$html = $buttonEvent->getHtml();
		if (!is_null($html))
		{
			return $html;
		}

		// Use the view native button building.
		return sprintf(
			'<a href="%s" class="%s" title="%s"%s>%s</a> ',
			$buttonEvent->getHref(),
			$buttonEvent->getClass(),
			specialchars($buttonEvent->getTitle()),
			$buttonEvent->getAttributes(),
			$buttonEvent->getLabel()
		);
	}

	/**
	 * Generate all buttons for the header of a view.
	 *
	 * @param string $strButtonId The id for the surrounding html div element.
	 *
	 * @return string
	 */
	protected function generateHeaderButtons($strButtonId)
	{
		/** @var CommandInterface[] $globalOperations */
		$globalOperations = $this->getViewSection()->getGlobalCommands()->getCommands();
		$buttons          = array();

		if (!is_array($globalOperations))
		{
			$globalOperations = array();
		}

		// Special case - if select mode active, we must not display the "edit all" button.
		if ($this->isSelectModeActive())
		{
			unset($globalOperations['all']);
		}
		// We do not have the select mode.
		else
		{
			$command = $this->getCreateModelCommand();
			if ($command !== null)
			{
				// New button always first.
				array_unshift($globalOperations, $command);
			}

			$command = $this->getClearClipboardCommand();
			if ($command !== null)
			{
				// Clear clipboard to the end.
				$globalOperations[] = $command;
			}
		}

		$command = $this->getBackCommand();
		if ($command !== null)
		{
			// Back button always to the end.
			$globalOperations[] = $command;
		}

		foreach ($globalOperations as $command)
		{
			$buttons[$command->getName()] = $this->generateHeaderButton($command);
		}

		$buttonsEvent = new GetGlobalButtonsEvent($this->getEnvironment());
		$buttonsEvent->setButtons($buttons);

		$this->getEnvironment()->getEventPropagator()->propagate(
			$buttonsEvent::NAME,
			$buttonsEvent,
			array($this->getEnvironment()->getDataDefinition()->getName())
		);

		return '<div id="' . $strButtonId . '">' . implode('', $buttonsEvent->getButtons()) . '</div>';
	}

	/**
	 * Render a command button.
	 *
	 * @param CommandInterface $objCommand           The command to render the button for.
	 *
	 * @param ModelInterface   $objModel             The model to which the command shall get applied.
	 *
	 * @param bool             $blnCircularReference Determinator if there exists a circular reference between the model
	 *                                               and the model(s) contained in the clipboard.
	 *
	 * @param array            $arrChildRecordIds    List of the ids of all child models of the current model.
	 *
	 * @param ModelInterface   $previous             The previous model in the collection.
	 *
	 * @param ModelInterface   $next                 The next model in the collection.
	 *
	 * @return string
	 */
	protected function buildCommand($objCommand, $objModel, $blnCircularReference, $arrChildRecordIds, $previous, $next)
	{
		$propagator = $this->getEnvironment()->getEventPropagator();

		// Set basic information.
		$opLabel = $objCommand->getLabel();
		if (strlen($opLabel))
		{
			$label = $opLabel;
		}
		else
		{
			$label = $objCommand->getName();
		}

		$label = $this->translate($label, $this->getEnvironment()->getDataDefinition()->getName());

		if (is_array($label))
		{
			$label = $label[0];
		}

		$opDesc = $this->translate($objCommand->getDescription(), $this->getEnvironment()->getDataDefinition()->getName());
		if (strlen($opDesc))
		{
			$title = sprintf($opDesc, $objModel->getID());
		}
		else
		{
			$title = sprintf('%s id %s', $label, $objModel->getID());
		}

		$arrParameters = (array)$objCommand->getParameters();
		$extra         = (array)$objCommand->getExtra();
		$strAttributes = isset($extra['attributes']) ? $extra['attributes'] : null;
		$attributes    = '';

		// Toggle has to trigger the javascript.
		if ($objCommand instanceof ToggleCommandInterface)
		{
			$arrParameters['act'] = $objCommand->getName();

			$attributes = 'onclick="Backend.getScrollOffset(); return BackendGeneral.toggleVisibility(this, \'' . $extra['icon'] . '\', \'' . $extra['icon_disabled'] . '\');"';
			if ($objCommand->isInverse()
					? $objModel->getProperty($objCommand->getToggleProperty())
					: !$objModel->getProperty($objCommand->getToggleProperty())
			)
			{
				$extra['icon'] = $extra['icon_disabled'] ?: 'invisible.gif';
			}
		}

		if (strlen($strAttributes))
		{
			$attributes .= ltrim(sprintf($strAttributes, $objModel->getID()));
		}

		// Cut needs some special information.
		if ($objCommand instanceof CutCommandInterface)
		{
			$arrParameters        = array();
			$arrParameters['act'] = $objCommand->getName();

			// If we have a pid add it, used for mode 4 and all parent -> current views.
			if ($this->getEnvironment()->getInputProvider()->hasParameter('pid'))
			{
				$arrParameters['pid'] = $this->getEnvironment()->getInputProvider()->getParameter('pid');
			}

			// Source is the id of the element which should move.
			$arrParameters['source'] = IdSerializer::fromModel($objModel)->getSerialized();
		}

		// The copy operation.
		elseif ($objCommand instanceof CopyCommandInterface)
		{
			$arrParameters        = array();
			$arrParameters['act'] = $objCommand->getName();

			// If we have a pid add it, used for mode 4 and all parent -> current views.
			if ($this->getEnvironment()->getInputProvider()->hasParameter('pid'))
			{
				$arrParameters['pid'] = $this->getEnvironment()->getInputProvider()->getParameter('pid');
			}

			// Source is the id of the element which should move.
			$arrParameters['source'] = IdSerializer::fromModel($objModel)->getSerialized();
		}

		else
		{
			// TODO: Shall we interface this option?
			$idParam = isset($extra['idparam']) ? $extra['idparam'] : null;
			if ($idParam)
			{
				$arrParameters[$idParam] = IdSerializer::fromModel($objModel)->getSerialized();
			}
			else
			{
				$arrParameters['id'] = IdSerializer::fromModel($objModel)->getSerialized();
			}
		}

		$strHref = '';
		foreach ($arrParameters as $key => $value)
		{
			$strHref .= sprintf('&%s=%s', $key, $value);
		}

		/** @var AddToUrlEvent $event */
		$event = $propagator->propagate(
			ContaoEvents::BACKEND_ADD_TO_URL,
			new AddToUrlEvent($strHref)
		);

		$strHref = $event->getUrl();

		$buttonEvent = new GetOperationButtonEvent($this->getEnvironment());
		$buttonEvent
			->setCommand($objCommand)
			->setObjModel($objModel)
			->setAttributes($attributes)
			->setLabel($label)
			->setTitle($title)
			->setHref($strHref)
			->setChildRecordIds($arrChildRecordIds)
			->setCircularReference($blnCircularReference)
			->setPrevious($previous)
			->setNext($next)
			->setDisabled($objCommand->isDisabled());

		$propagator->propagate(
			$buttonEvent::NAME,
			$buttonEvent,
			array(
				$this->getEnvironment()->getDataDefinition()->getName(),
				$objCommand->getName()
			)
		);

		// If the event created a button, use it.
		if (!is_null($buttonEvent->getHtml()))
		{
			return trim($buttonEvent->getHtml());
		}

		$icon = $extra['icon'];

		if ($buttonEvent->isDisabled())
		{
			/** @var GenerateHtmlEvent $event */
			$event = $propagator->propagate(
				ContaoEvents::IMAGE_GET_HTML,
				new GenerateHtmlEvent(
					substr_replace($icon, '_1', strrpos($icon, '.'), 0),
					$buttonEvent->getLabel()
				)
			);

			return $event->getHtml();
		}

		/** @var GenerateHtmlEvent $event */
		$event = $propagator->propagate(
			ContaoEvents::IMAGE_GET_HTML,
			new GenerateHtmlEvent(
				$icon,
				$buttonEvent->getLabel()
			)
		);

		return sprintf(' <a href="%s" title="%s" %s>%s</a>',
			$buttonEvent->getHref(),
			specialchars($buttonEvent->getTitle()),
			$buttonEvent->getAttributes(),
			$event->getHtml()
		);
	}

	/**
	 * Render the paste into button.
	 *
	 * @param GetPasteButtonEvent $event The event that has been triggered.
	 *
	 * @return string
	 */
	public function renderPasteIntoButton(GetPasteButtonEvent $event)
	{
		if (!is_null($event->getHtmlPasteInto()))
		{
			return $event->getHtmlPasteInto();
		}

		$strLabel = $this->translate('pasteinto.0', $event->getModel()->getProviderName());
		if ($event->isPasteIntoDisabled())
		{
			/** @var GenerateHtmlEvent $imageEvent */
			$imageEvent = $this->getEnvironment()->getEventPropagator()->propagate(
				ContaoEvents::IMAGE_GET_HTML,
				new GenerateHtmlEvent(
					'pasteinto_.gif',
					$strLabel,
					'class="blink"'
				)
			);

			return $imageEvent->getHtml();
		}

		/** @var GenerateHtmlEvent $imageEvent */
		$imageEvent = $this->getEnvironment()->getEventPropagator()->propagate(
			ContaoEvents::IMAGE_GET_HTML,
			new GenerateHtmlEvent(
				'pasteinto.gif',
				$strLabel,
				'class="blink"'
			)
		);

		$opDesc = $this->translate('pasteinto.1', $this->getEnvironment()->getDataDefinition()->getName());
		if (strlen($opDesc))
		{
			$title = sprintf($opDesc, $event->getModel()->getId());
		}
		else
		{
			$title = sprintf('%s id %s', $strLabel, $event->getModel()->getId());
		}

		return sprintf(' <a href="%s" title="%s" %s>%s</a>',
				$event->getHrefInto(),
				specialchars($title),
				'onclick="Backend.getScrollOffset()"',
				$imageEvent->getHtml()
			);
	}

	/**
	 * Render the paste after button.
	 *
	 * @param GetPasteButtonEvent $event The event that has been triggered.
	 *
	 * @return string
	 */
	public function renderPasteAfterButton(GetPasteButtonEvent $event)
	{
		if (!is_null($event->getHtmlPasteAfter()))
		{
			return $event->getHtmlPasteAfter();
		}

		$strLabel = $this->translate('pasteafter.0', $event->getModel()->getProviderName());
		if ($event->isPasteAfterDisabled())
		{
			/** @var GenerateHtmlEvent $imageEvent */
			$imageEvent = $this->getEnvironment()->getEventPropagator()->propagate(
				ContaoEvents::IMAGE_GET_HTML,
				new GenerateHtmlEvent(
					'pasteafter_.gif',
					$strLabel,
					'class="blink"'
				)
			);

			return $imageEvent->getHtml();
		}

		/** @var GenerateHtmlEvent $imageEvent */
		$imageEvent = $this->getEnvironment()->getEventPropagator()->propagate(
			ContaoEvents::IMAGE_GET_HTML,
			new GenerateHtmlEvent(
				'pasteafter.gif',
				$strLabel,
				'class="blink"'
			)
		);

		$opDesc = $this->translate('pasteafter.1', $this->getEnvironment()->getDataDefinition()->getName());
		if (strlen($opDesc))
		{
			$title = sprintf($opDesc, $event->getModel()->getId());
		}
		else
		{
			$title = sprintf('%s id %s', $strLabel, $event->getModel()->getId());
		}

		return sprintf(' <a href="%s" title="%s" %s>%s</a>',
			$event->getHrefAfter(),
			specialchars($title),
			'onclick="Backend.getScrollOffset()"',
			$imageEvent->getHtml()
		);
	}

	/**
	 * Compile buttons from the table configuration array and return them as HTML.
	 *
	 * @param ModelInterface $model    The model for which the buttons shall be generated for.
	 * @param ModelInterface $previous The previous model in the collection.
	 * @param ModelInterface $next     The next model in the collection.
	 * @return string
	 */
	protected function generateButtons(
		ModelInterface $model,
		ModelInterface $previous = null,
		ModelInterface $next = null
	)
	{
		$commands     = $this->getViewSection()->getModelCommands();
		$objClipboard = $this->getEnvironment()->getClipboard();
		$propagator   = $this->getEnvironment()->getEventPropagator();

		if ($this->getEnvironment()->getClipboard()->isNotEmpty())
		{
			$circularIds = $objClipboard->getCircularIds();
			$isCircular  = in_array(IdSerializer::fromModel($model)->getSerialized(), $circularIds);
		}
		else
		{
			$circularIds = array();
			$isCircular  = false;
		}

		$arrButtons = array();
		foreach ($commands->getCommands() as $command)
		{
			$arrButtons[$command->getName()] = $this->buildCommand(
				$command,
				$model,
				$isCircular,
				$circularIds,
				$previous,
				$next
			);
		}

		if ($this->getManualSortingProperty() &&
			$objClipboard->isEmpty() &&
			$this->getDataDefinition()->getBasicDefinition()->getMode() != BasicDefinitionInterface::MODE_HIERARCHICAL
		)
		{
			/** @var AddToUrlEvent $urlEvent */
			$urlEvent = $propagator->propagate(
				ContaoEvents::BACKEND_ADD_TO_URL,
				new AddToUrlEvent(
					'act=create&amp;after=' . IdSerializer::fromModel($model)->getSerialized()
				)
			);

			/** @var GenerateHtmlEvent $imageEvent */
			$imageEvent = $propagator->propagate(
				ContaoEvents::IMAGE_GET_HTML,
				new GenerateHtmlEvent(
					'new.gif',
					$this->translate('pastenew.0', $this->getDataDefinition()->getName())
				)
			);

			$arrButtons['pasteNew'] = sprintf(
				'<a href="%s" title="%s" onclick="Backend.getScrollOffset()">%s</a>',
				$urlEvent->getUrl(),
				specialchars($this->translate('pastenew.1', $this->getDataDefinition()->getName())),
				$imageEvent->getHtml()
			);
		}

		// Add paste into/after icons.
		if ($objClipboard->isNotEmpty())
		{
			if ($objClipboard->isCreate())
			{
				// Add ext. information.
				$add2UrlAfter = sprintf('act=create&after=%s&',
					IdSerializer::fromModel($model)->getSerialized()
				);

				$add2UrlInto = sprintf('act=create&into=%s&',
					IdSerializer::fromModel($model)->getSerialized()
				);
			}
			else
			{
				// Add ext. information.
				$add2UrlAfter = sprintf('act=paste&after=%s&',
					IdSerializer::fromModel($model)->getSerialized()
				);

				$add2UrlInto = sprintf('act=paste&into=%s&',
					IdSerializer::fromModel($model)->getSerialized()
				);
			}

			/** @var AddToUrlEvent $urlAfter */
			$urlAfter = $propagator->propagate(
				ContaoEvents::BACKEND_ADD_TO_URL,
				new AddToUrlEvent($add2UrlAfter)
			);

			/** @var AddToUrlEvent $urlInto */
			$urlInto = $propagator->propagate(
				ContaoEvents::BACKEND_ADD_TO_URL,
				new AddToUrlEvent($add2UrlInto)
			);

			$buttonEvent = new GetPasteButtonEvent($this->getEnvironment());
			$buttonEvent
				->setModel($model)
				->setCircularReference($isCircular)
				->setPrevious($previous)
				->setNext($next)
				->setHrefAfter($urlAfter->getUrl())
				->setHrefInto($urlInto->getUrl())
				// Check if the id is in the ignore list.
				->setPasteAfterDisabled($objClipboard->isCut() && $isCircular)
				->setPasteIntoDisabled($objClipboard->isCut() && $isCircular)
				->setContainedModels($this->getModelsFromClipboard());

			$this->getEnvironment()->getEventPropagator()->propagate(
				$buttonEvent::NAME,
				$buttonEvent,
				array($this->getEnvironment()->getDataDefinition()->getName())
			);

			$arrButtons['pasteafter'] = $this->renderPasteAfterButton($buttonEvent);
			if ($this->getDataDefinition()->getBasicDefinition()->getMode() == BasicDefinitionInterface::MODE_HIERARCHICAL)
			{
				$arrButtons['pasteinto'] = $this->renderPasteIntoButton($buttonEvent);
			}

		}

		return implode(' ', $arrButtons);
	}

	/**
	 * Render the panel.
	 *
	 * @param array $ignoredPanels A list with ignored elements [Optional].
	 *
	 * @throws DcGeneralRuntimeException When no information of panels can be obtained from the data container.
	 *
	 * @return string
	 */
	protected function panel($ignoredPanels = array())
	{
		if ($this->getPanel() === null)
		{
			throw new DcGeneralRuntimeException('No panel information stored in data container.');
		}

		$arrPanels = array();
		foreach ($this->getPanel() as $objPanel)
		{
			$arrPanel = array();
			foreach ($objPanel as $objElement)
			{
				// If the current class in the list of ignored panels go to the next one.
				if (!empty($ignoredPanels) && $this->isIgnoredPanel($objElement, $ignoredPanels))
				{
					continue;
				}

				$objElementTemplate = null;
				if ($objElement instanceof FilterElementInterface)
				{
					$objElementTemplate = $this->getTemplate('dcbe_general_panel_filter');
				}
				elseif ($objElement instanceof LimitElementInterface)
				{
					$objElementTemplate = $this->getTemplate('dcbe_general_panel_limit');
				}
				elseif ($objElement instanceof SearchElementInterface)
				{
					$objElementTemplate = $this->getTemplate('dcbe_general_panel_search');
				}
				elseif ($objElement instanceof SortElementInterface)
				{
					$objElementTemplate = $this->getTemplate('dcbe_general_panel_sort');
				}
				elseif ($objElement instanceof SubmitElementInterface)
				{
					$objElementTemplate = $this->getTemplate('dcbe_general_panel_submit');
				}
				$objElement->render($objElementTemplate);

				$arrPanel[] = $objElementTemplate->parse();
			}
			$arrPanels[] = $arrPanel;
		}

		if (count($arrPanels))
		{
			$objTemplate = $this->getTemplate('dcbe_general_panel');
			$themeEvent  = new GetThemeEvent();

			$this->getEnvironment()->getEventPropagator()->propagate(ContaoEvents::BACKEND_GET_THEME, $themeEvent);

			$this
				->addToTemplate(
					'action',
					ampersand($this->getEnvironment()->getInputProvider()->getRequestUrl(), true),
					$objTemplate
				)
				->addToTemplate('theme', $themeEvent->getTheme(), $objTemplate)
				->addToTemplate('panel', $arrPanels, $objTemplate);

			return $objTemplate->parse();
		}

		return '';
	}

	/**
	 * Check if the current element is in the ignored list.
	 *
	 * @param PanelElementInterface $objElement    A panel Element.
	 *
	 * @param array                 $ignoredPanels A list with ignored elements.
	 *
	 * @return boolean True => Element is on the ignored list. | False => Nope not in the list.
	 */
	protected function isIgnoredPanel(PanelElementInterface $objElement, $ignoredPanels)
	{
		foreach ((array)$ignoredPanels as $class)
		{
			if ($objElement instanceof $class)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the breadcrumb navigation via event.
	 *
	 * @return string
	 */
	protected function breadcrumb()
	{
		$event = new GetBreadcrumbEvent($this->getEnvironment());

		$this->getEnvironment()->getEventPropagator()->propagate(
			$event::NAME,
			$event,
			array($this->getEnvironment()->getDataDefinition()->getName())
		);

		$arrReturn = $event->getElements();

		if (!is_array($arrReturn) || count($arrReturn) == 0)
		{
			return null;
		}

		$GLOBALS['TL_CSS'][] = 'system/modules/dc-general/html/css/generalBreadcrumb.css';

		$objTemplate = $this->getTemplate('dcbe_general_breadcrumb');
		$this->addToTemplate('elements', $arrReturn, $objTemplate);

		return $objTemplate->parse();
	}

	/**
	 * Process input and return all modified properties or null if there is no input.
	 *
	 * @param ContaoWidgetManager $widgetManager The widget manager in use.
	 *
	 * @return null|PropertyValueBag
	 */
	public function processInput($widgetManager)
	{
		$input = $this->getEnvironment()->getInputProvider();

		if ($input->getValue('FORM_SUBMIT') == $this->getEnvironment()->getDataDefinition()->getName())
		{
			$propertyValues = new PropertyValueBag();
			$propertyNames  = $this->getEnvironment()->getDataDefinition()->getPropertiesDefinition()->getPropertyNames();

			// Process input and update changed properties.
			foreach ($propertyNames as $propertyName)
			{
				if ($input->hasValue($propertyName))
				{
					$propertyValue = $input->getValue($propertyName, true);
					$propertyValues->setPropertyValue($propertyName, $propertyValue);
				}
			}
			$widgetManager->processInput($propertyValues);

			return $propertyValues;
		}

		return null;
	}

	/**
	 * Format a model accordingly to the current configuration.
	 *
	 * Returns either an array when in tree mode or a string in (parented) list mode.
	 *
	 * @param ModelInterface $model The model that shall be formatted.
	 *
	 * @return array
	 */
	public function formatModel(ModelInterface $model)
	{
		$listing      = $this->getViewSection()->getListingConfig();
		$properties   = $this->getDataDefinition()->getPropertiesDefinition();
		$formatter    = $listing->getLabelFormatter($model->getProviderName());
		$sorting           = $this->getGroupingMode();
		$sortingDefinition = $sorting['sorting'];
		$firstSorting = '';

		if ($sortingDefinition)
		{
			/** @var GroupAndSortingDefinitionInterface $sortingDefinition */
			foreach ($sortingDefinition as $information)
			{
				/** @var GroupAndSortingInformationInterface $information */
				if ($information->getProperty())
				{
					$firstSorting = reset($sorting);
					break;
				}
			}
		}

		$args = array();
		foreach ($formatter->getPropertyNames() as $propertyName)
		{
			if ($properties->hasProperty($propertyName))
			{
				$property = $properties->getProperty($propertyName);

				$args[$propertyName] = (string)$this->getReadableFieldValue($property, $model, $model->getProperty($propertyName));
			}
			else
			{
				$args[$propertyName] = '-';
			}
		}

		$event = new ModelToLabelEvent($this->getEnvironment(), $model);
		$event
			->setArgs($args)
			->setLabel($formatter->getFormat())
			->setFormatter($formatter);

		$this->getEnvironment()->getEventPropagator()->propagate(
			$event::NAME,
			$event,
			array($this->getEnvironment()->getDataDefinition()->getName())
		);

		$arrLabel = array();

		// Add columns.
		if ($listing->getShowColumns())
		{
			$fields = $formatter->getPropertyNames();
			$args   = $event->getArgs();

			if (!is_array($args))
			{
				$arrLabel[] = array(
					'colspan' => count($fields),
					'class' => 'tl_file_list col_1',
					'content' => $args
				);
			}
			else
			{
				foreach ($fields as $j => $propertyName)
				{
					$arrLabel[] = array(
						'colspan' => 1,
						'class' => 'tl_file_list col_' . $j . (($propertyName == $firstSorting) ? ' ordered_by' : ''),
						'content' => (($args[$propertyName] != '') ? $args[$propertyName] : '-')
					);
				}
			}
		}
		else
		{
			if (!is_array($event->getArgs()))
			{
				$string = $event->getArgs();
			}
			else
			{
				$string = vsprintf($event->getLabel(), $event->getArgs());
			}

			if ($formatter->getMaxLength() !== null && strlen($string) > $formatter->getMaxLength())
			{
				$string = substr($string, 0, $formatter->getMaxLength());
			}

			$arrLabel[] = array(
				'colspan' => null,
				'class'   => 'tl_file_list',
				'content' => $string
			);
		}

		return $arrLabel;
	}

	/**
	 * Get for a field the readable value.
	 *
	 * @param PropertyInterface $property The property to be rendered.
	 *
	 * @param ModelInterface    $model    The model from which the property value shall be retrieved from.
	 *
	 * @param mixed             $value    The value for the property.
	 *
	 * @return mixed
	 */
	public function getReadableFieldValue(PropertyInterface $property, ModelInterface $model, $value)
	{
		$event = new RenderReadablePropertyValueEvent($this->getEnvironment(), $model, $property, $value);
		$this->getEnvironment()->getEventPropagator()->propagate(
			$event::NAME,
			$event,
			array(
				$this->getEnvironment()->getDataDefinition()->getName(),
				$property->getName()
			)
		);

		if ($event->getRendered() !== null)
		{
			return $event->getRendered();
		}

		return $value;
	}
}
