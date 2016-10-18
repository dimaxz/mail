<?php

namespace Mailbox;

/**
 * Description of Attachments
 *
 * @author d.lanec
 */
class Attachment {

	protected $id;

	protected $name;

	protected $dirpath;

	protected $filepath;

	/**
	 * 
	 * @param type $id уникальный номер вложения
	 * @param type $name имя вложения
	 * @param type $path путь к каталогу для сохранения вложения
	 * @param type $data содержимое вложния
	 */
	function __construct($id, $name, $dirpath, $data) {
		$this
				->setName($name)
				->setDirpath($dirpath)
				->setId($id)
		;

		$this->createTmpFile($data);
	}

	function getId() {
		return $this->id;
	}

	function getName() {
		return $this->name;
	}

	function setId($id) {
		$this->id = $id;
		return $this;
	}

	function setName($name) {
		$this->name = $name;
		return $this;
	}
	
	function getDirpath() {
		return $this->dirpath;
	}

	function getFilepath() {
		return $this->filepath;
	}

	function setDirpath($dirpath) {
		$this->dirpath = $dirpath;
		return $this;
	}

	function setFilepath($filepath) {
		$this->filepath = $filepath;
		return $this;
	}

	/**
	 * Создание темпового файла
	 * @param type $data
	 * @return boolean
	 */
	private function createTmpFile($data) {

		$this->filepath = rtrim($this->dirpath, '/') . '/' . $this->getId() . '_'. strtolower(preg_replace('~[^A-Za-z0-9\.]~', '',  $this->getName() ));

		file_put_contents($this->filepath, $data);

		return true;
	}

}
