<?php

/**
 * Created by NextPay.ir
 * author: Nextpay Company
 * ID: @nextpay
 * Date: 09/29/2016
 * Time: 3:25 PM
 * Website: NextPay.ir
 * Email: info@nextpay.ir
 * @copyright 2016
 * @package NextPay_Gateway
 * @version 1.0
 */
/*
  Plugin Name: حمایت مالی توسط درگاه امن نکست پی
  Plugin URI: http://www.nextpay.ir
  Description: افزونه حمایت مالی با پرداخت توسط درگاه واسط امن نکست پی - برای استفاده این افزونه فقط کافیست درون بخشی از برگه یا نوشته خود این عبارت [NextPayDonate] را قرار دهید
  Author: Nextpay Company
  Version: 1.0
  Author URI: http://www.nextpay.ir
*/

defined('ABSPATH') or die('Access denied!');
define ('NextPayDonateDIR', plugin_dir_path( __FILE__ ));
define ('TABLE_DONATE'  , 'nextpay_donate');

require_once ABSPATH . 'wp-admin/includes/upgrade.php';

if ( is_admin() )
{
        add_action('admin_menu', 'ENPD_AdminMenuItem');
        function ENPD_AdminMenuItem()
        {
	add_menu_page( 'تنظیمات افزونه حمایت مالی - نکست پی', 'حمایت مالی', 'administrator', 'ENPD_MenuItem', 'ENPD_MainPageHTML', /*plugins_url( 'myplugin/images/icon.png' )*/'', 6 ); 
        add_submenu_page('ENPD_MenuItem','نمایش حامیان مالی','نمایش حامیان مالی', 'administrator','ENPD_Hamian','ENPD_HamianHTML');
        }
}

function ENPD_MainPageHTML()
{
	include('ENPD_AdminPage.php');
}

function ENPD_HamianHTML()
{
	include('ENPD_Hamian.php');
}


add_action( 'init', 'NextPayDonateShortcode');
function NextPayDonateShortcode(){
	add_shortcode('NextPayDonate', 'NextPayDonateForm');
}

