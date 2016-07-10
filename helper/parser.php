<?php
	use GuzzleHttp\Client;
	use Psr\Http\Message\ResponseInterface;
	use GuzzleHttp\Exception\RequestException;
	use Symfony\Component\DomCrawler\Crawler;
	
	use Symfony\Component\Console\Output\ConsoleOutput;
	use Symfony\Component\Console\Helper\ProgressBar;
	
	$output = new ConsoleOutput();
	
	$progress_bar = null;
	
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
				echo $e -> getMessage() . "\n";
				sleep_rand();
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
			echo $e -> getMessage() . "\n";
			file_put_contents("./helper/files/apkmirror/error_link_" . time() . ".txt", $html_contents, FILE_APPEND);
			//abort downloading apk file.
			return false;
		}
		
	 	if ($link == "#downloads") {
	 		//finding correct download link
			//e.g. /apk/facebook-2/facebook/facebook-85-0-0-5-70-release/facebook-85-0-0-5-70-android-apk-download/
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
		
		$handle = @fopen("./helper/files/apkmirror/file_lists.txt", "r");
		
		if(!$handle) {
			echo "cannot find the file_lists.txt\n";
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
			echo "downloading the apk files...\n";
			echo $file_name . "\n";
			
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
			}
			catch(Exception $e) {
				file_put_contents("./helper/files/apkmirror/error_download_list.txt", $url . $link . "\r\n", FILE_APPEND);
				echo "error download: " . $file_name . "\n";
				echo "The Network error happened.\n";
				echo $e -> getMessage() . "\n";
			}
		}
		else {
			echo "the apk file is existed.\n";
		}
		
		sleep_rand();
	}
	
	//sleep function
	function sleep_rand() {
		$sleep_number = rand(30, 60);
		echo "\nsleep " . $sleep_number . " seconds...\n";
		sleep($sleep_number);
		echo "wake up !\n\n";
	}
	
	//initial progress bar
	function initial_bar() {
		global $progress_bar;
		global $output;
		
		$progress_bar = new ProgressBar($output, 100);
		$progress_bar -> setOverwrite(true);
		$progress_bar -> start();
	}
?>