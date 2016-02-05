<?php

use FOF30\Container\Container as Container;

/**
 * A helper class to generate language strings from XML forms and PHP Files
 */
class LanguageFiles
{
	protected $baseString = '';

	public function buildComponent($component)
	{
		$sides = ['admin', 'site'];
		$languages = array_keys(\JLanguage::getKnownLanguages());

		foreach ($sides as $side) 
		{
			$strings = $this->getStrings($side, $component);

			foreach ($languages as $lang)
			{
				$this->saveStrings($strings, $component, $lang, $side);
			}
		}
	}

	/**
	 * Fetches the strings from a components language file
	 */
	protected function getStrings($side, $component)
	{
		$componentPath = 'components/' . strtolower($component) . '/';
		$path = $side == 'admin' ? 'administrator/' . $componentPath : $componentPath;

		$xmls = \JFolder::files($path, $filter = '.xml', $recurse = true, $full = true);
		$phps = \JFolder::files($path, $filter = '.php', $recurse = true, $full = true);

		// Visit each php file for JText calls
		$visitor = new LanguageFiles\PhpNodeVisitor();
		$results = $visitor->traverse($phps);

		// Visit each xml file for strings starting
		foreach ($xmls as $file)
		{
			$xml = \JFile::read($file);
			try
			{
				$xml = @new \SimpleXMLElement($xml);
				$results = array_merge($results, $this->parseXml($xml, $file, $component));
			}
			catch(\Exception $e)
			{

			}
		}

		return $results;
	}

	/**
	 * Saves the language strings, merged with any old ones, to a Joomla! INI language file
	 */
	protected function saveStrings($newStrings, $component, $language, $side)
	{
		$baseString = strtoupper($component . '_');

		// If no filename is defined, get the component's language definition filename
		if (empty($targetFilename))
		{
			$basePath = $side == 'admin' ? JPATH_ADMINISTRATOR : JPATH_SITE;

			$jLang = \JFactory::getLanguage();
			$lang = $jLang->setLanguage($language);
			$jLang->setLanguage($lang);

			$path = $jLang->getLanguagePath($basePath, $lang);

			$targetFilename = $path . '/' . $lang . '.' . strtolower($component) . '.ini';
		}

		// Try to load the existing language file
		$strings = array();

		if (@file_exists($targetFilename))
		{
			$contents = file_get_contents($targetFilename);
			$contents = str_replace('_QQ_', '"\""', $contents);
			$strings = @parse_ini_string($contents);
		} else {
			\JFile::write($targetFilename, '');
		}

		foreach ($newStrings as $k => &$v) {
			$v = str_ireplace($baseString, '', $k);
			$parts = explode("_", $v);
			
			foreach ($parts as &$part)
			{
				$part = ucfirst(strtolower($part));
			}
			
			$v = implode(" ", $parts);
		}

		if(!is_array($strings))
		{
			return;
		}

		$strings = array_merge($newStrings, $strings);

		// Create the INI file
		$iniFile = '';

		foreach ($strings as $k => $v)
		{
			$iniFile .= strtoupper(trim($k)) . '="' . str_replace('"', '"_QQ_"', trim($v)) . "\"\n";
		}

		// Save it
		$saveResult = @file_put_contents($targetFilename, $iniFile);

		if ($saveResult === false)
		{
			\JLoader::import('joomla.filesystem.file');
			\JFile::write($targetFilename, $iniFile);
		}
	}

	/**
	 * Gets the strings from a view XML file
	 */
	protected function parseXml($xml, $file, $component)
	{
		$baseString = strtoupper($component . '_');

		$results = array();

		foreach ($xml->attributes() as $key => $value) 
		{
			if (stripos($value, $baseString) !== false)
			{
				$results[(string) $value][] = ['file' => $file];
			}
		}

		if ($xml->count() > 0)
		{
			foreach ($xml->children() as $child) 
			{
				$results = array_merge($results, $this->parseXml($child, $file, $component));
			}
		}
		else 
		{
			if (stripos($xml, $baseString) !== false)
			{
				$results[(string) $xml][] = ['file' => $file];
			}
		}

		return $results;
	}
}