function NextPayDonateForm() {
  $out = '';
  $error = '';
  $message = '';
  
	$api_key = get_option( 'ENPD_api_key');
  $ENPD_IsOK = get_option( 'ENPD_IsOK');
  $ENPD_IsError = get_option( 'ENPD_IsError');
  $ENPD_Unit = get_option( 'ENPD_Unit');
  
  $Amount = '';
  $Description = '';
  $Name = '';
  $Mobile = '';
  $Email = '';
  
  //////////////////////////////////////////////////////////
  //            REQUEST
  if(isset($_POST['submit']) && $_POST['submit'] == 'پرداخت')
  {
    require_once( NextPayDonateDIR . '/nextpay_payment.php' );
    
    if($api_key == '')
    {
      $error = 'کلید مجوز دهی پرداخت وارد نشده است' . "<br>\r\n";
    }
    
    $order_id = time();
    
    $Amount = filter_input(INPUT_POST, 'ENPD_Amount', FILTER_SANITIZE_SPECIAL_CHARS);
    
    if(is_numeric($Amount) != false)
    {
      //Amount will be based on Toman  - Required
      if($ENPD_Unit == 'ریال')
        $SendAmount =  $Amount / 10;
      else
        $SendAmount =  $Amount;
    }
    else
    {
      $error .= 'مبلغ به درستی وارد نشده است' . "<br>\r\n";
    }
    
    $Description =    filter_input(INPUT_POST, 'ENPD_Description', FILTER_SANITIZE_SPECIAL_CHARS);
    $Name =           filter_input(INPUT_POST, 'ENPD_Name', FILTER_SANITIZE_SPECIAL_CHARS);
    $Mobile =         filter_input(INPUT_POST, 'mobile', FILTER_SANITIZE_SPECIAL_CHARS);
    $Email =          filter_input(INPUT_POST, 'email', FILTER_SANITIZE_SPECIAL_CHARS);
    
    if($error == '') // اگر خطایی نباشد
    {
      $CallbackURL = ENPD_GetCallBackURL();  // Required
      
      $data = array(
            'api_key' => $api_key,
            'order_id' => $order_id,
            'amount' => $SendAmount,
            'callback_uri' => $CallbackURL
        );
      $nextpay = new Nextpay_Payment($data);
      $result = $nextpay->token();
      if(intval($result->code) == -1) {
	  //$nextpay->send($result->trans_id);
	  $trans_id = $result->trans_id;
	  $nextpay_paymentpage = $nextpay->request_http . "/$trans_id";
	  
	   ENPD_AddDonate(array(
					'trans_id'     => $trans_id,
					'order_id'     => $order_id,
					'Name'          => $Name,
					'AmountTomaan'  => $SendAmount,
					'Mobile'        => $Mobile,
					'Email'         => $Email,
					'InputDate'     => current_time( 'mysql' ),
					'Description'   => $Description,
					'Status'        => 'SEND'
        ),array(
          '%s',
          '%s',
          '%d',
          '%s',
          '%s',
          '%s',
          '%s',
          '%s'
        ));
        
        
        return "<script>document.location = '${$nextpay_paymentpage}'</script><center>در صورتی که به صورت خودکار به درگاه بانک منتقل نشدید <a href='${$nextpay_paymentpage}'>اینجا</a> را کلیک کنید.</center>";

      } else {
	  $error .= ENPD_GetResaultStatusString($result->code) . "<br>\r\n";
      }
    }
  }
  //// END REQUEST
  
  
  ////////////////////////////////////////////////////
  ///             RESPONSE
  if(isset($_POST['trans_id']))
  {
    require_once( NextPayDonateDIR . '/nextpay_payment.php' );
    
    $trans_id = filter_input(INPUT_GET, 'trans_id', FILTER_SANITIZE_SPECIAL_CHARS);
    
    if(isset($_POST['order_id'])){
        $order_id = $_POST['order_id'];
      $Record = ENPD_GetDonate($trans_id,$order_id);
      if( $Record  === false)
      {
        $error .= 'چنین تراکنشی در سایت ثبت نشده است' . "<br>\r\n";
      }
      else
      {
        $nextpay = new Nextpay_Payment();
        $result = $nextpay->verify_request(array("api_key"=>$api_key,"order_id"=>$order_id,"amount"=>$Amount,"trans_id"=>$trans_id));        
        if(intval($result) == 0)
        {
          ENPD_ChangeStatus($trans_id, 'OK');
          $message .= get_option( 'ENPD_IsOk') . "<br>\r\n";
          $message .= 'کد پیگیری تراکنش:'. $trans_id . "<br>\r\n";
          $message .= 'شماره سفارش:'. $order_id . "<br>\r\n";
          
          $ENPD_TotalAmount = get_option("ENPD_TotalAmount");
          update_option("ENPD_TotalAmount" , $ENPD_TotalAmount + $Record['AmountTomaan']);
        } 
        else 
        {
          ENPD_ChangeStatus($trans_id, 'ERROR');
          $error .= get_option( 'ENPD_IsError') . "<br>\r\n";
          $error .= ENPD_GetResaultStatusString($result) . "<br>\r\n";
        }
      }
    } 
    else
    {
      $error .= 'تراکنش توسط کاربر بازگشت خورد';
      ENPD_ChangeStatus($trans_id, 'CANCEL');
    }
  }
  ///     END RESPONSE
  
  $style = '';
  
  if(get_option('ENPD_UseCustomStyle') == 'true')
  {
    $style = get_option('ENPD_CustomStyle');
  }
  else
  {
    $style = '#ENPD_MainForm {  width: 400px;  height: auto;  margin: 0 auto;  direction: rtl; }  #ENPD_Form {  width: 96%;  height: auto;  float: right;  padding: 10px 2%; }  #ENPD_Message,#ENPD_Error {  width: 90%;  margin-top: 10px;  margin-right: 2%;  float: right;  padding: 5px 2%;  border-right: 2px solid #006704;  background-color: #e7ffc5;  color: #00581f; }  #ENPD_Error {  border-right: 2px solid #790000;  background-color: #ffc9c5;  color: #580a00; }  .ENPD_FormItem {  width: 90%;  margin-top: 10px;  margin-right: 2%;  float: right;  padding: 5px 2%; }    .ENPD_FormLabel {  width: 35%;  float: right;  padding: 3px 0; }  .ENPD_ItemInput {  width: 64%;  float: left; }  .ENPD_ItemInput input {  width: 90%;  float: right;  border-radius: 3px;  box-shadow: 0 0 2px #00c4ff;  border: 0px solid #c0fff0;  font-family: inherit;  font-size: inherit;  padding: 3px 5px; }  .ENPD_ItemInput input:focus {  box-shadow: 0 0 4px #0099d1; }  .ENPD_ItemInput input.error {  box-shadow: 0 0 4px #ef0d1e; }  input.ENPD_Submit {  background: none repeat scroll 0 0 #2ea2cc;  border-color: #0074a2;  box-shadow: 0 1px 0 rgba(120, 200, 230, 0.5) inset, 0 1px 0 rgba(0, 0, 0, 0.15);  color: #fff;  text-decoration: none;  border-radius: 3px;  border-style: solid;  border-width: 1px;  box-sizing: border-box;  cursor: pointer;  display: inline-block;  font-size: 13px;  line-height: 26px;  margin: 0;  padding: 0 10px 1px;  margin: 10px auto;  width: 50%;  font: inherit;  float: right;  margin-right: 24%; }';
  }
  
  
	$out = '
  <style>
    '. $style . '
  </style>
      <div style="clear:both;width:100%;float:right;">
	        <div id="ENPD_MainForm">
          <div id="ENPD_Form">';
          
if($message != '')
{    
    $out .= "<div id=\"ENPD_Message\">
    ${message}
            </div>";
}

if($error != '')
{    
    $out .= "<div id=\"ENPD_Error\">
    ${error}
            </div>";
}

     $out .=      '<form method="post">
              <div class="ENPD_FormItem">
                <label class="ENPD_FormLabel">مبلغ :</label>
                <div class="ENPD_ItemInput">
                  <input style="width:60%" type="text" name="ENPD_Amount" value="'. $Amount .'" />
                  <span style="margin-right:10px;">'. $ENPD_Unit .'</span>
                </div>
              </div>
              
              <div class="ENPD_FormItem">
                <label class="ENPD_FormLabel">نام و نام خانوادگی :</label>
                <div class="ENPD_ItemInput"><input type="text" name="ENPD_Name" value="'. $Name .'" /></div>
              </div>
              
              <div class="ENPD_FormItem">
                <label class="ENPD_FormLabel">تلفن همراه :</label>
                <div class="ENPD_ItemInput"><input type="text" name="mobile" value="'. $Mobile .'" /></div>
              </div>
              
              <div class="ENPD_FormItem">
                <label class="ENPD_FormLabel">ایمیل :</label>
                <div class="ENPD_ItemInput"><input type="text" name="email" style="direction:ltr;text-align:left;" value="'. $Email .'" /></div>
              </div>
              
              <div class="ENPD_FormItem">
                <label class="ENPD_FormLabel">توضیحات :</label>
                <div class="ENPD_ItemInput"><input type="text" name="ENPD_Description" value="'. $Description .'" /></div>
              </div>
              
              <div class="ENPD_FormItem">
                <input type="submit" name="submit" value="پرداخت" class="ENPD_Submit" />
              </div>
            </form>
          </div>
        </div>
      </div>
	';
  
  return $out;
}

