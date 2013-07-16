<?php
/**
 * PHP version 5
 * @package    generalDriver
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace DcGeneral\Panel;

use DcGeneral\Data\Interfaces\Config;
use DcGeneral\Data\Interfaces\Model;
use DcGeneral\Panel\AbstractElement;
use DcGeneral\Panel\Interfaces\Element;
use DcGeneral\Panel\Interfaces\FilterElement;

class BaseFilterElement extends AbstractElement implements FilterElement
{
	/**
	 * @var string
	 */
	protected $strProperty;

	/**
	 * @var mixed
	 */
	protected $mixValue;

	protected $arrfilterOptions;


	protected function getPersistent()
	{
		$arrValue = array();
		if ($this->getInputProvider()->hasPersistentValue('filter'))
		{
			$arrValue = $this->getInputProvider()->getPersistentValue('filter');
		}

		if (array_key_exists($this->getDataContainer()->getName(), $arrValue))
		{
			$arrValue = $arrValue[$this->getDataContainer()->getName()];

			if (array_key_exists($this->getPropertyName(), $arrValue))
			{
				return $arrValue[$this->getPropertyName()];
			}
		}

		return null;
	}

	protected function setPersistent($strValue)
	{
		$arrValue = array();

		if ($this->getInputProvider()->hasPersistentValue('filter'))
		{
			$arrValue = $this->getInputProvider()->getPersistentValue('filter');
		}

		if (!is_array($arrValue[$this->getDataContainer()->getName()]))
		{
			$arrValue[$this->getDataContainer()->getName()] = array();
		}

		if ($strValue)
		{
			$arrValue[$this->getDataContainer()->getName()][$this->getPropertyName()] = $strValue;
		}
		else
		{
			unset($arrValue[$this->getDataContainer()->getName()][$this->getPropertyName()]);
		}

		$this->getInputProvider()->setPersistentValue('search', $arrValue);
	}

	/**
	 * {@inheritDoc}
	 */
	public function initialize(Config $objConfig, Element $objElement = null)
	{
		$input = $this->getInputProvider();
		$value = null;

		if ($this->getPanel()->getContainer()->updateValues() && $input->hasValue($this->getPropertyName()))
		{
			$value = $input->getValue($this->getPropertyName());

			$this->setPersistent($value);
		}
		elseif ($input->hasPersistentValue('tl_sort'))
		{
			$persistent = $this->getPersistent();
			if ($persistent)
			{
				$value = $persistent;
			}
		}

		if (!is_null($value))
		{
			$this->setValue($value);
		}

		if ($this->getPropertyName() && $this->getValue())
		{
			$arrCurrent = $objConfig->getFilter();
			if (!is_array($arrCurrent))
			{
				$arrCurrent = array();
			}

			$objConfig->setFilter(array_merge_recursive(
				$arrCurrent,
				array(
					array(
/*						'operation' => 'AND',
						'childs' => array(array(
*/
							'operation' => '=',
							'property' => $this->getPropertyName(),
							'value' => $this->getValue()
/*
						))
*/
					)
				)
			));
		}

		// Finally load the filter options.
		if (is_null($objElement))
		{
			$objTempConfig = $this->getOtherConfig($objConfig);
			$objTempConfig->setFields(array($this->getPropertyName()));

			$objFilterOptions = $this
				->getPanel()
				->getContainer()
				->getDataContainer()
				->getDataProvider()
				->getFilterOptions($objTempConfig);

			$arrOptions = array();
			/**
			 * @var Model $objOption
			 */
			foreach ($objFilterOptions as $objOption)
			{
				$arrOptions[] = $objOption->getProperty($this->getPropertyName());
			}
			$this->arrfilterOptions = $arrOptions;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function render($objTemplate)
	{
		$arrLabel = $this->getDataContainer()->getDataDefinition()->getProperty($this->getPropertyName())->getName();

		// Begin select menu
		$arrFilter = array(
			'select' => array(
				'name' => $this->getPropertyName(),
				'id' => $this->getPropertyName(),
				'class' => 'tl_select' . (is_null($this->getValue()) ? ' active' : '')
			),
			'option' => array(
				array(
					'value' => 'tl_' . $this->getPropertyName(),
					'content' => (is_array($arrLabel) ? $arrLabel[0] : $arrLabel)
				),
				array(
					'value' => 'tl_' . $this->getPropertyName(),
					'content' => '---'
				)
			)
		);


		var_dump($this->arrfilterOptions);
	}

	/**
	 * {@inheritDoc}
	 */
	public function setPropertyName($strProperty)
	{
		$this->strProperty = $strProperty;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPropertyName()
	{
		return $this->strProperty;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setValue($mixValue)
	{
		$this->mixValue = $mixValue;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getValue()
	{
		return $this->mixValue;
	}
}
