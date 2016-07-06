<?php
	use GuzzleHttp\Client;
	use Psr\Http\Message\ResponseInterface;
	use GuzzleHttp\Exception\RequestException;
	use Symfony\Component\DomCrawler\Crawler;

	//apkmirrors
	function parse_apkmirror_html($html_contents) {
		$crawler = new Crawler($html_contents);
		$link = $crawler->filter('a[class="fontBlack"]');
		$count = 0;
		$links = array();

		foreach ($link as $key => $value) {
			$crawler = new Crawler($value);
			$url = $crawler -> filter('a') -> attr('href');
			$res = in_array($url, $links);

			if(!$res) {
				$links[$count] = $url;
				$count += 1;	
			}
		}

		foreach ($links as $value) {
			file_put_contents("./helper/files/apkmirror.txt", $value . "\r\n", FILE_APPEND);
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
	 	$download_direct = $crawler -> filter('a[data-google-vignette="false"]');
	 	$link = $download_direct -> attr('href');

	 	if ($link == "#downloads") {
	 		//find correct download link

	 	}
	 	else {
	 		//download apk directly
	 		$client = new Client();
	 		$client->request('GET', $url . $link, [
    				'sink' => './helper/files/apkmirror',
			]);
            		}

            		return $link;
	}

?>