/////////////////////////////////////////////////
// تنظیمات اولیه در هنگام اجرا شدن افزونه.
register_activation_hook(__FILE__,'EriamNextPayDonate_install');
function EriamNextPayDonate_install()
{
	ENPD_CreateDatabaseTables();
}
function ENPD_CreateDatabaseTables()
{
		global $wpdb;
		$erimaDonateTable = $wpdb->prefix . TABLE_DONATE;
		// Creat table
		$nazrezohoor = "CREATE TABLE IF NOT EXISTS `$erimaDonateTable` (
					  `DonateID` int(11) NOT NULL AUTO_INCREMENT,
					  `trans_id` varchar(50) NOT NULL,
					  `order_id` varchar(50) NOT NULL,
					  `Name` varchar(50) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
					  `AmountTomaan` int(11) NOT NULL,
					  `Mobile` varchar(11) ,
					  `Email` varchar(50),
					  `InputDate` varchar(20),
					  `Description` varchar(100) CHARACTER SET utf8 COLLATE utf8_persian_ci,
					  `Status` varchar(5),
					  PRIMARY KEY (`DonateID`),
					  KEY `DonateID` (`DonateID`)
					) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
		dbDelta($nazrezohoor);
		// Other Options
		add_option("ENPD_TotalAmount", 0, '', 'yes');
		add_option("ENPD_TotalPayment", 0, '', 'yes');
		add_option("ENPD_IsOK", 'با تشکر پرداخت شما به درستی انجام شد.', '', 'yes');
		add_option("ENPD_IsError", 'متاسفانه پرداخت انجام نشد.', '', 'yes');
    
    $style = '#ENPD_MainForm {
  width: 400px;
  height: auto;
  margin: 0 auto;
  direction: rtl;
}

