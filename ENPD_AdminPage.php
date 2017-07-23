<?php

defined('ABSPATH') or die('Access denied!');

if ( $_POST ) {

	if ( isset($_POST['ENPD_api_key']) ) {
		update_option( 'ENPD_api_key', $_POST['ENPD_api_key'] );
	}

	if ( isset($_POST['ENPD_IsOK']) ) {
		update_option( 'ENPD_IsOK', $_POST['ENPD_IsOK'] );
	}

	if ( isset($_POST['ENPD_IsError']) ) {
		update_option( 'ENPD_IsError', $_POST['ENPD_IsError'] );
	}

  if ( isset($_POST['ENPD_Unit']) ) {
		update_option( 'ENPD_Unit', $_POST['ENPD_Unit'] );
	}

  if ( isset($_POST['ENPD_UseCustomStyle']) ) {
		update_option( 'ENPD_UseCustomStyle', 'true' );

    if ( isset($_POST['ENPD_CustomStyle']) )
    {
      update_option( 'ENPD_CustomStyle', strip_tags($_POST['ENPD_CustomStyle']) );
    }

	}
  else
  {
    update_option( 'ENPD_UseCustomStyle', 'false' );
  }

	echo '<div class="updated" id="message"><p><strong>تنظیمات ذخیره شد</strong>.</p></div>';

}
//XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
?>
<h2 id="add-new-user">تنظیمات افزونه حمایت مالی - نکست پی</h2>
<h2 id="add-new-user">جمع تمام پرداخت ها : <?php echo get_option("ENPD_TotalAmount"); ?>  تومان</h2>
<form method="post">
  <table class="form-table">
    <tbody>
      <tr class="user-first-name-wrap">
        <th><label for="ENPD_api_key">کلید مجوز دهی پرداخت</label></th>
        <td>
          <input type="text" class="regular-text" value="<?php echo get_option( 'ENPD_api_key'); ?>" id="ENPD_api_key" name="ENPD_api_key">
          <p class="description indicator-hint">XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX</p>
        </td>
      </tr>
      <tr>
        <th><label for="ENPD_IsOK">پرداخت صحیح</label></th>
        <td><input type="text" class="regular-text" value="<?php echo get_option( 'ENPD_IsOK'); ?>" id="ENPD_IsOK" name="ENPD_IsOK"></td>
      </tr>
      <tr>
        <th><label for="ENPD_IsError">خطا در پرداخت</label></th>
        <td><input type="text" class="regular-text" value="<?php echo get_option( 'ENPD_IsError'); ?>" id="ENPD_IsError" name="ENPD_IsError"></td>
      </tr>

      <tr class="user-display-name-wrap">
        <th><label for="ENPD_Unit">واحد پول</label></th>
        <td>
          <?php $ENPD_Unit = get_option( 'ENPD_Unit'); ?>
          <select id="ENPD_Unit" name="ENPD_Unit">
            <option <?php if($ENPD_Unit == 'تومان' ) echo 'selected="selected"' ?>>تومان</option>
            <option <?php if($ENPD_Unit == 'ریال' ) echo 'selected="selected"' ?>>ریال</option>
          </select>
        </td>
      </tr>

      <tr class="user-display-name-wrap">
        <th>استفاده از استایل سفارشی</th>
        <td>
          <?php $ENPD_UseCustomStyle = get_option('ENPD_UseCustomStyle') == 'true' ? 'checked="checked"' : ''; ?>
          <input type="checkbox" name="ENPD_UseCustomStyle" id="ENPD_UseCustomStyle" value="true" <?php echo $ENPD_UseCustomStyle ?> /><label for="ENPD_UseCustomStyle">استفاده از استایل سفارشی برای فرم</label><br>
        </td>
      </tr>


      <tr class="user-display-name-wrap" id="ENPD_CustomStyleBox" <?php if(get_option('ENPD_UseCustomStyle') != 'true') echo 'style="display:none"'; ?>>
        <th>استایل سفارشی</th>
        <td>
          <textarea style="width: 90%;min-height: 400px;direction:ltr;" name="ENPD_CustomStyle" id="ENPD_CustomStyle"><?php echo get_option('ENPD_CustomStyle') ?></textarea><br>
        </td>
      </tr>

    </tbody>
  </table>
  <p class="submit"><input type="submit" value="به روز رسانی تنظیمات" class="button button-primary" id="submit" name="submit"></p>
</form>

<script>
  if(typeof jQuery == 'function')
  {
    jQuery("#ENPD_UseCustomStyle").change(function(){
      if(jQuery("#ENPD_UseCustomStyle").prop('checked') == true)
        jQuery("#ENPD_CustomStyleBox").show(500);
      else
        jQuery("#ENPD_CustomStyleBox").hide(500);
    });
  }
</script>
