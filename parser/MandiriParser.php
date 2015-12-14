<?php defined('BASEPATH') OR exit('No direct script access allowed');

class MandiriParser {

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
        $this->ch = curl_init();
        curl_setopt( $this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; U; Android 2.3.7; en-us; Nexus One Build/GRK39F) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1' );
        curl_setopt( $this->ch, CURLOPT_URL, 'https://ib.bankmandiri.co.id/retail/Login.do?action=form&lang=in_ID' );
        curl_setopt( $this->ch, CURLOPT_COOKIEFILE, $this->conf['path'] . '/cookie' );
        curl_setopt( $this->ch, CURLOPT_COOKIEJAR, $this->conf['path'] . '/cookiejar' );
        $this->curlexec();

        $params = implode( '&', array( 'userID=' . $username, 'password=' . $password, 'action=result' ) );
        curl_setopt( $this->ch, CURLOPT_URL, 'https://ib.bankmandiri.co.id/retail/Login.do' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://ib.bankmandiri.co.id/retail/Login.do?action=form&lang=in_ID' );
        curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $params );
        curl_setopt( $this->ch, CURLOPT_POST, 1 );
        return $this->curlexec();
    }

    function logout() {
        curl_setopt( $this->ch, CURLOPT_URL, 'https://ib.bankmandiri.co.id/retail/Logout.do?action=result' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://ib.bankmandiri.co.id/retail/Redirect.do?action=forward' );
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
        curl_setopt( $this->ch, CURLOPT_URL, 'https://ib.bankmandiri.co.id/retail/Welcome.do?action=result' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://ib.bankmandiri.co.id/retail/Redirect.do?action=forward' );
        $src = $this->curlexec();
        $parse = explode('<p class="header" style="color: #000;">', $src);
        if (empty($parse[1])) return false;
        $parse = explode('</p>', $parse[1]);
        if (empty($parse[0])) return false;
        $account_name = trim($parse[0]);

        curl_setopt( $this->ch, CURLOPT_URL, 'https://ib.bankmandiri.co.id/retail/AccountList.do?action=acclist' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://ib.bankmandiri.co.id/retail/Redirect.do?action=forward' );
        $src = $this->curlexec();
        $parse = explode( "sendForm('", $src );
        if ( empty( $parse[1] ) )
            return false;
        $parse = explode( "',this", $parse[1] );
        if ( empty( $parse[0] ) )
            return false;
        $accountID = $parse[0];

        curl_setopt( $this->ch, CURLOPT_URL, 'https://ib.bankmandiri.co.id/retail/AccountDetail.do?action=result&ACCOUNTID='.$accountID );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://ib.bankmandiri.co.id/retail/AccountList.do?action=acclist' );
        $src = $this->curlexec();
        $parseAll = explode('<td height="25" width="304">', $src); //get all column

        if (empty($parseAll[2])) return false;
        $parse = explode('</td>', $parseAll[2]);
        if (empty($parse[0])) return false;
        $account_number = trim($parse[0]);

        if (empty($parseAll[3])) return false;
        $parse = explode('</td>', $parseAll[3]);
        if (empty($parse[0])) return false;
        $account_type = trim($parse[0]);

        if (empty($parseAll[5])) return false;
        $parse = explode('</td>', $parseAll[5]);
        if (empty($parse[0])) return false;
        $account_balance = str_replace('Rp.&nbsp;', '', $parse[0]);
        $account_balance = str_replace('.', '', $account_balance);
        $account_balance = str_replace(',', '.', $account_balance);
        $account_balance = trim($account_balance);

        $last_update = date('Y-m-d H:i:s');
        
        $data = new stdClass();
        $data->bank = 'Mandiri';
        $data->account_number = $account_number;
        $data->account_name = $account_name;
        $data->account_type = $account_type;
        $data->account_balance = $account_balance;
        $data->last_update = $last_update;
        return $data;
    }

    function getBalance() {
        curl_setopt( $this->ch, CURLOPT_URL, 'https://ib.bankmandiri.co.id/retail/AccountList.do?action=acclist' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://ib.bankmandiri.co.id/retail/Redirect.do?action=forward' );
        $src = $this->curlexec();
        $parse = explode( "sendForm('", $src );
        if ( empty( $parse[1] ) )
            return false;
        $parse = explode( "',this", $parse[1] );
        if ( empty( $parse[0] ) )
            return false;
        $accountID = $parse[0];

        curl_setopt( $this->ch, CURLOPT_URL, 'https://ib.bankmandiri.co.id/retail/AccountDetail.do?action=result&ACCOUNTID='.$accountID );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://ib.bankmandiri.co.id/retail/AccountList.do?action=acclist' );
        $src = $this->curlexec();
        $parse = explode( "Rp.", $src );
        if ( empty( $parse[1] ) )
            return false;
        $parse = explode( "</td>", $parse[1] );
        if ( empty( $parse[0] ) )
            return false;
        $parse = str_replace( '&nbsp;', '', $parse[0] );
        $parse = str_replace( '.', '', $parse );
        $parse = str_replace( ',', '.', $parse );
        // return $parse;
        return ( is_numeric( $parse ) ) ? $parse: false;
    }

    function getTransactions() {
        curl_setopt( $this->ch, CURLOPT_URL, 'https://ib.bankmandiri.co.id/retail/TrxHistoryInq.do?action=form' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://ib.bankmandiri.co.id/retail/Redirect.do?action=forward' );
        $src = $this->curlexec();
        $parse = explode('<select name="fromAccountID"><option value="">Silahkan Pilih</option>', $src);
        if (empty($parse[1])) return false;
        $parse = explode('">', $parse[1]);
        $account_number = str_replace('<option value="', '', trim($parse[0]));
        
        $inputs = array(
            'action=result',
            'fromAccountID='.$account_number,
            'searchType=R',
            'fromDay='.$this->post_time['start']['d'],
            'fromMonth='.$this->post_time['start']['m'],
            'fromYear='.$this->post_time['start']['y'],
            'toDay='.$this->post_time['end']['d'],
            'toMonth='.$this->post_time['end']['m'],
            'toYear='.$this->post_time['end']['y'],
        );
        $params = implode('&', $inputs);

        curl_setopt( $this->ch, CURLOPT_URL, 'https://ib.bankmandiri.co.id/retail/TrxHistoryInq.do' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://ib.bankmandiri.co.id/retail/TrxHistoryInq.do?action=form' );
        curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $params );
        curl_setopt( $this->ch, CURLOPT_POST, 1 );
        $src = $this->curlexec();
        $parse = explode('<!-- Start of Item List -->', $src);
        if (empty($parse[1])) return false;
        $parse = explode('<!-- End of Item List -->', $parse[1]);
        $parse = explode('<tr height="25">', $parse[0]);
        $rows  = array(); 
        foreach ($parse as $n => $r) {
            if ($n > 0) {
                $r = explode('</tr>', $r);
                $rows[] = $r[0];
            }
        }

        foreach ($rows as $k => $val) {
            $rows[$k]   = new stdClass;
            if ($val == '<td>&nbsp;</td>') {
                unset($rows[$k]);
            } else {
                $parse      = explode('</td>', $val);
                $date       = explode('>', $parse[0]);
                $date       = trim($date[1]);
                if ($date != 'PEND') {
                    $date = str_replace('/', '-', $date.'/'.date('Y'));
                    $date = date('Y-m-d', strtotime($date));
                }
                $desc       = explode('">', $parse[1]);
                $desc       = preg_replace('/\s+/', ' ', trim(str_replace('<br>',' ',$desc[1])));
                $debet      = explode('>', $parse[2]);
                $debet      = str_replace(',','.',str_replace('.','',trim($debet[1])));
                $kredit     = explode('>', $parse[3]);
                $kredit     = str_replace(',','.',str_replace('.','',trim($kredit[1])));

                $rows[$k]->date         = $date;
                $rows[$k]->description  = $desc;
                $rows[$k]->debet        = $debet;
                $rows[$k]->kredit       = $kredit;
            }
        }
        
        // return false;
        return (!empty($rows)) ? $rows : false;
    }

    function findTransaction($amount) {
        $amount = number_format($amount,2,'.','');
        $transactions = $this->getTransactions();
        // return $hm = [
        //     'amount'    => $amount,
        //     'transaction'=> $transactions
        // ];
        if (empty($transactions)) return false;
        foreach ($transactions as $t) {
            if ($t->kredit == $amount) {
                return true;
                break;
            }
        }
    }

}

/* End of file MandiriParser.php */
/* Location: ./application/libraries/parser/MandiriParser.php */
