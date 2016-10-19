<?php

namespace Mailbox;

/**
 * помощник
 *
 * @author d.lanec
 */
class Helper {

	static function decodeString($filename, $serverEncoding = 'utf-8') {

		$filename1 = self::decodeMimeStr($filename, $serverEncoding);
		$filename2 = self::decodeRFC2231($filename1, $serverEncoding);
		$filename3 = urldecode(preg_replace('~^utf-8\'\'~', '', $filename2));
		return $filename3;
	}

	static function decodeMimeStr($value, $defcharset = 'utf-8') {
		$decoded = '';
		$parts = imap_mime_header_decode($value);

		foreach ($parts as $part) {
			$charset = 'default' == $part->charset ? 'auto' : $part->charset;
			// imap_utf8 doesn't seem to work properly, so use Transcoder instead

			if (strtoupper(str_replace('-', '', $charset)) != strtoupper(str_replace('-', '', $defcharset)) &&
					$str = @mb_convert_encoding(mb_convert_encoding(utf8_encode($part->text), 'WINDOWS-1252', 'UTF-8'), 'UTF-8', $charset)) {
				$decoded .= $str;
			} else {
				$decoded .= $part->text;
			}
		}
		return $decoded;
	}

	static function isUrlEncoded($string) {
		$string = str_replace('%20', '+', $string);
		$decoded = urldecode($string);
		return $decoded != $string && urlencode($decoded) == $string;
	}

	static function decodeRFC2231($string, $charset = 'utf-8') {
		if (preg_match("/^(.*?)'.*?'(.*?)$/", $string, $matches)) {
			$encoding = $matches[1];
			$data = $matches[2];
			if (self::isUrlEncoded($data)) {
				$string = iconv(strtoupper($encoding), $charset . '//IGNORE', urldecode($data));
			}
		}
		return $string;
	}
	
	/**
	 * Очистка старых файлов
	 * @param type $dir
	 */
	static function clearOldTmpFiles($dir){
		
		$expire_time = 3000; // Время через которое файл считается устаревшим (в сек.)

		$dir = rtrim($dir,"/");
		
		// проверяем, что $dir - каталог
		if (is_dir($dir)) {
			// открываем каталог
			if ($dh = opendir($dir)) {
			// читаем и выводим все элементы
				// от первого до последнего
				while (($file = readdir($dh)) !== false) {

					// текущее время
					$time_sec	 = time();
					// время изменения файла
					$time_file	 = filemtime($dir . "/" . $file);
					// тепрь узнаем сколько прошло времени (в секундах)
					$time		 = $time_sec - $time_file;

					$unlink = $dir . "/" . $file;

					if (is_file($unlink)) {
						if ($time > $expire_time) {

							if (unlink($unlink)) {

//								echo 'Файл удален';
							} else {

//								echo 'Ошибка при удалении файла';
							}
						}
					}
				}
// закрываем каталог
				closedir($dh);
			}
		}


		return true;
		
	}

}
