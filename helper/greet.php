<?php
	use Symfony\Component\Console\Command\Command;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\OutputInterface;
	
	class greet extends Command {
		protected function configure() {
			$this
				->setName('greet:start')
				->setDescription('Outputs \'Hello apk_collector\'');
		}
		
		protected function execute(InputInterface $input, OutputInterface $output) {
			$output -> writeln('Thank you for using the apk_collsctor !');
			$output -> writeln('Here are some useful commands:');
			$output -> writeln('');
			$output -> writeln('download');
			$output -> writeln('	To downloading the apk file, you have to use this command and');
			$output -> writeln('	it will present a list to let you select the apk mirror url');
			$output -> writeln('	that you want to use.');
		}
	}
?>