<?php defined('BASEPATH') OR exit('No direct script access allowed');

class SkripayParser {
	protected $ci;

	public function __construct() {
        $this->ci =& get_instance();
        date_default_timezone_set('Asia/Jakarta');
        $this->conf['ip']       = json_decode( file_get_contents( 'http://myjsonip.appspot.com/' ) )->ip;
        $this->conf['time']     = time();
        $this->conf['path']     = dirname( __FILE__ );
        $this->conf['days']     = 28; //rentang waktu (hari) pengambilan catatan transaksi/mutasi. maksimal 29 hari
        $this->ci->load->library('encrypt');
	}

	function instantiate($bank) { //instansiasi
        $class = $bank.'Parser';
        $this->ci->load->library('parser/'.$class, $this->conf, 'bank'); //load library parser
    }

    function loginCheck($bank, $username, $password) {
        $username = $this->ci->encrypt->decode($username);
        $password = $this->ci->encrypt->decode($password);
        $this->instantiate($bank);
        $result = $this->ci->bank->loginCheck($username, $password);
        return $result;
    }

    function getDetails($bank, $username, $password) {
        $username = $this->ci->encrypt->decode($username);
        $password = $this->ci->encrypt->decode($password);
        $this->instantiate($bank);
        $this->ci->bank->login($username, $password);
        $details = $this->ci->bank->getDetails();
        $this->ci->bank->logout();
        return $details;
    }

    function getBalance($bank, $username, $password) {
        $username = $this->ci->encrypt->decode($username);
        $password = $this->ci->encrypt->decode($password);
        $this->instantiate($bank);
        $this->ci->bank->login($username, $password);
        $balance = $this->ci->bank->getBalance();
        $this->ci->bank->logout();
        return $balance;
    }

    function getTransactions($bank, $username, $password) {
        $username = $this->ci->encrypt->decode($username);
        $password = $this->ci->encrypt->decode($password);
        $this->instantiate($bank);
        $this->ci->bank->login($username, $password);
        $transactions = $this->ci->bank->getTransactions();
        $this->ci->bank->logout();
        return $transactions;
    }

    function findTransaction($bank, $username, $password, $amount) {
        $username = $this->ci->encrypt->decode($username);
        $password = $this->ci->encrypt->decode($password);
        if (!is_numeric($amount)) return false; 
        $this->instantiate($bank);
        $this->ci->bank->login($username, $password);
        $result = $this->ci->bank->findTransaction($amount);
        $this->ci->bank->logout();
        return $result;
    }

}

/* End of file skripayParser.php */
/* Location: ./application/libraries/skripayParser.php */
