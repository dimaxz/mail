<?php

namespace Mailbox;

use Mailbox\SearchCriteria;

/**
 * Description of MailBox
 *
 * @author d.lanec
 */
class MailBox {

	protected $id;

	protected $name;

	protected $count;

	protected $mails;

	protected $imap;

	protected $parent;

	function __construct(\Eden\Mail\Imap $imap, $id, $name) {
		$this->imap = $imap;
		$this
				->setId($id)
				->setName($name)
		;
	}

	function getParent() {
		return $this->parent;
	}

	function setParent($parent) {
		$this->parent = $parent;
		return $this;
	}

	public function getId() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function getCount() {
		if(!$thhis->count)
			$this->count = $this->imap->getEmailTotal();
		
		return $this->count;
	}

	public function setId($id) {
		$this->id = $id;
		return $this;
	}

	public function setName($name) {
		$this->name = iconv('UTF-7', 'UTF-8', str_replace(',', '/', str_replace('&', '+', $name)));
		return $this;
	}

	/**
	 * Получение писем с лимитами
	 * @param type $start
	 * @param type $limit
	 * @return type
	 */
	public function getMails($start = 0, $limit = 1000) {

		$key = md5($start . $limit);
		
		if (count($this->mails[$key])) {
			return $this->mails[$key];
		}

		foreach ($this->imap->getEmails($start, $limit ) as $email) {
			
			$this->mails[$key] [] = (new Mail(
					$this->imap, 
					$email['uid'], 
					(new \Datetime)->setTimestamp($email['date']), 
					Helper::decodeMimeStr($email['topic']), 
					$email['from']['email'])
					)
					->setAre_attachments($email['attachment'])
			;
		}

		return $this->mails[$key];
	}

	/**
	 * поллучение письма по его идентификатору
	 * @param type $uid
	 * @return Mailbox\Mail $Mail
	 */
	public function getMailByUid($uid) {

		foreach ((array) $this->getMails() as $Mail) {
			if ($uid == $Mail->getUid())
				return $Mail;
		}
	}

	/**
	 * поиск писем по криетериям
	 * @param SearchCriteria $Criteria условия поиска
	 * @param type $all поиск по всему ящику, значительно замедляет работу
	 * @param type $one поиск только первого попавшегося письма
	 * @return array|\Mailbox\Mail $Mail
	 */
	public function getMailsByCriteria(SearchCriteria $Criteria,$all = false,$one = false) {
		$find = [];
		
		$i = 0; $step = 1000;
		
         while (true) {

			foreach ((array) $this->getMails($i,$step) as $Mail) {

				if ($Criteria->getFrom() && strtolower($Criteria->getFrom()) != strtolower($Mail->getFrom()))
					continue;

				if ($Criteria->getSubject() && strpos(strtolower($Mail->getSubject()), strtolower($Criteria->getSubject())) === false)
					continue;

				if ($Criteria->getSince() > 0 && $Mail->getDatetime()->getTimestamp() < (new \Datetime($Criteria->getSince()))->getTimestamp())
					continue;

				if ($Criteria->getAttachment()) {

					if ($Mail->getAre_attachments() !== true)
						continue;

					$attachments = $Mail->getAttachments();

					$find_attach = false;

					foreach ((array) $attachments as $Attachment) {

						if (strpos(strtolower($Attachment->getName()), strtolower($Criteria->getAttachment())) !== false) {
							$find_attach = true;
							break;
						}
					}

					if ($find_attach === false)
						continue;
				}

				if($one===true)
					return $Mail;

				$find [] = $Mail;
			}
			
			$i = $i + $step;
			
			if($all===false || $i >= $this->getCount())
				break;
			 
         }
		


		return $find;
	}
	
	/**
	 * 
	 * @param SearchCriteria $Criteria
	 * @param \Mailbox\Mail $Mail
	 */
	public function getMailByCriteria(SearchCriteria $Criteria,$all = false) {
		return $this->getMailsByCriteria($Criteria, $all, true);
	}

}
