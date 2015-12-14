<?php defined('BASEPATH') OR exit('No direct script access allowed');

class BNIParser {

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
        $this->url = ''; //URL buat ngeparsing BNI dengan session
	}

	function curlexec() {
        curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, 1 );
        return curl_exec( $this->ch );
    }

	function login( $username, $password ) {
        $this->username = $username;
        $this->password = $password;
        $this->ch = curl_init();
        curl_setopt( $this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; U; Android 2.3.7; en-us; Nexus One Build/GRK39F) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1' );
        curl_setopt( $this->ch, CURLOPT_URL, 'https://ibank.bni.co.id/MBAWeb/FMB' );
        curl_setopt( $this->ch, CURLOPT_COOKIEFILE, $this->conf['path'] . '/cookie' );
        curl_setopt( $this->ch, CURLOPT_COOKIEJAR, $this->conf['path'] . '/cookiejar' );
        $res = $this->curlexec();
        $signin = explode('class="lgnaln" href="', $res);
        if (empty($signin[1])) return false;
        $signin = explode('"', $signin[1]);
        if (empty($signin[0])) return false;
        // return $signin[0];
        curl_setopt( $this->ch, CURLOPT_URL, $signin[0] );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://ibank.bni.co.id/MBAWeb/FMB' );
        $loginpage =  $this->curlexec();
        $form_action = explode('name="form" action="', $loginpage);
        if (empty($form_action[1])) return false;
        $form_action = explode('"', $form_action[1]);
        if (empty($form_action[0])) return false;
        // return $loginpage;
        $inputs = array(
            'Num_Field_Err=Please enter digits only!',
            'Mand_Field_Err=Mandatory field is empty!',
            'CorpId='.$username,
            'PassWord='.$password,
            'CancelPage=HomePage.xml',
            'USER_TYPE=1',
            'AUTHENTICATION_REQUEST=True',
            'uniqueURLStatus=disabled',
            'Alignment=LEFT',
            'page=SignOnRetRq',
            'locale=bh',
            'PageName=Thin_SignOnRetRq.xml',
            'formAction='.$form_action[0],
            'serviceType=Dynamic',
            '__AUTHENTICATE__=Login'
        );
        $params = implode('&', $inputs);
        curl_setopt( $this->ch, CURLOPT_URL, $form_action[0] );
        curl_setopt( $this->ch, CURLOPT_REFERER, $signin[0] );
        curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $params );
        curl_setopt( $this->ch, CURLOPT_POST, 1 );
        $this->url = $form_action[0];
        $res = $this->curlexec();
        $pecah = explode('">REKENING', $res);
        if (empty($pecah[0])) return false;
        $rekening_url = explode('class="MainMenuStyle" href="', $pecah[0]);
        if (empty($rekening_url[1])) return false;
        $this->url_rekening = $rekening_url[1];
        return $res;
        // curl_setopt( $this->ch, CURLOPT_URL, $form_action[0] );
        // return $this->curlexec();
    }

    function logout() {
        return curl_close( $this->ch );
    }

    function loginCheck( $username, $password ) {
        $result = $this->login($username, $password);
        if ($result != false) $result = true;
        $this->logout();
        return $result;
    }

    function getDetails() {
        if (empty($this->url_rekening)) return false;
        $inputs = array(
            'Num_Field_Err=Please enter digits only!',
            'Mand_Field_Err=Mandatory field is empty!',
            'CorpId='.$this->username,
            'PassWord='.$this->password,
            'USER_TYPE=1',
            'AUTHENTICATION_REQUEST=True',
            'MAIN_ACCOUNT_TYPE=OPR',
            'AccountIDSelectRq=Lanjut',
            'AccountRequestType=ViewBalance',
            // 'mbparam='.$mbparam[0],
            'uniqueURLStatus=disabled',
            'Alignment=LEFT',
            'page=AccountTypeSelectRq',
            'locale=bh',
            'PageName=BalanceInqRq',
            'serviceType=Dynamic'
        );
        $params = implode('&', $inputs);
        curl_setopt( $this->ch, CURLOPT_URL, $this->url_rekening );
        curl_setopt( $this->ch, CURLOPT_REFERER, $this->url );
        curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $params );
        curl_setopt( $this->ch, CURLOPT_POST, 1 );
        // return $this->url_rekening.'<br/>'.$mbparam[0];
        $res = $this->curlexec(); //rekening
        $pecah = explode('">INFORMASI SALDO', $res);
        if (empty($pecah[0])) return false;
        $saldo_url = explode('class="MainMenuStyle" href="', $pecah[0]);
        if (empty($saldo_url[1])) return false;
        curl_setopt( $this->ch, CURLOPT_URL, $saldo_url[1] );
        curl_setopt( $this->ch, CURLOPT_REFERER, $this->url );
        curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $params );
        curl_setopt( $this->ch, CURLOPT_POST, 1 );
        $res2 = $this->curlexec();
        // $mbparam = explode('id="mbparam" value="', $res2);
        // $mbparam = explode('"', $mbparam[1]);
        $inputs = array(
            'Num_Field_Err=Please enter digits only!',
            'Mand_Field_Err=Mandatory field is empty!',
            'CorpId='.$this->username,
            'PassWord='.$this->password,
            'USER_TYPE=1',
            'AUTHENTICATION_REQUEST=True',
            'MAIN_ACCOUNT_TYPE=OPR',
            'AccountIDSelectRq=Lanjut',
            'AccountRequestType=ViewBalance',
            'uniqueURLStatus=disabled',
            'Alignment=LEFT',
            'page=AccountTypeSelectRq',
            'locale=bh',
            'PageName=BalanceInqRq',
            'serviceType=Dynamic'
        );
        $params = implode('&', $inputs);
        curl_setopt( $this->ch, CURLOPT_URL, $this->url );
        curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $params );
        curl_setopt( $this->ch, CURLOPT_POST, 1 );
        $res3 = $this->curlexec();
        $norek = explode('id="acc1" value="', $res3);
        if (empty($norek[1])) return false;
        $norek = explode('"', $norek[1]);
        if (empty($norek[0])) return false;
        // $mbparam = explode('id="mbparam" value="', $res3);
        // $mbparam = explode('"', $mbparam[1]);
        $inputs = array(
            'Num_Field_Err=Please enter digits only!',
            'Mand_Field_Err=Mandatory field is empty!',
            'CorpId='.$this->username,
            'PassWord='.$this->password,
            'USER_TYPE=1',
            'AUTHENTICATION_REQUEST=True',
            'MAIN_ACCOUNT_TYPE=OPR',
            'acc1='.$norek[0],
            'BalInqRq=Lanjut',
            'CHANNEL_TYPE=T',
            'AccountRequestType=ViewBalance',
            // 'mbparam='.$mbparam[0],
            'uniqueURLStatus=disabled',
            'Alignment=LEFT',
            'page=AccountIDSelectRq',
            'locale=bh',
            'PageName=AccountTypeSelectRq',
            'serviceType=Dynamic'
        );
        $params = implode('&', $inputs);
        curl_setopt( $this->ch, CURLOPT_URL, $this->url );
        curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $params );
        curl_setopt( $this->ch, CURLOPT_POST, 1 );
        $res4 = $this->curlexec();

        $account_number = explode('<td id="Row1_1_column2" width="223">
                     <div><span id="H" class="BodytextCol2">', $res4);
        if (empty($account_number[1])) return false;
        $account_number = explode('</span>', $account_number[1]);
        if (empty($account_number[0])) return false;
        $account_number = $account_number[0];
        
        $account_name = explode('<td id="Row3_3_column2" width="223">
                     <div><span id="H" class="BodytextCol2">', $res4);
        if (empty($account_name[1])) return false;
        $account_name = explode('</span>', $account_name[1]);
        if (empty($account_name[0])) return false;
        $account_name = explode(' ',$account_name[0]);
        unset($account_name[0]);
        $account_name = implode(' ', $account_name);

        $account_type = explode('<td id="Row4_4_column2" width="223">
                     <div><span id="H" class="BodytextCol2">', $res4);
        if (empty($account_type[1])) return false;
        $account_type = explode('</span>', $account_type[1]);
        if (empty($account_type[0])) return false;
        $account_type = $account_type[0];

        $account_balance = explode('<td id="Row5_5_column2" width="190">
                     <div><span id="H" class="BodytextUnbold">', $res4);
        if (empty($account_balance[1])) return false;
        $account_balance = explode('</span>', $account_balance[1]);
        if (empty($account_balance[0])) return false;
        $account_balance = $account_balance[0];
        $account_balance = str_replace('.', '', $account_balance);
        $account_balance = str_replace(',', '.', $account_balance);

        $last_update = date('Y-m-d H:i:s');
        
        $data = new stdClass();
        $data->bank = 'BNI';
        $data->account_number = $account_number;
        $data->account_name = $account_name;
        $data->account_type = $account_type;
        $data->account_balance = $account_balance;
        $data->last_update = $last_update;
        return $data;
    }

    function getBalance() {
        $result = $this->getDetails();
        $result = $result->account_balance;
        if (empty($result)) return false;
        return $result;
    }

    function getTransactions() {
        if (empty($this->url_rekening)) return false;
        $inputs = array(
            'Num_Field_Err=Please enter digits only!',
            'Mand_Field_Err=Mandatory field is empty!',
            'CorpId='.$this->username,
            'PassWord='.$this->password,
            'USER_TYPE=1',
            'AUTHENTICATION_REQUEST=True',
            'MAIN_ACCOUNT_TYPE=OPR',
            'AccountIDSelectRq=Lanjut',
            'AccountRequestType=ViewBalance',
            // 'mbparam='.$mbparam[0],
            'uniqueURLStatus=disabled',
            'Alignment=LEFT',
            'page=AccountTypeSelectRq',
            'locale=bh',
            'PageName=BalanceInqRq',
            'serviceType=Dynamic'
        );
        $params = implode('&', $inputs);
        curl_setopt( $this->ch, CURLOPT_URL, $this->url_rekening );
        curl_setopt( $this->ch, CURLOPT_REFERER, $this->url );
        curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $params );
        curl_setopt( $this->ch, CURLOPT_POST, 1 );
        // return $this->url_rekening.'<br/>'.$mbparam[0];
        $res = $this->curlexec(); //rekening
        $pecah = explode('">TRANSAKSI TERAKHIR', $res);
        if (empty($pecah[0])) return false;
        $transaksi_url = explode('class="MainMenuStyle" href="', $pecah[0]);
        if (empty($transaksi_url[2])) return false;
        $transaksi_url = $transaksi_url[2];
        curl_setopt( $this->ch, CURLOPT_URL, $transaksi_url );
        curl_setopt( $this->ch, CURLOPT_REFERER, $this->url );
        curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $params );
        curl_setopt( $this->ch, CURLOPT_POST, 1 );
        $res2 = $this->curlexec();
        $rekening = explode('<option value="', $res2);
        if (empty($rekening[2])) return false;
        $rekening = explode('"', $rekening[2]);
        if (empty($rekening[0])) return false;
        $rekening = $rekening[0];
        $inputs = array(
            'Num_Field_Err=Please enter digits only!',
            'Mand_Field_Err=Mandatory field is empty!',
            'CorpId='.$this->username,
            'PassWord='.$this->password,
            'USER_TYPE=1',
            'AUTHENTICATION_REQUEST=True',
            'MAIN_ACCOUNT_TYPE=OPR',
            'Go=Lanjut',
            'MiniStmt='.$rekening,
            // 'mbparam='.$mbparam[0],
            'uniqueURLStatus=disabled',
            'Alignment=LEFT',
            'page=OperMiniAccIDSelectRq',
            'locale=bh',
            'PageName=MiniStatementRq',
            'serviceType=Dynamic'
        );
        $params = implode('&', $inputs);
        curl_setopt( $this->ch, CURLOPT_URL, $this->url );
        curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $params );
        curl_setopt( $this->ch, CURLOPT_POST, 1 );
        $res3 = $this->curlexec();
        $table = explode('<table border="0" cellpadding="0" cellspacing="0" class="CommonTableClass" id="s3_table">
               <tr id="s3_tr">
                  <td id="s3"><span id="Header" class="BodytextCol1">Nomor Rekening</span><span id="H" class="BodytextCol2">00000000218772641</span></td>
               </tr>
            </table><div class="CommonTableClass" id="ComboList_div" ><br/></div>', $res3);
        if (empty($table[1])) return false;
        $table = explode('</table><div class="CommonTableClass" id="linebreak_div" ><br/></div></div><div id="quickLinkBar" name="quickLinkBar" class="quickStyle"><table cellpadding="0" cellspacing="0" class="CommonTableClass" border="0" id="message_table">', $table[1]);
        if (empty($table[0])) return false;
        $table = $table[0];
        $row = explode('<span id="Header" class="clsLine"></span><span id="H" class="BodytextCol2"></span></td>
               </tr>
            </table>', $table);
        // if (empty($row[0])) return false;
        unset($row[0]);
        $rows = array();
        foreach ($row as $n => $r) {
            $tanggal = explode('Tanggal Transaksi</span><span id="H" class="BodytextCol2">', $r);
            if (empty($tanggal[1])) return false;
            $tanggal = explode('</span>', $tanggal[1]);
            if (empty($tanggal[0])) return false;
            $tanggal = $tanggal[0];
            $tanggal = date('Y-m-d', strtotime($tanggal));

            $desc = explode('Uraian Transaksi</span><span id="H" class="BodytextCol2">', $r);
            if (empty($desc[1])) return false;
            $desc = explode('</span>', $desc[1]);
            if (empty($desc[0])) return false;
            $desc = $desc[0];

            $amount = explode('Jumlah Pembayaran</span><span id="H" class="BodytextCol2">', $r);
            if (empty($amount[1])) return false;
            $amount = explode('</span>', $amount[1]);
            if (empty($amount[0])) return false;
            $amount = $amount[0];
            $amount = str_replace(',','.',str_replace('.','',trim($amount)));

            $type = explode('<span id="Header" class="BodytextCol1">Tipe</span>', $r);
            if (empty($type[1])) return false;
            $type = explode('</span>', $type[1]);
            if (empty($type[0])) return false;
            $type = strip_tags($type[0]);

            if ($type == 'Db') {
                $debet = $amount;
                $kredit = '0.00';
            } else {
                $debet = '0.00';
                $kredit = $amount;
            }

            $detail = new stdClass;
            $detail->date         = $tanggal;
            $detail->description  = $desc;
            $detail->debet        = $debet;
            $detail->kredit       = $kredit;
            $rows[] = $detail;
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

/* End of file BNIParser.php */
/* Location: ./application/libraries/parser/BNIParser.php */
