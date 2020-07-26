<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use ZipArchive;

ini_set('memory_limit', '-1');

class UpdateIp2locationCommand extends Command
{

    const IP2LOCATION_DOWNLOAD = 'https://www.ip2location.com/download/';
    const RATE_LIMIT_MESSAGE = 'THIS FILE CAN ONLY BE DOWNLOADED 5 TIMES PER HOUR';

    protected static $defaultName = 'app:update-ip2location';
    /**
     * @var string
     */
    private $ip2location_path;
    /**
     * @var string
     */
    private $ip2location_token;
    /**
     * @var string
     */
    private $ip2location_db;

    /**
     * UpdateIp2locationCommand constructor.
     * @param string $ip2location_path
     * @param string $ip2location_token
     * @param string $ip2location_db
     * @param string|null $name
     */
    public function __construct(string $ip2location_path, string $ip2location_token, string $ip2location_db, string $name = null)
    {
        $this->ip2location_path = $ip2location_path;
        $this->ip2location_token = $ip2location_token;
        $this->ip2location_db = $ip2location_db;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription('Download/Update ip2location database files.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $dbs = explode(',', $this->ip2location_db);

        $io->text('Checking if download folder exists.');
        if(!is_dir(sprintf('%s/downloads', $this->ip2location_path))){
            if(mkdir(sprintf('%s/downloads', $this->ip2location_path))){
                $io->success('Downloads folder created successfully');
            } else {
                $io->error(sprintf('Failed to create download folder, please create it manually. on %s/downloads', $this->ip2location_path));
            }
        }
        $io->text('Download folder is ready.');

        $io->text("Starting databases download...");

        $now = new \DateTime();

        foreach ($dbs as $db) {
            $io->text(sprintf('"%s"download is starting...', $db));
            $downloadUrl = sprintf('%s?token=%s&file=%s', self::IP2LOCATION_DOWNLOAD, $this->ip2location_token, $db);
            $io->text($downloadUrl);
            $dbContent = file_get_contents($downloadUrl);
            if($dbContent === self::RATE_LIMIT_MESSAGE){
                $io->error(sprintf('Rate limit hit for database "%s"', $db));
                continue;
            }
            $downloadPath = sprintf('%s/downloads/%s-%s.zip', $this->ip2location_path, $db, $now->getTimestamp());
            if(file_put_contents($downloadPath,$dbContent) !== false){
                $zip = new ZipArchive();
                if ($zip->open($downloadPath) === TRUE) {
                    $zip->extractTo(sprintf('%s/downloads/temp',$this->ip2location_path));
                    $zip->close();

                    $from = sprintf('%s/downloads/temp/IP2LOCATION-LITE-DB11.BIN', $this->ip2location_path);
                    $to = sprintf('%s/IP2LOCATION-LITE-DB3.BIN',$this->ip2location_path);
                    if(str_contains($db, 'IPV6')){
                        $from = sprintf('%s/downloads/temp/IP2LOCATION-LITE-DB11.IPV6.BIN',$this->ip2location_path);
                        $to = sprintf('%s/IP2LOCATION-LITE-DB3.IPV6.BIN',$this->ip2location_path);
                    }

                    if(rename($from, $to) === false){
                        $io->error('Failed to move database to correct folder.');
                    }
                } else {
                    $io->error(sprintf('Failed to unzip database "%s"', $db));
                    continue;
                }
            }

            $io->success(sprintf('Database "%s" downloaded successfully.', $db));
        }
        $this->deleteDirectory(sprintf('%s/downloads/temp/', $this->ip2location_path));
        $io->success('IP2Location is now up to date.');
        return 0;
    }
    function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }

        }

        return rmdir($dir);
    }

}
