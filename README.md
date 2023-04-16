# UPC item DB

A simple project, which provides cli tool for https://www.upcitemdb.com/ search

## Installation

```shell
docker compose up -d
```

```shell
docker compose exec -iT php composer install
```

## Searching by file

### Input File format

File should be a csv with 2 columns:
- name (used in `?s=` query param)
- brand (used in `?brand=` query param)

See `docs/input.example.csv` as an example.

### Output file format

File has the following structure:
- key (the ordinal number from input file)
- upc (found UPC)
- name (name from the source file)
- brand (brand from the source file)
- title (found title)

See `docs/output.example.csv` as an example.

### Search command

There's `app:search-upc-items` console command. Place your input file in `var/input.cvs` and then run command.

It will scan the input file, make the requests to api and yield the output into the output file. 
Optionally, you may pass `--offset` option in order to skip processing of items from the beginning of the file (if some items were already processed on previous command run). 

```shell
docker compose exec php bash
bin/console app:search-upc-items ./var/input.csv ./var/output.csv --offset=0
```

## Configuration

See `config/services.yaml`. Make sure to adjust it if necessary.

There are following parameters:
- `$searchEndpoint` - url to send the requests to;
- `$userKey` - access key;
- `$keyType` - access key type.
