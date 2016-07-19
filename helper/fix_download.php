<?php
	use GuzzleHttp\Client;
	use Psr\Http\Message\ResponseInterface;
	use GuzzleHttp\Exception\RequestException;
	
	use Symfony\Component\Console\Output\ConsoleOutput;
	use Symfony\Component\Console\Formatter\OutputFormatter;
	use Symfony\Component\Console\Helper\ProgressBar;
	
	$output = new ConsoleOutput();
	$output -> setFormatter(new OutputFormatter(true));
	
	$progress_bar = null;
	
	function fix_download_apk() {
		$path = "./helper/files/apkmirror/";
		
		$file_path = "./helper/files/apkmirror/error_download_list.txt";
		$apks = array();
		
		if(file_exists($file_path)) {
			$handle = fopen($file_path, "r");
			
			$index = 0;
			while(!feof($handle)) {
				$download_url = fgets($handle, 4096);
				$apks[$index] = $download_url;
				$index++;
			}
			
			foreach($apks as $value) {
				request_download_link($path, $file_path, $value);
			}
			
			fclose($handle);
		}
		
		$path = "./helper/files/androidapksfree/";
		
		$file_path = "./helper/files/androidapksfree/error_download_list.txt";
		
		if(file_exists($file_path)) {
			$handle = fopen($file_path, "r");
			
			$apks = array();
			$index = 0;
			while(!feof($handle)) {
				$download_url = fgets($handle, 4096);
				$apks[$index] = $download_url;
				$index++;
			}
			
			foreach($apks as $value) {
				request_download_link($path, $file_path, $value);
			}
			
			fclose($handle);
		}
	}
	
	function request_download_link($path, $file_path, $link) {
		
		$client = new Client(['headers' => ['Keep-Alive' => '1000', 'Connection' => 'keep-alive']]);
		$resource = fopen($path . , 'w+');
		
		global $output;
		initial_bar();
			
		try {
			$response = $client -> request('GET', $url . $link, ["verify" => false, "sink" => $resource, 'progress' => 
				function ($download_size, $downloaded_size, $upload_size, $uploaded_size) {
					// present the progress string
					if($download_size !=0) {
						$number = round(100 - (abs($download_size - $downloaded_size - 100) / $download_size * 100), 2);
						//echo "Progress: " . round(100 - (abs($download_size - $downloaded_size - 100) / $download_size * 100), 2) . "%\n";
						global $progress_bar;
						$progress_bar -> setProgress((int)$number);
					}
				}
			]);
				
			global $progress_bar;
			$progress_bar -> finish();
			file_put_contents($file_lists_path, $file_name . "\r\n", FILE_APPEND);
		}
		catch(Exception $e) {
			$output -> writeln($e -> getMessage());
		}
	}
	
	function get_file_name($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
		$last_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
		curl_close($ch);
		$arr = explode("/", $last_url);
		$file_name = $arr[count($arr) - 1];
		return $file_name;
	}
	
	//initial progress bar
	function initial_bar() {
		global $progress_bar;
		global $output;
		$output -> writeln('');
		
		$progress_bar = new ProgressBar($output, 100);
		$progress_bar -> setOverwrite(true);
		$progress_bar -> start();
	}
?>