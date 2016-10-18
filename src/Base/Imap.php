<?php 

namespace Mailbox\Base;

use Eden\Mail\Argument;

class Imap extends \Eden\Mail\Imap
{
	
    /**
     * Returns a list of mailboxes
     *
     * @return array
     */
    public function getMailboxes()
    {
        if (!$this->socket) {
            $this->connect();
        }

        $response = $this->call('LIST', $this->escape('', '*'));
		
        $mailboxes = array();
        foreach ($response as $line) {
			
            if (strpos($line, 'Noselect') !== false || strpos($line, 'LIST') == false) {
                continue;
            }
			
			//* LIST (\HasNoChildren) "/" &BBQEPQQ1BDIEPQQ4BDo-
			if(!preg_match("~(\*\sLIST\s\(.*?\))\s\"(.)\"\s(.*)~is", $line, $match)){
				continue;
			}
			
			$name = trim($match[3],'"');
			
			$m2 = explode("{$match[2]}", $name );
			
			$mailboxes[$name] = [
				'name'		=>	$m2[count($m2)-1],
				"parent"	=>	$m2[count($m2)-2]
			];
        }

        return $mailboxes;
    }
	
   /**
     * Returns stringified list
     * considering arrays inside of arrays
     *
     * @param array $array The list to transform
     *
     * @return string
     */
    private function getList($array)
    {
        $list = array();
        foreach ($array as $key => $value) {
            $list[] = !is_array($value) ? $value : $this->getList($v);
        }

        return '(' . implode(' ', $list) . ')';
    }

	    /**
     * Returns email reponse headers
     * array key value format
     *
     * @param *string $rawData The data to parse
     *
     * @return array
     */
    private function getHeaders($rawData)
    {
        if (is_string($rawData)) {
            $rawData = explode("\n", $rawData);
        }

        $key = null;
        $headers = array();
        foreach ($rawData as $line) {
            $line = trim($line);
            if (preg_match("/^([a-zA-Z0-9-]+):/i", $line, $matches)) {
                $key = strtolower($matches[1]);
                if (isset($headers[$key])) {
                    if (!is_array($headers[$key])) {
                        $headers[$key] = array($headers[$key]);
                    }

                    $headers[$key][] = trim(str_replace($matches[0], '', $line));
                    continue;
                }

                $headers[$key] = trim(str_replace($matches[0], '', $line));
                continue;
            }

            if (!is_null($key) && isset($headers[$key])) {
                if (is_array($headers[$key])) {
                    $headers[$key][count($headers[$key])-1] .= ' '.$line;
                    continue;
                }

                $headers[$key] .= ' '.$line;
            }
        }

        return $headers;
    }
	
    /**
     * Escaping works differently with IMAP
     * compared to how PHP escapes code
     *
     * @param *string $string the string to escape
     *
     * @return string
     */
    private function escape($string)
    {
        if (func_num_args() < 2) {
            if (strpos($string, "\n") !== false) {
                return array('{' . strlen($string) . '}', $string);
            } else {
                return '"' . str_replace(array('\\', '"'), array('\\\\', '\\"'), $string) . '"';
            }
        }

        $result = array();
        foreach (func_get_args() as $string) {
            $result[] = $this->escape($string);
        }

        return $result;
    }	
	
    /**
     * Returns a list of emails given the range
     *
     * @param number $start Pagination start
     * @param number $range Pagination range
     * @param bool   $body  add body to threads
     *
     * @return array
     */
    public function getEmails($start = 0, $range = 10, $body = false)
    {
        Argument::i()
            ->test(1, 'int', 'array')
            ->test(2, 'int');

        //if not connected
        if (!$this->socket) {
            //then connect
            $this->connect();
        }

        //if the total in this mailbox is 0
        //it means they probably didn't select a mailbox
        //or the mailbox selected is empty
        if ($this->total == 0) {
            //we might as well return an empty array
            return array();
        }

        //if start is an array
        if (is_array($start)) {
            //it is a set of numbers
            $set = implode(',', $start);
            //just ignore the range parameter
        } else {
            //start is a number
            //range must be grater than 0
            $range = $range > 0 ? $range : 1;
            //start must be a positive number
            $start = $start >= 0 ? $start : 0;

            //calculate max (ex. 300 - 4 = 296)
            $max = $this->total - $start;

            //if max is less than 1
            if ($max < 1) {
                //set max to total (ex. 300)
                $max = $this->total;
            }

            //calculate min (ex. 296 - 15 + 1 = 282)
            $min = $max - $range + 1;

            //if min less than 1
            if ($min < 1) {
                //set it to 1
                $min = 1;
            }

            //now add min and max to set (ex. 282:296 or 1 - 300)
            $set = $min . ':' . $max;

            //if min equal max
            if ($min == $max) {
                //we should only get one number
                $set = $min;
            }
        }

        $items = array('UID', 'FLAGS', 'BODY[HEADER]');

        if ($body) {
            $items  = array('UID', 'FLAGS', 'BODY[]');
        }

        //now lets call this
        $emails = $this->getEmailResponse('FETCH', array($set, $this->getList($items)));

        //this will be in ascending order
        //we actually want to reverse this
        $emails = array_reverse($emails);

        return $emails;
    }
	
