<?php
	set_time_limit(0);
	ini_set("allow_url_fopen", "On");
	require "vendor/autoload.php";
	require "helper/parser.php";

	use GuzzleHttp\Client;
	use Psr\Http\Message\ResponseInterface;
	use GuzzleHttp\Exception\RequestException;
	
	$page = 1;
	$base_urls = array(
		"http://www.apkmirror.com",
		"https://gamesapk.net",
		"http://www.androidappsgame.com/",
		"http://www.androidapksfree.com/"
	);

	$contents = array();
	$pages = 0;

	$client = new Client();
	
	try {
		
		$response = $client -> get($base_urls[0]);
	
		if($response -> getStatuscode() == 200)
			$pages = get_apkmirror_pages($response -> getBody() -> getContents());
		else
			die("The code is not 200.");
	}
	catch(Exception $e) {
		die($e -> getMessage());
	}
	
	/*
	*	Firstly, run page 1
	*	and some applications have downloaded successfully.
	*	Changing the page 1 to page 122. (up to the situation.)
	*/
	
	for($page=1;$page<=$pages;$page++) {
		//record the current page
		file_put_contents("./curr_page.txt", $page);
		
		try {
			$response = $client -> get($base_urls[0] . "/page/" . $page . "/");
		}
		catch(Exception $e) {
			die($e -> getMessage());
		}
		
		parse_apkmirror_html($base_urls[0], $response -> getBody() -> getContents());
		
		if($page == 1) {
			$page = 122;
		}
		
	}

?>