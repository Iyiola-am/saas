<?php
namespace Cradle;

/**
 * Acts as an abstract representation of a view file.
 */
class View
{
	/** @var string $filePath Holds the file path to the view file */
	protected $filePath;

	/** @var array $parameters Holds the parameters to be passed into the view file for dynamic page rendering */
	protected $parameters;

	/**
	 * @param string $file The file path relative to the views directory
	 * @param array @params An associative array of parameters to be passed into the view file at compile time
	 */
	public function __construct(string $file, array $params = [])
	{
		$this->filePath = $file;
		$this->parameters = $params;
	}

	/**
	 * Gets the file path relative to the views directory
	 * 
	 * @return string The file path relative to the views directory
	 */
	public function getRelativeFilePath(): string
	{
		return $this->filePath;
	}

	/**
	 * Gets the absolute file path of the view file relative to the document root.
	 * 
	 * @return string The complete file path to the view file
	 */
	public function getFullFilePath(): string
	{
		return RESOURCES_DIR . '/views/' . $this->filePath;
	}

	/**
	 * Gets the parameters to be passed into the view file.
	 * 
	 * @return array The array of parameters to be passed into the view
	 */
	public function getParameters(): array
	{
		return $this->parameters;
	}
}
