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

}
