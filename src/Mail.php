<?php

namespace Mailbox;

/**
 * Объект письма
 *
 * @author d.lanec
 */
class Mail {

	protected $uid;
	
	protected $message_id;

	protected $from;

	protected $subject;

	protected $datetime;

	protected $are_attachments = false;

	protected $attachments = [];

	protected $body;

	/**
	 *
	 * @var \Eden\Mail\Imap $imap 
	 */
	private $imap;

	function __construct(\Eden\Mail\Imap $imap, $uid, \Datetime $datetime, $subject, $from) {

		$this->imap = $imap;

		$this
				->setUid($uid)
				->setDatetime($datetime)
				->setSubject($subject)
				->setFrom($from)
		;
	}

	function getMessage_id() {
		return $this->message_id;
	}

	function setMessage_id($message_id) {
		$this->message_id = $message_id;
		return $this;
	}
	
	/**
	 * Получение вложений
	 * @return type
	 */
	function getAttachments(SearchCriteria $Criteria) {

		if (count($this->attachments)) {
			return $this->attachments;
		}

		$this->loadBodyAndAttach($Criteria);

		return $this->attachments;
	}

	function getDatetime() {
		return $this->datetime;
	}

	/**
	 * Получение тела письма
	 * @return type
	 */
	function getBody() {

		if (empty($this->body)) {
			$this->loadBodyAndAttach();
		}

		return $this->body;
	}

	/**
	 * служебный метод получения содержимого и вложений
	 * @return $this
	 */
	public function loadBodyAndAttach(SearchCriteria $Criteria) {
		$res = $this->imap->getUniqueEmails($this->getUid(), true);

		//страхуемся от неоотсветствия uid
		if($res['uid']!=$this->getUid()) return $this;

		$this->body = $res['body'];

		foreach ((array) $res['attachment'] as $name => $body) {
			if(count($Criteria->getAttachment_ext()) && preg_match('~.*\.(.*?)$~', $name ,$match) && !in_array($match[1],$Criteria->getAttachment_ext()) ){
				continue;
			}

			$this->attachments[] = new Attachment(
					md5($this->getUid() . $name), Helper::decodeString($name), $_SERVER['DOCUMENT_ROOT'] . Config::getTmpDir(), $body);
		}

		return $this;
	}

	function getUid() {
		return $this->uid;
	}

	function getFrom() {
		return $this->from;
	}

	function getSubject() {
		return $this->subject;
	}

	function getAre_attachments() {
		return $this->are_attachments;
	}

	function setDatetime(\Datetime $datetime) {
		$this->datetime = $datetime;
		return $this;
	}

	function setUid($uid) {
		$this->uid = $uid;
		return $this;
	}

	function setFrom($from) {
		$this->from = $from;
		return $this;
	}

	function setSubject($subject) {
		$this->subject = $subject;
		return $this;
	}

	function setAre_attachments($are_attachments) {
		$this->are_attachments = $are_attachments;
		return $this;
	}

}
