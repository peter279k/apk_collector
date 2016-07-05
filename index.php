<?php
	ini_set("allow_url_fopen", "On");
	require "vendor/autoload.php";
	
	use GuzzleHttp\Client;
	use Psr\Http\Message\ResponseInterface;
	use GuzzleHttp\Exception\RequestException;
	
	$base_urls = array(
		"",
		"",
		"",
		"",
		"",
		""
	);
	
	$client = new Client();
	
	$check_site = true;
	
	$promise = $client -> getAsync('http://docs.guzzlephp.org/en/latest/quickstart.html#making-a-request');
	
	$promise -> then(
		function (ResponseInterface $res) {
			echo $res->getStatusCode() . "\n";
		},
		function (RequestException $e) {
			echo $e->getMessage();
			$check_site = false;
		}
	);
	
	$promise = $client -> getAsync('http://docs.guzzlephp.org/en/latest/quickstart.html#making-a-request');
	
	$promise -> then(
		function (ResponseInterface $res) {
			echo $res->getStatusCode() . "\n";
		},
		function (RequestException $e) {
			echo $e->getMessage();
			$check_site = false;
		}
	);
	
	$promise -> wait();
	
	if($check_site) {
		echo "The site active";
	}
	else {
		echo "The site is dead temporarily.";
	}
	
?>