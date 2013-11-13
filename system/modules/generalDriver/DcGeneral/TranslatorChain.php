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

namespace DcGeneral;

class TranslatorChain implements TranslatorInterface
{
	/**
	 * @var TranslatorInterface[]
	 */
	protected $translators = array();

	public function clear()
	{
		$this->translators = array();
	}

	public function addAll(array $translators)
	{
		foreach ($translators as $translator) {
			$this->add($translator);
		}
	}

	public function add(TranslatorInterface $translator)
	{
		$hash = spl_object_hash($translator);
		$this->translators[$hash] = $translator;
	}

	public function remove(TranslatorInterface $translator)
	{
		$hash = spl_object_hash($translator);
		unset($this->translators[$hash]);
	}

	public function getAll()
	{
		return array_values($this->translators);
	}

	/**
	 * {@inheritdoc}
	 */
	public function translate($string, $domain = null)
	{
		$original = $string;

		for (
			$translator = reset($this->translators);
			$string == $original;
			$translator = next($this->translators)
		) {
			$string = $translator->translate($string, $domain);
		}

		return $string;
	}
}
