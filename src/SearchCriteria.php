<?php

namespace Mailbox;

/**
 * Description of SearchCriteria
 *
 * @author d.lanec
 */
class SearchCriteria {

	protected $from;

	protected $subject;

	protected $since;

	/**
	 * фильтро по имени вложения
	 * @var type 
	 */
	protected $attachment;
	
	/**
	 * фильтро по имени вложения с использованием регулярки
	 * @var type 
	 */
	protected $attachment_reg;
	
	/**
	 * фильтр по расшитрению вложения
	 * @var type 
	 */
	protected $attachment_ext;

	public function getFrom() {
		return $this->from;
	}

	public function getSubject() {
		return $this->subject;
	}

	public function getSince() {
		return $this->since;
	}

	public function setFrom($from) {
		$this->from = $from;
		return $this;
	}

	public function setSubject($subject) {
		$this->subject = $subject;
		return $this;
	}

	public function setSince($since) {
		$this->since = $since;
		return $this;
	}

	function getAttachment() {
		return $this->attachment;
	}

	function setAttachment($attachment) {
		$this->attachment = $attachment;
		return $this;
	}
	
	function getAttachment_reg() {
		return $this->attachment_reg;
	}

	function setAttachment_reg($attachment_reg) {
		$this->attachment_reg = $attachment_reg;
		return $this;
	}
	
	function getAttachment_ext() {
		return $this->attachment_ext;
	}

	function setAttachment_ext($attachment_ext) {
		$this->attachment_ext = $attachment_ext;
		return $this;
	}

}
