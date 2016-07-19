<?php
	use GuzzleHttp\Client;
	use Psr\Http\Message\ResponseInterface;
	use GuzzleHttp\Exception\RequestException;
	use Symfony\Component\DomCrawler\Crawler;
	
	use Symfony\Component\Console\Output\ConsoleOutput;
	use Symfony\Component\Console\Formatter\OutputFormatter;
	use Symfony\Component\Console\Helper\ProgressBar;
	
	$output = new ConsoleOutput();
	$output -> setFormatter(new OutputFormatter(true));
	
	$progress_bar = null;
	
	$detect_os = PHP_OS;
	
	$messages = array(
		'cannot find the file_lists.txt',
		'downloading the apk files...',
		'The Network error happened.',
		'the apk file is existed.',
		'wake up !',
	);
	
	/*
		@request_url: http://www.apkmirror.com/
		@author: peter
		@date: 2016/07/06
		functions:
			parse_apkmirror_html($urls, $html_contents)
			get_apkmirror_pages($html_contents)
			get_apkmirror_apk($url, $html_contents)
			apkmirror_file_name($url)
			download_apkmirror_file($url, $link)
	*/
	
	//seed the random number generator
	srand();
	
	function parse_apkmirror_html($urls, $html_contents) {
		$crawler = new Crawler($html_contents);
		$link = $crawler -> filter('a[class="fontBlack"]');
		$count = 0;
		$links = array();

		foreach ($link as $key => $value) {
			$crawler = new Crawler($value);
			$url = $crawler -> filter('a') -> attr('href');
			$res = in_array($url, $links);
				
			if(!$res && $res != "/uploads/") {
				$links[$count] = $url;
				$count += 1;	
			}
		}

		foreach ($links as $value) {
			$client = new Client();
			try {
				$response = $client -> get($urls . $value);
				get_apkmirror_apk($urls, $response -> getBody() -> getContents());
			}
			catch(Exception $e) {
				global $output;
				global $detect_os;
				if($detect_os != "WINNT")
					$output -> writeln('<error>' . $e -> getMessage() . '</error>');
				else
					$output -> writeln($e -> getMessage());
				sleep_rand();
				die();
			}
		}
	}

	function get_apkmirror_pages($html_contents) {
		$crawler = new Crawler($html_contents);
		$pages = $crawler -> filter('span.pages');
		$page = $pages -> text();
		$page = str_replace("Page 1 of ", "", $page);
		return $page;
	}

	function get_apkmirror_apk($url, $html_contents) {
	 	$crawler = new Crawler($html_contents);
		
		//getting title whether the title is correct.
		$title = $crawler -> filter('title') -> text();
		
		if($title == "Latest Uploads - APKMirror") {
			//abort downloading apk file
			return false;
		}
		
		try {
			$download_direct = $crawler -> filter('a[data-google-vignette="false"]');
			$link = $download_direct -> attr('href');
		}
		catch(Exception $e) {
			global $output;
			global $detect_os;
			if($detect_os != "WINNT")
				$output -> writeln('<error>' . $e -> getMessage() . '</error>');
			else
				$output -> writeln($e -> getMessage());
			
			file_put_contents("./helper/files/apkmirror/error_link_" . time() . ".txt", $html_contents, FILE_APPEND);
			//abort downloading apk file.
			die();
		}
		
	 	if ($link == "#downloads") {
	 		//finding correct download link
			//e.g.(apkmirror) /apk/facebook-2/facebook/facebook-85-0-0-5-70-release/facebook-85-0-0-5-70-android-apk-download/
			$find_download = $crawler -> filter('div[class="table-cell rowheight addseparator expand pad dowrap"]');
			
			foreach($find_download as $key => $value) {
				$crawler = new Crawler($value);
				$link = $crawler -> filter('a') -> attr('href');
				break;
			}
			
			$client = new Client();
            $response = $client -> get($url . $link);
			get_apkmirror_apk($url, $response -> getBody() -> getContents());
	 	}
		
		else {
			download_apkmirror_file($url, $link);
		}
	}
	
	//get the default apk file name
	function apkmirror_file_name($url) {
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
	
	//downloading apk directly
	function download_apkmirror_file($url, $link) {
	
		$file_name = apkmirror_file_name($url . $link);
		$file_name = trim($file_name);
		
		$file_path = "./helper/files/apkmirror/" . $file_name;
		
		$is_exists = false;
		
		$file_lists_path = "./helper/files/apkmirror/file_lists.txt";
		$handle = @fopen($file_lists_path, "r");
		
		global $output;
		global $detect_os;
		global $messages;
		
		if(!$handle) {
			if($detect_os != "WINNT")
				$output -> writeln('<error>' . $messages[0] . '</error>');
			else
				$output -> writeln($messages[0]);
		}
		else {
			while(!feof($handle)) {
				$str = fgets($handle, 4096);
				$str = trim($str);
				
				if($str == $file_name) {
					$is_exists = true;
					fclose($handle);
					break;
				}
			}
		}
		
		if(!file_exists($file_path) && !$is_exists) {
			
			if($detect_os != "WINNT") {
				$output -> writeln('<info>' . $messages[1] . '</info>');
				
				$output -> writeln('<info>' . $file_name . '</info>');
			}
			else {
				$output -> writeln($messages[1]);
				
				$output -> writeln($file_name);

			}
			
			$client = new Client(['headers' => ['Keep-Alive' => '1000', 'Connection' => 'keep-alive']]);
			$resource = fopen($file_path, 'w+');
			
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
				file_put_contents("./helper/files/apkmirror/error_download_list.txt", $url . $link . "\r\n", FILE_APPEND);
				
				if($detect_os != "WINNT") {
					
					$output -> writeln('');
					
					$output -> writeln('<error>' . "error download: " . $file_name . '</error>');
					
					$output -> writeln('<error>' . $messages[2] . '</error>');

					$output -> writeln('<error>' . $e -> getMessage() . '</error>');
				}
				else {
					$output -> writeln("error download: " . $file_name);
					
					$output -> writeln($messages[2]);
					
					
					$output -> writeln($e -> getMessage());
				}
				
				die();
			}
		}
		else {
			if($detect_os != "WINNT")
				$output -> writeln('<comment>' . $messages[3] . '</comment>');
			else	
				$output -> writeln($messages[3]);
		}
		
		$output -> writeln('');
		sleep_rand();
	}
	
	/*
		@request_url: http://www.androidapksfree.com/
		@author: peter
		@date: 2016/07/12
		functions:
			androidapks_free_html($urls, $html_contents)
			androidapks_free_pages($html_contents)
			androidapks_free_apk($html_contents)
			download_androidapks_file($link)
	*/
	
	function androidapks_free_html($urls, $html_contents) {
		$crawler = new Crawler($html_contents);
		$read_more_arr = $crawler -> filter('div.read-more');
		
		$download_pages = array();
		$index = 0;
		
		foreach($read_more_arr as $key => $value) {
			$crawler = new Crawler($value);
			$download_pages[$index] = $crawler -> filter('a') -> attr('href');
			$index++;
		}
		
		foreach($download_pages as $value) {
			$client = new Client();
			$response = $client -> request("GET", $value);
			androidapks_free_apk($response -> getBody() -> getContents());
		}
	}
	
	function androidapks_free_pages($html_contents) {
		$crawler = new Crawler($html_contents);
		$page_arr = $crawler -> filter('a[class="page-numbers"]');
		
		$max_page = 1;
		foreach($page_arr as $key => $value) {
			$crawler = new Crawler($value);
			$page = $crawler -> filter('a') -> text();
			
			if($page > $max_page)
				$max_page = $page;
		}
		
		return $max_page;
	}
	
	function androidapks_free_apk($html_contents) {
		$crawler = new Crawler($html_contents);
		$download_link_arr = $crawler -> filter('a');
		
		foreach($download_link_arr as $key => $value) {
			$crawler = new Crawler($value);

			$download_link = $crawler -> filter('a') -> attr('href') . "\n";
			
			if(strpos($download_link, ".apk")) {
				if(strpos($download_link, "/cdn-cgi/l/email-protection")) {
					$download_link = rawurldecode($download_link);
					$link_arr = explode("&", $download_link);
					$download_link = $link_arr[count($link_arr) - 1];
					$download_link = str_replace("body=", "", $download_link);
				}	
				
				download_androidapks_file($download_link);
			}
		}
	}
	
	function download_androidapks_file($link) {
		$link = trim($link);
		$link_arr = explode("/", $link);
		$file_name = $link_arr[count($link_arr)-1];
		$file_name = trim($file_name);
		$file_path = "./helper/files/androidapksfree/" . $file_name;
		
		$is_exists = false;
		$file_lists_path = "./helper/files/androidapksfree/file_lists.txt";
		$handle = @fopen($file_lists_path, "r");
		
		global $output;
		global $detect_os;
		global $messages;
		
		if(!$handle) {
			if($detect_os != "WINNT")
				$output -> writeln('<error>' . $messages[0] . '</error>');
			else
				$output -> writeln($messages[0]);
		}
		else {
			while(!feof($handle)) {
				$str = fgets($handle, 4096);
				$str = trim($str);
				
				if($str == $file_name) {
					$is_exists = true;
					fclose($handle);
					break;
				}
			}
		}
		
		if(!file_exists($file_path) && !$is_exists) {
			
			if($detect_os != "WINNT") {
				$output -> writeln('<info>' . $messages[1] . '</info>');
				
				$output -> writeln('<info>' . $file_name . '</info>');
			}
			else {
				$output -> writeln($messages[1]);
				
				$output -> writeln($file_name);

			}
			
			$client = new Client(["cookies" => true, 'headers' => ['Keep-Alive' => '1000', 'Connection' => 'keep-alive']]);
			$resource = fopen($file_path, 'w+');
			
			initial_bar();
			
			try {
				
				//replace .nope with empty
				$link = str_replace(".nope", "", $link);
				
				$response = $client -> request('GET', $link, ["verify" => false, "sink" => $resource, 'progress' => 
					function ($download_size, $downloaded_size, $upload_size, $uploaded_size) {
						// present the progress string
						if($download_size !=0) {
							$number = round(100 - (abs($download_size - $downloaded_size - 100) / $download_size * 100), 2);
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
				file_put_contents("./helper/files/androidapksfree/error_download_list.txt", $link . "\r\n", FILE_APPEND);
				
				if($detect_os != "WINNT") {
					
					$output -> writeln('');
					
					$output -> writeln('<error>' . "error download: " . $file_name . '</error>');
					
					$output -> writeln('<error>' . $messages[2] . '</error>');
					
					$output -> writeln('<error>' . $e -> getMessage() . '</error>');
				}
				else {
					$output -> writeln("error download: " . $file_name);
					
					$output -> writeln($messages[2]);

					$output -> writeln($e -> getMessage());
				}
				
				die();
			}
		}
		else {
			if($detect_os != "WINNT")
				$output -> writeln('<comment>' . $messages[3] . '</comment>');
			else	
				$output -> writeln($messages[3]);
		}
		
		$output -> writeln('');
		sleep_rand();
		
	}
	
	
	
	//sleep function
	function sleep_rand() {
		global $output;
		global $detect_os;
		global $messages;
		
		$sleep_number = rand(10, 20);
		
		if($detect_os != "WINNT")
			$output -> writeln('<info>' . "sleep " . $sleep_number . " seconds..." . '</info>');
		else
			$output -> writeln("sleep " . $sleep_number . " seconds...");
		
		sleep($sleep_number);
		
		if($detect_os != "WINNT")
			$output -> writeln('<info>' . $messages[4] . '</info>');
		else
			$output -> writeln($messages[4]);
		
		$output -> writeln('');
		$output -> writeln('');
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