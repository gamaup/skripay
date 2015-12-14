<?php defined('BASEPATH') OR exit('No direct script access allowed');

class BCAParser {

	public function __construct($conf) {
        $this->conf = $conf;
        $d          = explode( '|', date( 'Y|m|d|H|i|s', $this->conf['time'] ) );
        $start      = mktime( $d[3], $d[4], $d[5], $d[1], ( $d[2] - $this->conf['days'] ), $d[0] );
        $this->post_time['end']['y'] = $d[0];
        $this->post_time['end']['m'] = $d[1];
        $this->post_time['end']['d'] = $d[2];
        $this->post_time['start']['y'] = date( 'Y', $start );
        $this->post_time['start']['m'] = date( 'm', $start );
        $this->post_time['start']['d'] = date( 'd', $start );
	}

    function curlexec() {
        curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, 1 );
        return curl_exec( $this->ch );
    }

    function login( $username, $password ) {
    	$useragent = 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:39.0) Gecko/20100101 Firefox/39.0';
        $this->ch = curl_init();
        curl_setopt( $this->ch, CURLOPT_USERAGENT, $useragent );
        curl_setopt( $this->ch, CURLOPT_URL, 'https://ibank.klikbca.com/login.jsp' );
        curl_setopt( $this->ch, CURLOPT_COOKIEFILE, $this->conf['path'] . '/cookie' );
        curl_setopt( $this->ch, CURLOPT_COOKIEJAR, $this->conf['path'] . '/cookiejar' );
        $src = $this->curlexec();
        $parse = explode('<input type="hidden" name="value(CurNum)" value="', $src);
        $curnum = explode('">', $parse[1]);

        $inputs = array(
        	'value(actions)=login',
        	'value(user_id)='.$username,
        	'value(CurNum)='.$curnum[0],
        	'value(user_ip)='.$this->conf['ip'],
        	'value(browser_info)='.$useragent,
        	'value(mobile)=false',
        	'value(pswd)='.$password,
        	'value(Submit)=LOGIN'
        );
        $params = implode('&', $inputs);

        curl_setopt( $this->ch, CURLOPT_URL, 'https://ibank.klikbca.com/authentication.do' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://ibank.klikbca.com/login.jsp' );
        curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $params );
        curl_setopt( $this->ch, CURLOPT_POST, 1 );
        $this->curlexec();
        // return $src;
    }

    function logout() {
        curl_setopt( $this->ch, CURLOPT_URL, 'https://ibank.klikbca.com/authentication.do?value(actions)=logout' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://ibank.klikbca.com/authentication.do?value(actions)=menu' );
        $this->curlexec();
        return curl_close( $this->ch );
    }

    function loginCheck( $username, $password ) {
        $this->login($username, $password);
        $result = $this->getBalance();
        if ($result != false) $result = true;
        $this->logout();
        return $result;
    }

    function getDetails() {
        curl_setopt( $this->ch, CURLOPT_URL, 'https://ibank.klikbca.com/authentication.do?value(actions)=welcome' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://ibank.klikbca.com/authentication.do' );
        $src = $this->curlexec();
        $parse = explode('<font face="Verdana, Arial, Helvetica, sans-serif" size="3" color="#000099">', $src);
        $account_name = explode('Selamat Datang Di Internet Banking BCA', $parse[1]);
        $account_name = trim(str_replace(',', '', $account_name[0]));
        curl_setopt( $this->ch, CURLOPT_URL, 'https://ibank.klikbca.com/balanceinquiry.do' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://ibank.klikbca.com/authentication.do' );
        $src = $this->curlexec();
        $parseAll = explode('<font face="Verdana" size="2" color="#0000bb">', $src);
        $parse 				= explode('</font>', $parseAll[6]);
        $account_number		= trim($parse[0]);
        $parse 				= explode('</font>', $parseAll[7]);
        $account_type   	= trim($parse[0]);
        $parse 				= explode('</font>', $parseAll[9]);
        $account_balance   	= trim($parse[0]);
        $account_balance	= str_replace(',', '', $account_balance);

        $data = new stdClass();
        $data->bank = 'BCA';
        $data->account_number = $account_number;
        $data->account_name = $account_name;
        $data->account_type = $account_type;
        $data->account_balance = $account_balance;
        $data->last_update = date('Y-m-d H:i:s');
        return $data;
    }

    function getBalance() {
        curl_setopt( $this->ch, CURLOPT_URL, 'https://ibank.klikbca.com/balanceinquiry.do' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://ibank.klikbca.com/authentication.do' );
        $src = $this->curlexec();
        $parseAll = explode('<font face="Verdana" size="2" color="#0000bb">', $src);
        if (empty($parseAll[9])) return false;
        $parse 				= explode('</font>', $parseAll[9]);
        if (empty($parse[0])) return false;
        $account_balance   	= trim($parse[0]);
        $account_balance	= str_replace(',', '', $account_balance);
        return (is_numeric($account_balance)) ? $account_balance : false;
    }

    function getTransactions() {
        curl_setopt( $this->ch, CURLOPT_URL, 'https://ibank.klikbca.com/accountstmt.do?value(actions)=acct_stmt' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://ibank.klikbca.com/accountstmt.do?value(actions)=menu' );
        $this->curlexec();

        $inputs = array(
        	'r1=1',
        	'value(D1)=0',
        	'value(startDt)='.$this->post_time['start']['d'],
        	'value(startMt)='.$this->post_time['start']['m'],
        	'value(startYr)='.$this->post_time['start']['y'],
        	'value(endDt)='.$this->post_time['end']['d'],
        	'value(endMt)='.$this->post_time['end']['m'],
        	'value(endYr)='.$this->post_time['end']['y']
        );
        $params = implode('&', $inputs);

        curl_setopt( $this->ch, CURLOPT_URL, 'https://ibank.klikbca.com/accountstmt.do?value(actions)=acctstmtview' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://ibank.klikbca.com/accountstmt.do?value(actions)=acct_stmt' );
        curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $params );
        curl_setopt( $this->ch, CURLOPT_POST, 1 );

        $src = $this->curlexec();
        // return $src;

        $parse = explode( '<table border="1" width="100%" cellpadding="0" cellspacing="0" bordercolor="#ffffff">', $src );

        if (empty($parse[1])) return false;

        $parse = explode('</table>', $parse[1]);
        $parse = explode('<tr>', $parse[0]);
        $rows  = array(); 
        foreach ($parse as $n => $r) {
            if ($n > 1) {
                $r = explode('</tr>', $r);
                $rows[] = $r[0];
            }
        }

        foreach ($rows as $k => $val) {
            $rows[$k]   = new stdClass;
            $parse      = explode('<font face="verdana" size="1" color="#0000bb">', $val);
            $date       = explode('</font>', $parse[1]);
            $date       = trim($date[0]);
            if ($date != 'PEND') {
                $date = str_replace('/', '-', $date.'/'.date('Y'));
                $date = date('Y-m-d', strtotime($date));
            }
            $desc       = explode('</font>', $parse[2]);
            $desc       = preg_replace('/\s+/', ' ', trim(strip_tags($desc[0])));
            $amount     = explode('</font>', $parse[4]);
            $amount     = str_replace(',', '', trim($amount[0]));
            $type       = explode('</font>', $parse[5]);
            $type       = trim($type[0]);
            if ($type == 'DB') {
                $debet  = $amount;
                $kredit = '0.00';
            } else {
                $debet  = '0.00';
                $kredit = $amount;
            }

            $rows[$k]->date         = $date;
            $rows[$k]->description  = $desc;
            $rows[$k]->debet        = $debet;
            $rows[$k]->kredit       = $kredit;
        }
        
        return (!empty($rows)) ? $rows : false;
    }

    function findTransaction($amount) {
        $amount = number_format($amount,2,'.','');
        $transactions = $this->getTransactions();
        if (empty($transactions)) return false;
        foreach ($transactions as $t) {
            if ($t->kredit == $amount) {
                return true;
                break;
            }
        }
    }

}

/* End of file BCAParser.php */
/* Location: ./application/libraries/parser/BCAParser.php */
