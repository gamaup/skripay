# skripay
Codeigniter Library for Internet Banking Website CURL Services and Parser

### Supported Banks
- Mandiri
- BCA
- BNI

### Install
- Put **SkripayParser.php** and **parser** folder to library folder
- Load library $this->load->library('skripayparser')

### Methods
- getDetails('bank_name','username','password')
- getBalance('bank_name','username','password')
- getTransactions('bank_name','username','password')
- findTransaction('bank_name','username','password','amount)

### Example
- $this->skripayparser->getDetails('Mandiri','gamaup','123456')
