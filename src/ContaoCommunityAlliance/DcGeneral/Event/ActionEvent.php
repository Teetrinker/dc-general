<?php
/**
 * PHP version 5
 *
 * @package    generalDriver
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace ContaoCommunityAlliance\DcGeneral\Event;

use ContaoCommunityAlliance\DcGeneral\Action;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\View\CommandInterface;
use ContaoCommunityAlliance\DcGeneral\EnvironmentInterface;

/**
 * This event occurs when an action should handled.
 *
 * @package DcGeneral\Event
 */
class ActionEvent extends AbstractActionAwareEvent
{
	/**
	 * The action response, if any is set.
	 *
	 * @var string
	 */
	protected $response;

	/**
	 * Set the action response.
	 *
	 * @param string $response The response.
	 *
	 * @return ActionEvent
	 */
	public function setResponse($response)
	{
		$this->response = $response !== null ? (string)$response : null;
		return $this;
	}

	/**
	 * Return the action response.
	 *
	 * @return string
	 */
	public function getResponse()
	{
		return $this->response;
	}
}