    /**
     * Splits emails into arrays
     *
     * @param *string $command    The IMAP command
     * @param array   $parameters Any extra parameters
     * @param bool    $first      Whether the return should just be the first
     *
     * @return array
     */
    private function getEmailResponse($command, $parameters = array(), $first = false)
    {
        //send out the command
        if (!$this->send($command, $parameters)) {
            return false;
        }

        $messageId  = $uniqueId = $count = 0;
        $emails     = $email = array();
        $start      = time();

        //while there is no hang
        while (time() < ($start + self::TIMEOUT)) {
            //get a response line
            $line = str_replace("\n", '', $this->getLine());

            //if the line starts with a fetch
            //it means it's the end of getting an email
            if (strpos($line, 'FETCH') !== false && strpos($line, 'TAG'.$this->tag) === false) {
                //if there is email data
                if (!empty($email)) {
                    //create the email format and add it to emails
                    $emails[$uniqueId] = $this->getEmailFormat($email, $uniqueId, $flags);

                    //if all we want is the first one
                    if ($first) {
                        //just return this
                        return $emails[$uniqueId];
                    }

                    //make email data empty again
                    $email = array();
                }

                //if just okay
                if (strpos($line, 'OK') !== false) {
                    //then skip the rest
                    continue;
                }

                //if it's not just ok
                //it will contain the message id and the unique id and flags
                $flags = array();
                if (strpos($line, '\Answered') !== false) {
                    $flags[] = 'answered';
                }

                if (strpos($line, '\Flagged') !== false) {
                    $flags[] = 'flagged';
                }

                if (strpos($line, '\Deleted') !== false) {
                    $flags[] = 'deleted';
                }

                if (strpos($line, '\Seen') !== false) {
                    $flags[] = 'seen';
                }

                if (strpos($line, '\Draft') !== false) {
                    $flags[] = 'draft';
                }

                $findUid = explode(' ', $line);
                foreach ($findUid as $i => $uid) {
                    if (is_numeric($uid)) {
                        $uniqueId = $uid;
                    }
                    if (strpos(strtolower($uid), 'uid') !== false) {
                        $uniqueId = $findUid[$i+1];
                        break;
                    }
                }

                //skip the rest
                continue;
            }

            //if there is a tag it means we are at the end
            if (strpos($line, 'TAG'.$this->tag) !== false) {
                //if email details are not empty and the last line is just a )
                if (!empty($email) && strpos(trim($email[count($email) -1]), ')') === 0) {
                    //take it out because that is not part of the details
                    array_pop($email);
                }

                //if there is email data
                if (!empty($email)) {
                    //create the email format and add it to emails
                    $emails[$uniqueId] = $this->getEmailFormat($email, $uniqueId, $flags);

                    //if all we want is the first one
                    if ($first) {
                        //just return this
                        return $emails[$uniqueId];
                    }
                }

                //break out of this loop
                break;
            }

            //so at this point we are getting raw data
            //capture this data in email details
            $email[] = $line;
        }

        return $emails;
    }
	
