<?php
/*

   Plugin Name: Contact Form Mail PDF
   Plugin URI: http://wordpress.org/extend/plugins/contact-form-7-pdf-extension/
   Version: 1.2
   Author: Anjit Vishwakarma
   Author URI:anjitvishwakarma28.wordpress.com
   Description: Send form Data to the mail in PDF attachment from <a href="http://wordpress.org/extend/plugins/contact-form-7/">Contact Form 7</a>, Click <a href="admin.php?page=contact_mail_pdf">Settings</a> To start
   Text Domain: contact-form-7-pdf-extension
   License: GPL3
  */

// Add admin option

add_action( 'admin_menu', 'conf7_pdf_init' );


	function conf7_pdf_init() {

	add_menu_page('contact_mail_pdf', "Contact Form7 Mail-PDF", "manage_options", "conf7_mail_pdf_ext", "conf7_mail_pdf_ext",plugins_url('ico.png',__FILE__));
	}

	function conf7_mail_pdf_ext(){
?>

<h1>Contact Form 7 Data Mail As PDF </h1>
<h2>Settings</h2>
<form method="post" id="cnf_form"  >
	<div class="form-group">
	<table class="form-table">
	<tbody>
		<tr>
		<th scope="row">Select Contact form </th>
		<td>
			<fieldset><legend class="screen-reader-text"><span>Select Contact form </span></legend>
			<select name="cnf_frm_id"  onchange="change_form(this.value);">
			<option value="">Select Form</option>
			<?php 
				$args =array(
				'post_type'=>'wpcf7_contact_form',
				'posts_per_page'=>-1,
				);

		$contacts_forms = get_posts($args);
		foreach($contacts_forms as $contacts_form):
		$default_form_content[]= get_post_meta($contacts_form->ID,'_form',true);
		?>
		<option <?php if($_REQUEST['cnf_frm_id']==$contacts_form->ID) echo 'selected' ;?> value="<?php echo $contacts_form->ID; ?>"><?php echo $contacts_form->post_title; ?></option>
		<?php endforeach; wp_reset_query(); ?>
		</select>
		<br>
		</fieldset>
		</td>
		</tr>
		<tr>
		<th scope="row">PDF Templates</th>
		<td>
			<fieldset><legend class="screen-reader-text"><span>PDF Templates</span></legend>
			<?php
			if($_REQUEST['cnf_frm_id']):
			$args =array(
				'post_type'=>'wpcf7_contact_form',
				'posts_per_page'=>-1,
				'p'=> $_REQUEST['cnf_frm_id']
				);
				$contact_form = get_posts($args);
				foreach($contact_form as $contact_form):
				$default_form_content= get_post_meta($contact_form->ID,'_form',true);
				endforeach;
				keys_Extractor($default_form_content);
				else: 
				 keys_Extractor($default_form_content[0]);
				endif;
			?>
			<?php $in_db = get_page_by_title($_REQUEST['cnf_frm_id'], OBJECT, 'cnf_mail_template');?>

			<?php wp_editor( $in_db->post_content,'cnf_mail_Content', $settings );?>
			<br>
			</fieldset>
		</td>
		</tr>
	
	</tbody>
	</table>
<?php submit_button(); ?>
	</div>
</form>
<p style="font-size: 19px;text-align: center;">Do you want full plugin?</br>Please Contact me <a href="http://helponsoftware.com/contact/" target="blank">Anjit Vishwakarma</a></p>
<?php }?>
<?php
if(isset($_REQUEST["submit"])){ 

	// checking the form data is already in database or not

	$in_db = get_page_by_title($_REQUEST['cnf_frm_id'], OBJECT, 'cnf_mail_template');
  
   if($in_db->ID):
   	$cnf_mail_post = array(
    	   'ID'			=>$in_db->ID,
    	   'post_type'   => 'cnf_mail_template',
           'post_content'=>$_REQUEST['cnf_mail_Content'],
           'post_status' =>'publish',
           'post_author'   => 1,
		  );
		wp_update_post($cnf_mail_post, $wp_error );
		
   	else:
	
		  $cnf_mail_post = array(
    	   'post_title'  =>sanitize_text_field($_REQUEST['cnf_frm_id']),
    	   'post_type'   => 'cnf_mail_template',
           'post_content'=>sanitize_text_field($_REQUEST['cnf_mail_Content']),
           'post_status' =>'publish',
           'post_author'   => 1,
		  );
		wp_insert_post($cnf_mail_post, $wp_error );
	endif;
	}

/* sending the attachments with email by- Anjit vishwakarma */

add_action( 'wpcf7_before_send_mail', 'send_conf7_attachment_file',10, 1 );
 
 	function send_conf7_attachment_file($cf7){

 //check if this is the right form ID
  	// ...
 	if ($cf7->mail['use_html']==true) $nl="<br/>"; else $nl="\n";
 
 	
 	// getting all submitted data
 	$submission = WPCF7_Submission::get_instance();
	$data = $submission->get_posted_data();

	
	unset($data[_wpcf7]);
	unset($data[_wpcf7_version]);
	unset($data[_wpcf7_locale]);
	unset($data[_wpcf7_is_ajax_call]);
	unset($data[_wpcf7_unit_tag]);
	
	// making the html
	$in_db = get_page_by_title($cf7->id, OBJECT, 'cnf_mail_template');
	
	$html .=$in_db->post_content;
	foreach($data as $key=>$value):
	if(is_array($value)):
	$html =str_replace('#'.$key,$value[0],$html);
	else:	
	$html = str_replace('#'.$key,$value,$html);
	endif;
	endforeach;	
	

	// library for genrating the pdf file 
	require('pdflib/html2fpdf.php');
	$pdf=new HTML2FPDF();
	$pdf->AddPage();
	$strContent = $html;
	$pdf->WriteHTML($strContent);
	$pdf->Output("wp-content/uploads/form-". $cf7->id .".pdf");
	

	//I omitted all the stuff used to create
 	//the pdf file, you have just to know that
 	//$pdf_filename contains the filename to attach
 	//Let'go to the file attachment!

	// $pdf_filename with the extenstion not just the filename
 	$pdf_filename = "form-". $cf7->id.".pdf";

	// geting the real mail /////
	$mail = $cf7->prop('mail');  

	// giving the attachment path//// 
	$mail['attachments']='uploads/'.$pdf_filename;

	$cf7->set_properties(array("mail" => $mail));
	
		
  	
}

function keys_Extractor($form_content){

preg_match_all('/\[([^\]]*)\]/',$form_content, $out);
			    array_pop($out[1]);
				
			echo '<p> Form Keys : Use below keys in Mail template</p>';
				foreach($out[1] as $key=>$value):
				$value= explode(' ',$value);
				echo '<b>#'.$value[1].',</b> ';
				endforeach;
			echo '<p>&nbsp;</p>';
}

function admin_inline_js(){
	echo "<script type='text/javascript'>\n";
	echo "function change_form(val){
		 window.location.href = window.location.href + '&cnf_frm_id='+ val;
		  }";
	echo "\n</script>";
}
add_action( 'admin_print_scripts', 'admin_inline_js' );