#ENPD_Form {
  width: 96%;
  height: auto;
  float: right;
  padding: 10px 2%;
}

#ENPD_Message,#ENPD_Error {
  width: 90%;
  margin-top: 10px;
  margin-right: 2%;
  float: right;
  padding: 5px 2%;
  border-right: 2px solid #006704;
  background-color: #e7ffc5;
  color: #00581f;
}

#ENPD_Error {
  border-right: 2px solid #790000;
  background-color: #ffc9c5;
  color: #580a00;
}

.ENPD_FormItem {
  width: 90%;
  margin-top: 10px;
  margin-right: 2%;
  float: right;
  padding: 5px 2%;
}

.ENPD_FormLabel {
  width: 35%;
  float: right;
  padding: 3px 0;
}

.ENPD_ItemInput {
  width: 64%;
  float: left;
}

.ENPD_ItemInput input {
  width: 90%;
  float: right;
  border-radius: 3px;
  box-shadow: 0 0 2px #00c4ff;
  border: 0px solid #c0fff0;
  font-family: inherit;
  font-size: inherit;
  padding: 3px 5px;
}

.ENPD_ItemInput input:focus {
  box-shadow: 0 0 4px #0099d1;
}

.ENPD_ItemInput input.error {
  box-shadow: 0 0 4px #ef0d1e;
}

input.ENPD_Submit {
  background: none repeat scroll 0 0 #2ea2cc;
  border-color: #0074a2;
  box-shadow: 0 1px 0 rgba(120, 200, 230, 0.5) inset, 0 1px 0 rgba(0, 0, 0, 0.15);
  color: #fff;
  text-decoration: none;
  border-radius: 3px;
  border-style: solid;
  border-width: 1px;
  box-sizing: border-box;
  cursor: pointer;
  display: inline-block;
  font-size: 13px;
  line-height: 26px;
  margin: 0;
  padding: 0 10px 1px;
  margin: 10px auto;
  width: 50%;
  font: inherit;
  float: right;
  margin-right: 24%;
}';
  add_option("ENPD_CustomStyle", $style, '', 'yes');
  add_option("ENPD_UseCustomStyle", 'false', '', 'yes');
}

