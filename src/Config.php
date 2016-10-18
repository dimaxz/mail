<?php

namespace Mailbox;

/**
 * Класс конфига
 *
 * @author d.lanec
 */
class Config {

	/**
	 * Путь для временных файлов вложений
	 * @return string
	 */
	static function getTmpDir() {
		return "/_debug/attachments";
	}

}
