<?php

namespace Mailbox;

/**
 * Description of MailSource
 *
 * @author d.lanec
 */
class MailSource {

	protected $host;

	protected $login;

	protected $port;

	protected $password;

	protected $ssl = true;

	/**
	 *
	 * @var Eden\Mail\Imap $imap
	 */
	protected $imap;

	protected $mailboxes = [];

	function __construct($host, $login, $password, $port = 993, $ssl = true) {

		$this
				->setHost($host)
				->setLogin($login)
				->setPassword($password)
				->setPort($port)
				->setPassword($password)
		;
	}

	public function getHost() {
		return $this->host;
	}

	public function getLogin() {
		return $this->login;
	}

	public function getPort() {
		return $this->port;
	}

	public function getPassword() {
		return $this->password;
	}

	public function getSsl() {
		return $this->ssl;
	}

	public function setHost($host) {
		$this->host = $host;
		return $this;
	}

	public function setLogin($login) {
		$this->login = $login;
		return $this;
	}

	public function setPort($port) {
		$this->port = $port;
		return $this;
	}

	public function setPassword($password) {
		$this->password = $password;
		return $this;
	}

	public function setSsl($ssl) {
		$this->ssl = $ssl;
		return $this;
	}

	/**
	 * Получение списка каталогов почты
	 * @return \Mailbox\MailBox
	 */
	public function getMailBoxes() {
		$mailboxes = [];

		if(count($this->mailboxes))
			return $this->mailboxes;
		
		$boxes = $this->getConnection()->getMailboxes();
		
		foreach ($boxes as $id => $box) {
			$mailboxes [] = (new MailBox($this->getConnection(), $id, $box["name"]))->setParent($box["parent"]);
		}

		return $mailboxes;
	}

	/**
	 ** Отдаем почтовый ящик
	 * @param type $name
	 * @return \Mailbox\MailBox $MailBox
	 */
	public function getMailBox($name = "INBOX") {
		foreach($this->getMailBoxes() as $Mailbox){
			if($Mailbox->getName()==$name){
				$this->imap->setActiveMailbox($Mailbox->getId());
				return $Mailbox;
			}
		}
		return null;
	}

	/**
	 * получение коннекта
	 * @return type
	 */
	private function getConnection() {

		if (!$this->imap) {
			$this->imap = Base\Index::imap(
					$this->getHost(), $this->getLogin(), $this->getPassword(), $this->getPort(), $this->getSsl()
			);
		}

		return $this->imap;
	}

}
