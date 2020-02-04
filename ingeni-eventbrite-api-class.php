<?php


class IngeniEventbriteApi {
	private $eb_api_private_token;

	public function __construct( $private_token ) {
		$this->eb_api_private_token = $private_token;
	}

	private function is_local() {
		$local_install = false;
		if ( ($_SERVER['SERVER_NAME']=='localhost') || ( stripos($_SERVER['SERVER_NAME'],'dev.local') !== false ) ) {
			$local_install = true;
		}
		return $local_install;
	}


	private function fb_log($msg) {
		$upload_dir = wp_upload_dir();
		$outFile = $upload_dir['basedir'];
		if ( is_local() ) {
			$outFile .= DIRECTORY_SEPARATOR;
		} else {
			$outFile .= DIRECTORY_SEPARATOR;
		}
		$outFile .= basename(__DIR__).'.txt';
		
		date_default_timezone_set(get_option('timezone_string'));

		// Now write out to the file
		$log_handle = fopen($outFile, "a");
		if ($log_handle !== false) {
			fwrite($log_handle, date("Y-m-d H:i:s").": ".$msg."\r\n");
			fclose($log_handle);
		}
	}	


	private function ingeni_eb_connect( $url, &$errMsg ) {
		try {
			$return_json = "";

			$request_headers = [
				'Authorization: Bearer '. $this->eb_api_private_token
			];

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

			$return_data = curl_exec($ch);

			if (curl_errno($ch)) {
				$errMsg = curl_error($ch);
			} else {
				$return_json = json_decode($return_data, true);
			}

			// Show me the result
			curl_close($ch);

		} catch (Exception $ex) {
			$errMsg = $ex->Message;
		}
		return $return_json;
	}



	public function get_eb_events( $test = false, &$errMsg ) {
		$json = "";

		try {
			$url = "https://www.eventbriteapi.com/v3/users/me/events/?status=live&time_filter=current_future";
			$json = $this->ingeni_eb_connect( $url, $errMsg );

		} catch (Exception $ex) {
			$errMsg = $ex->Message;
		}
		return $json;
	}

} ?>