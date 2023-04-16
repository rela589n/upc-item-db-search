<?php

namespace App\Command;

use App\Shared\CsvIterator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Service\Attribute\Required;

#[AsCommand(
    name: 'app:search-upc-items',
    description: 'Search UPC items',
)]
class SearchUpcItemsCommand extends Command
{
    private HttpClientInterface $httpClient;
    private string $searchEndpoint;
    private $userKey;
    private $keyType;

    #[Required]
    public function setHttpClient(HttpClientInterface $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    #[Required]
    public function setSearchEndpoint(string $searchEndpoint): void
    {
        $this->searchEndpoint = $searchEndpoint;
    }

    #[Required]
    public function setUserKey($userKey): void
    {
        $this->userKey = $userKey;
    }

    #[Required]
    public function setKeyType($keyType): void
    {
        $this->keyType = $keyType;
    }



    protected function configure(): void
    {
        $this
            ->addOption('offset', mode: InputOption::VALUE_OPTIONAL, default: 0)
            ->addArgument('input', InputArgument::REQUIRED, 'Path to the input file')
            ->addArgument('output', InputArgument::REQUIRED, 'Path to the output file')
        ;
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $offset = (int)$input->getOption('offset');

        $in = $input->getArgument('input');
        $it = new CsvIterator($in);

        $outFileName = $input->getArgument('output');

        if (!file_exists($outFileName)) {
            $out = fopen($outFileName, 'wb');
            fputcsv($out, ['key', 'upc', 'name', 'brand', 'title']);
        } else {
            $out = fopen($outFileName, 'ab');
        }

        foreach ($it as $key => ['name' => $name, 'brand' => $brand]) {
            if ($key < $offset) {
                continue;
            }

            $io->note('Processing item #'.$key);

            $response = $this->httpClient->request(
                'GET',
                $this->searchEndpoint,
                [
                    'query' => [
                        's' => $name,
                        'brand' => $brand,
                    ],
                    'headers' => array_filter([
                        'user_key' => $this->userKey,
                        'key_type' => $this->keyType,
                        'Accept' => 'application/json',
                    ]),
                ]
            );

            if ($response->getStatusCode() !== 200) {
                $io->error('Failed to process item #'.$key);
                fclose($out);
                dd($response->toArray(false));
            }

            $responseArray = $response->toArray();

            foreach ($responseArray['items'] as ['upc' => $upc, 'title' => $title]) {
                fputcsv($out, [$key, $upc, $name, $brand, $title]);
            }

            $io->success('OK');
        }
        fclose($out);

        $io->success('Processing finished. Check out '.$outFileName);

        return Command::SUCCESS;
    }
}
