<?php

namespace Mailbox\Base;

use Eden\Mail\Argument;

/**
 * Description of Index
 *
 * @author d.lanec
 */
class Index extends \Eden\Mail\Index {
	
    /**
     * Returns Mail IMAP
     *
     * @param *string  $host The IMAP host
     * @param *string  $user The mailbox user name
     * @param *string  $pass The mailbox password
     * @param int|null $port The IMAP port
     * @param bool     $ssl  Whether to use SSL
     * @param bool     $tls  Whether to use TLS
     *
     * @return Eden\Mail\Imap
     */
    public function imap($host, $user, $pass, $port = null, $ssl = false, $tls = false)
    {
        Argument::i()
            ->test(1, 'string')
            ->test(2, 'string')
            ->test(3, 'string')
            ->test(4, 'int', 'null')
            ->test(5, 'bool')
            ->test(6, 'bool');
            
        return Imap::i($host, $user, $pass, $port, $ssl, $tls);
    }
}
