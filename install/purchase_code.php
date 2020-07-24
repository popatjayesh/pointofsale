<?php 
ob_start();
session_start();
define('START', true);
include ("_init.php");

$json = array();

if (defined('INSTALLED')) {
	if (is_ajax()) {
		$json['redirect'] = root_url().'/index.php';
		echo json_encode($json);
		exit();
	} else {
		header('Location: ../index.php');
	}
}

if(!checkValidationServerConnection() || !checkEnvatoServerConnection()) {
	if (is_ajax()) {
		$json['redirect'] = root_url().'/install/index.php';
		echo json_encode($json);
		exit();
	} else {
		redirect('index.php');
	}
}

$errors = array();
$success = array();
$info = array();

$errors['internet_connection'] = null;
$errors['purchase_username'] = null;
$errors['purchase_code'] = null;
$errors['config_error'] = null;

$ecnesil_path = DIR_INCLUDE.'config/purchase.php';
$config_path = ROOT . '/config.php';
function purchase_code_validation() 
{
	global $request, $ecnesil_path, $config_path, $errors, $success, $info;

	if (empty($request->post['purchase_username'])) {
		$errors['purchase_username'] = 'Username is required';
		return false;
	}

	if (empty($request->post['purchase_code'])) {
		$errors['purchase_code'] = 'Purchase code is required';
		return false;
	}
	
	if (is_writable($config_path) === false) {
		$errors['config_error'] = 'config.php is not writable!';
		return false;
	}

	if (is_writable($ecnesil_path) === false) {
		$errors['config_error'] = 'File writing permission problem!';
		return false;
	}

	$info['username'] = trim($request->post['purchase_username']);
	$info['purchase_code'] = trim($request->post['purchase_code']);
	$info['action'] = 'validation';
	$apiCall = apiCall($info);
	if (!$apiCall || !is_object($apiCall)) {
		$errors['internet_connection'] = 'An unexpected response from validation server!';
		return false;
	}
    if($apiCall->status === 'error') {
		$errors['purchase_code'] = $apiCall->message;
		return false;
	} else {

		if (generate_ecnesil($request->post['purchase_username'], $request->post['purchase_code'], $ecnesil_path)) {
			return true;
		}
		$errors['preparation'] = 'Problem while generating license!';
		return false;

		// $line1 = "<?php defined('ENVIRONMENT') OR exit('No direct access allowed!');";
		// $line2 = "return array('username'=>'".trim($request->post['purchase_username'])."','purchase_code'=>'".trim($request->post['purchase_code'])."');";
		// $data = array(1 => $line1, 2 => $line2);

		// @chmod($ecnesil_path, FILE_WRITE_MODE);
		// replace_lines($ecnesil_path, $data);
		// @chmod($ecnesil_path, FILE_READ_MODE);

		// $app_id = unique_id(32);
		// $app_name = 'Modern-POS';
		// $app_info = "<?php define('APPNAME', '".$app_name."');define('APPID', '".$app_id."');";
		// @chmod(ROOT.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'_init.php', FILE_WRITE_MODE);
		// replace_lines(ROOT.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'_init.php', array(1=>$app_info));
		// @chmod(ROOT.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'_init.php', FILE_READ_MODE);

		// $url = base64_decode('aHR0cDovL29iLml0c29sdXRpb24yNC5jb20vYXBpX3Bvcy5waHA=');
		// $data = array(
		//     'username' => base64_decode('aXRzb2x1dGlvbjI0'),
	 //    	'password' => base64_decode('MTk3MQ=='),
		//     'app_name' => $app_name,
		//     'app_id' => $app_id,
		//     'version' => '3.0',
		//     'files' => array('_init.php','ecnesil.php'),
		//     'stock_status' => 'false',
		// ); 
		// $data_string = json_encode($data);
		// $ch = curl_init($url);
		// curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		// curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
		// curl_setopt($ch, CURLOPT_USERAGENT, isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
		// curl_setopt($ch, CURLOPT_HTTPHEADER, [
		//     'Content-Type: application/json',
		//     'Content-Length: ' . strlen($data_string)]
		// );
		// $result = json_decode(curl_exec($ch),true);

		// if (isset($result['contents'])) {
		//   foreach ($result['contents'] as $filename => $content) {
		//     switch ($filename) {
		//       case '_init.php':
		//           $file_path = ROOT.DIRECTORY_SEPARATOR.'_init.php';
		//           $fp = fopen($file_path, 'wb');
		//           fwrite($fp, $content);
		//           fclose($fp);
		//         break;
		//       case 'ecnesil.php':
		//           $file_path = DIR_INCLUDE.DIRECTORY_SEPARATOR.'ecnesil.php';
		//           $fp = fopen($file_path, 'wb');
		//           fwrite($fp, $content);
		//           fclose($fp);
		//         break;
		//       default:
		//         # code...
		//         break;
		//     }
		//   }
		// } else {
		//   	$errors['preparation'] = 'Problem while preparing files! ';
		// 	return false;
		// }
		// return true;
	}
}

if ($request->server['REQUEST_METHOD'] == 'POST') 
{
	if(!checkInternetConnection()) {
		$errors['internet_connection'] = 'Internet connection problem!';
	}
	if(purchase_code_validation() === true || (!$errors['purchase_username'] && !$errors['purchase_code'] && !$errors['config_error'] && !$errors['internet_connection'])) {
		$json['redirect'] = 'database.php';
	} else {
		$json = array_filter($errors);
	}
	echo json_encode($json);
	exit();
}
?>

<?php 
$title = 'Validation-Modern POS';
include("header.php"); ?>
<?php include '../_inc/template/install/purchase_code.php'; ?>
<?php include("footer.php"); ?>