    /**
     * Secret Sauce - Transform an email string
     * response to array key value format
     *
     * @param *string      $email    The actual email
     * @param string|null  $uniqueId The mail UID
     * @param array        $flags    Any mail flags
     *
     * @return array
     */
    private function getEmailFormat($email, $uniqueId = null, array $flags = array())
    {
        //if email is an array
        if (is_array($email)) {
            //make it into a string
            $email = implode("\n", $email);
        }

        //split the head and the body
        $parts = preg_split("/\n\s*\n/", $email, 2);

        $head = $parts[0];
        $body = null;
        if (isset($parts[1]) && trim($parts[1]) != ')') {
            $body = $parts[1];
        }

        $lines = explode("\n", $head);
        $head = array();
        foreach ($lines as $line) {
            if (trim($line) && preg_match("/^\s+/", $line)) {
                $head[count($head)-1] .= ' '.trim($line);
                continue;
            }

            $head[] = trim($line);
        }

        $head = implode("\n", $head);

        $recipientsTo = $recipientsCc = $recipientsBcc = $sender = array();

        //get the headers
        $headers1   = imap_rfc822_parse_headers($head);
        $headers2   = $this->getHeaders($head);

        //set the from
        $sender['name'] = null;
        if (isset($headers1->from[0]->personal)) {
            $sender['name'] = $headers1->from[0]->personal;
            //if the name is iso or utf encoded
            if (preg_match("/^\=\?[a-zA-Z]+\-[0-9]+.*\?/", strtolower($sender['name']))) {
                //decode the subject
                $sender['name'] = str_replace('_', ' ', mb_decode_mimeheader($sender['name']));
            }
        }

        $sender['email'] = $headers1->from[0]->mailbox . '@' . $headers1->from[0]->host;

        //set the to
        if (isset($headers1->to)) {
            foreach ($headers1->to as $to) {
                if (!isset($to->mailbox, $to->host)) {
                    continue;
                }

                $recipient = array('name'=>null);
                if (isset($to->personal)) {
                    $recipient['name'] = $to->personal;
                    //if the name is iso or utf encoded
                    if (preg_match("/^\=\?[a-zA-Z]+\-[0-9]+.*\?/", strtolower($recipient['name']))) {
                        //decode the subject
                        $recipient['name'] = str_replace('_', ' ', mb_decode_mimeheader($recipient['name']));
                    }
                }

                $recipient['email'] = $to->mailbox . '@' . $to->host;

                $recipientsTo[] = $recipient;
            }
        }

        //set the cc
        if (isset($headers1->cc)) {
            foreach ($headers1->cc as $cc) {
                $recipient = array('name'=>null);
                if (isset($cc->personal)) {
                    $recipient['name'] = $cc->personal;

                    //if the name is iso or utf encoded
                    if (preg_match("/^\=\?[a-zA-Z]+\-[0-9]+.*\?/", strtolower($recipient['name']))) {
                        //decode the subject
                        $recipient['name'] = str_replace('_', ' ', mb_decode_mimeheader($recipient['name']));
                    }
                }

                $recipient['email'] = $cc->mailbox . '@' . $cc->host;

                $recipientsCc[] = $recipient;
            }
        }

        //set the bcc
        if (isset($headers1->bcc)) {
            foreach ($headers1->bcc as $bcc) {
                $recipient = array('name'=>null);
                if (isset($bcc->personal)) {
                    $recipient['name'] = $bcc->personal;
                    //if the name is iso or utf encoded
                    if (preg_match("/^\=\?[a-zA-Z]+\-[0-9]+.*\?/", strtolower($recipient['name']))) {
                        //decode the subject
                        $recipient['name'] = str_replace('_', ' ', mb_decode_mimeheader($recipient['name']));
                    }
                }

                $recipient['email'] = $bcc->mailbox . '@' . $bcc->host;

                $recipientsBcc[] = $recipient;
            }
        }

        //if subject is not set
        if (!isset($headers1->subject) || strlen(trim($headers1->subject)) === 0) {
            //set subject
            $headers1->subject = self::NO_SUBJECT;
        }

        //trim the subject
        $headers1->subject = str_replace(array('<', '>'), '', trim($headers1->subject));

        //if the subject is iso or utf encoded
        if (preg_match("/^\=\?[a-zA-Z]+\-[0-9]+.*\?/", strtolower($headers1->subject))) {
            //decode the subject
            $headers1->subject = str_replace('_', ' ', mb_decode_mimeheader($headers1->subject));
        }

        //set thread details
        $topic  = isset($headers2['thread-topic']) ? $headers2['thread-topic'] : $headers1->subject;
        $parent = isset($headers2['in-reply-to']) ? str_replace('"', '', $headers2['in-reply-to']) : null;

        //set date
        $date = isset($headers1->date) ? strtotime($headers1->date) : null;

        //set message id
        if (isset($headers2['message-id'])) {
            $messageId = str_replace('"', '', $headers2['message-id']);
        } else {
            $messageId = '<eden-no-id-'.md5(uniqid()).'>';
        }

        $attachment = is_array($headers2['content-type']) || (isset($headers2['content-type'])
        && !is_array($headers2['content-type']) && strpos($headers2['content-type'], 'multipart/mixed') === 0) ;

        $format = array(
            'id'            => $messageId,
            'parent'        => $parent,
            'topic'         => $topic,
            'mailbox'       => $this->mailbox,
            'uid'           => $uniqueId,
            'date'          => $date,
            'subject'       => str_replace('’', '\'', $headers1->subject),
            'from'          => $sender,
            'flags'         => $flags,
            'to'            => $recipientsTo,
            'cc'            => $recipientsCc,
            'bcc'           => $recipientsBcc,
            'attachment'    => $attachment);

        if (trim($body) && $body != ')') {
            //get the body parts
            $parts = $this->getParts($email);

            //if there are no parts
            if (empty($parts)) {
                //just make the body as a single part
                $parts = array('text/plain' => $body);
            }

            //set body to the body parts
            $body = $parts;

            //look for attachments
            $attachment = array();
            //if there is an attachment in the body
            if (isset($body['attachment'])) {
                //take it out
                $attachment = $body['attachment'];
                unset($body['attachment']);
            }

            $format['body']         = $body;
            $format['attachment']   = $attachment;
        }

        return $format;
    }
	
	
}

