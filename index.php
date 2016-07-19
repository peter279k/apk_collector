<?php
	set_time_limit(0);
	ini_set("allow_url_fopen", "On");
	require "vendor/autoload.php";
	require "helper/greet.php";
	require "helper/parser.php";
	require "helper/fix_download.php";

	use GuzzleHttp\Client;
	use Psr\Http\Message\ResponseInterface;
	use GuzzleHttp\Exception\RequestException;
	
	use Symfony\Component\Console\Command\Command;
	use Symfony\Component\Console\Input\InputArgument;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Input\InputOption;
	use Symfony\Component\Console\Output\OutputInterface;
	use Symfony\Component\Console\Application;
	use Symfony\Component\Console\Question\ChoiceQuestion;
	
	class download_command extends Command {
		private $urls = array(
			'http://www.apkmirror.com', 'http://www.androidapksfree.com/', 'fixing error when downloading apk'
		);
		
		protected function configure() {
			$this
				-> setName('download')
				-> setDescription('what kinds of file (default: apk)?')
				-> addArgument(
					'action',
					InputArgument::OPTIONAL);
		}
		
		protected function execute(InputInterface $input, OutputInterface $output) {
			
			$action = $input->getArgument('action');
			
			if($action == "apk" || $action == "") {
				$helper = $this -> getHelper('question');
				$question = new ChoiceQuestion('Please select the url that you want to download (the default url is http://www.apkmirror.com)',
					$this -> urls, 0);
				
				$question -> setErrorMessage('The apk url %s is invalid.');
				
				$url = $helper -> ask($input, $output, $question);
				
				$output -> writeln('You selected: ' . $url);
				
				if($url != "fixing error when downloading apk")
					echo $this -> request_initial($url, $output);
				else
					fix_download_apk();
			}
		}
		
		private function request_initial($url, $output) {
			$urls = $this -> urls;
			$pages = 0;

			$client = new Client();
	
			try {
		
				$response = $client -> get($url);
				
				if($response -> getStatuscode() == 200) {
					$dir_path = "";
					
					switch($url) {
						case $urls[0]:
							$pages = get_apkmirror_pages($response -> getBody() -> getContents());
							$dir_path = "./helper/files/apkmirror";
							break;
						case $urls[1]:
							$pages = androidapks_free_pages($response -> getBody() -> getContents());
							$dir_path = "./helper/files/androidapksfree";
							break;
						case $urls[2]:
							fix_download_apk();
							break;
					}
					
					@mkdir($dir_path);
				}
				else {
					die($output -> writeln($response -> getBody() -> getContents()));
				}
			}
			catch(Exception $e) {
				die($output -> writeln($e -> getMessage()));
			}
	
			/*
			*	Firstly, run page 1
			*	and some applications have downloaded successfully.
			*	e.g. Changing the page 1 to page 122. (up to the situation.)
			*/
			
			$page = 1;
			if(file_exists("./curr_page.txt")) {
				$curr_page = file_get_contents("./curr_page.txt");
				$page = $curr_page;
			}
			
			for(;$page<=$pages;$page++) {
				//record the current page
				file_put_contents("./curr_page.txt", $page);
				try {
					$response = $client -> get($url . "/page/" . $page . "/");
				}
				catch(Exception $e) {
					die($output -> writeln($e -> getMessage()));
				}
				
				switch($url) {
					case $urls[0]:
						parse_apkmirror_html($url, $response -> getBody() -> getContents());
						break;
					case $urls[1]:
						androidapks_free_html($url, $response -> getBody() -> getContents());
						break;
				}
			}
		}
	}
	
	$command = new greet();
	$application = new Application('apk_collector', 'beta-1.0');
	$application -> add($command);
	$application -> add(new download_command());
	$application -> setDefaultCommand($command -> getName());
	$application -> run();

?>