function ENPD_GetDonate($trans_id,$order_id)
{
  global $wpdb;
  $trans_id = strip_tags($wpdb->escape($trans_id));
  $order_id = strip_tags($wpdb->escape($order_id));
  
  if($trans_id == '')
    return false;
  
	$erimaDonateTable = $wpdb->prefix . TABLE_DONATE;

  $res = $wpdb->get_results( "SELECT * FROM ${erimaDonateTable} WHERE trans_id = '${trans_id}' and order_id = '${order_id}' LIMIT 1",ARRAY_A);
  
  if(count($res) == 0)
    return false;
  
  return $res[0];
}

function ENPD_AddDonate($Data, $Format)
{
  global $wpdb;

  if(!is_array($Data))
    return false;
  
	$erimaDonateTable = $wpdb->prefix . TABLE_DONATE;

  $res = $wpdb->insert( $erimaDonateTable , $Data, $Format);
  
  if($res == 1)
  {
    $totalPay = get_option('ENPD_TotalPayment');
    $totalPay += 1;
    update_option('ENPD_TotalPayment', $totalPay);
  }
  
  return $res;
}

function ENPD_ChangeStatus($trans_id,$Status)
{
  global $wpdb;
  $trans_id = strip_tags($wpdb->escape($trans_id));
  $Status = strip_tags($wpdb->escape($Status));
  
  if($trans_id == '' || $Status == '')
    return false;
  
	$erimaDonateTable = $wpdb->prefix . TABLE_DONATE;

  $res = $wpdb->query( "UPDATE ${erimaDonateTable} SET `Status` = '${Status}' WHERE `trans_id` = '${trans_id}'");
  
  return $res;
}

function ENPD_GetResaultStatusString($StatusNumber)
{
  $error_code = intval($StatusNumber);
        $error_array = array(
            0 => "Complete Transaction",
	    -1 => "Default State",
	    -2 => "Bank Failed or Canceled",
	    -3 => "Bank Payment Pendding",
	    -4 => "Bank Canceled",
	    -20 => "api key is not send",
	    -21 => "empty trans_id param send",
	    -22 => "amount in not send",
	    -23 => "callback in not send",
	    -24 => "amount incorrect",
	    -25 => "trans_id resend and not allow to payment",
	    -26 => "Token not send",
	    -30 => "amount less of limite payment",
	    -32 => "callback error",
	    -33 => "api_key incorrect",
	    -34 => "trans_id incorrect",
	    -35 => "type of api_key incorrect",
	    -36 => "order_id not send",
	    -37 => "transaction not found",
	    -38 => "token not found",
	    -39 => "api_key not found",
	    -40 => "api_key is blocked",
	    -41 => "params from bank invalid",
	    -42 => "payment system problem",
	    -43 => "gateway not found",
	    -44 => "response bank invalid",
	    -45 => "payment system deactived",
	    -46 => "request incorrect",
	    -48 => "commission rate not detect",
	    -49 => "trans repeated",
	    -50 => "account not found",
	    -51 => "user not found"
        );
  
  return $error_array[$error_code];
}

function ENPD_GetCallBackURL()
{
  $pageURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
  
  $ServerName = htmlspecialchars($_SERVER["SERVER_NAME"], ENT_QUOTES, "utf-8");
  $ServerPort = htmlspecialchars($_SERVER["SERVER_PORT"], ENT_QUOTES, "utf-8");
  $ServerRequestUri = htmlspecialchars($_SERVER["REQUEST_URI"], ENT_QUOTES, "utf-8");
  
  if ($_SERVER["SERVER_PORT"] != "80")
  {
      $pageURL .= $ServerName .":". $ServerPort . $_SERVER["REQUEST_URI"];
  } 
  else 
  {
      $pageURL .= $ServerName . $ServerRequestUri;
  }
  return $pageURL;
}

?>