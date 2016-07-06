<?php
	set_time_limit(0);
	ini_set("allow_url_fopen", "On");
	require "vendor/autoload.php";
	require "helper/parser.php";

	use GuzzleHttp\Client;
	use Psr\Http\Message\ResponseInterface;
	use GuzzleHttp\Exception\RequestException;
	use Symfony\Component\DomCrawler\Crawler;

	$page = 1;
	$base_urls = array(
		"http://www.apkmirror.com",
		"https://gamesapk.net",
		"http://www.androidappsgame.com/",
		"http://www.androidapksfree.com/"
	);

	if(file_exists("helper/files/apkmirror.txt")) {
		$handler = fopen("./helper/files/apkmirror.txt", "r");

              	if($handler) {
              		while(!feof($handler)) {
              			$str = fgets($handler, 4096);
              			$client = new Client();
              			$promise = $client -> getAsync($base_urls[0] . $str);
              			$promise->then(
					function (ResponseInterface $res) {
    						get_apkmirror_apk($res -> getBody() -> getContents());
    					},
    					function (RequestException $e) {
        						echo $e->getMessage() . "\n";
    					}
				);
                        		}

                        		fclose($handler);
                	}
                	
		exit();
	}

	$contents = array();
	$pages = 0;

	$client = new Client();
	
	$response = $client -> get($base_urls[0]);
	
	if($response -> getStatuscode() == 200)
		$pages = get_apkmirror_pages($response -> getBody() -> getContents());
	else
		die("The code is not 200.");

	for($page=1;$page<=$pages;$page++) {
		$promise = $client -> getAsync($base_urls[0] . "/page/" . $page . "/");
		$promise->then(
			function (ResponseInterface $res) {
    				parse_apkmirror_html($res -> getBody() -> getContents());
    			},
    			function (RequestException $e) {
        				echo $e->getMessage() . "\n";
    			}
		);

		$promise -> wait();
	}

?>