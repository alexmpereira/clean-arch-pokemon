<?php

use App\Battle\Application\UseCases\Contracts\BattleRepository as BattleRepositoryInterface;
use App\Battle\Adapters\Repositories\BattleRepository;
use App\Market\Adapters\Repository\MarketRepository;
use App\Market\Application\UseCases\Contracts\MarketRepository as MarketRepositoryRepositoryInterface;
use App\Player\Adapters\Repository\PlayerRepository;
use App\Player\Application\UseCases\Contracts\PlayerRepository as PlayerRepositoryRepositoryInterface;
use App\Pokedex\Adapters\Repository\PokemonRepository;
use App\Pokedex\Adapters\Repository\TypeRepository;
use App\Pokedex\Application\UseCases\Contracts\PokemonRepository as PokemonRepositoryInterface;
use App\Pokedex\Application\UseCases\Contracts\TypeRepository as TypeRepositoryInterface;
use App\Pokedex\Application\UseCases\Contracts\PokedexRepository as PokedexRepositoryInterface;
use App\Pokedex\Adapters\Repository\PokedexRepository;
use App\Shared\Adapters\Gateways\Contracts\CacheSystem;
use App\Shared\Adapters\Gateways\Contracts\DatabaseConnection;
use App\Shared\Adapters\Gateways\Contracts\HttpClient;
use App\Shared\Adapters\Gateways\Contracts\PokemonAPI;
use App\Shared\Adapters\Gateways\Contracts\ValidatorTool;
use App\Shared\Adapters\Gateways\Database\MySQLConnection;
use App\Shared\Adapters\Gateways\GuzzleHttpClient;
use App\Shared\Adapters\Gateways\PokeAPI;
use App\Shared\Adapters\Gateways\PRedisClient;
use App\Shared\Adapters\Gateways\RespectValidation;
use App\Shared\Adapters\Gateways\TwigEngine;
use App\Shared\Adapters\Presentation\Contracts\TemplatePresenter;
use DI\Container;
use DI\ContainerBuilder;
use Predis\Client;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([
    // Adapters
    HttpClient::class => DI\autowire(GuzzleHttpClient::class),
    DatabaseConnection::class => DI\get('database'),
    CacheSystem::class => DI\get('cache'),
    PokemonAPI::class => DI\get('pokemonApi'),
    ValidatorTool::class => DI\autowire(RespectValidation::class),
    TemplatePresenter::class => DI\get('templatePresentation'),

    // Repositories
    PlayerRepositoryRepositoryInterface::class => DI\autowire(PlayerRepository::class),
    MarketRepositoryRepositoryInterface::class => DI\autowire(MarketRepository::class),
    BattleRepositoryInterface::class => DI\autowire(BattleRepository::class),
    PokemonRepositoryInterface::class => DI\autowire(PokemonRepository::class),
    TypeRepositoryInterface::class => DI\autowire(TypeRepository::class),
    PokedexRepositoryInterface::class => DI\autowire(PokedexRepository::class)
]);

$container = $containerBuilder->build();

$container->set('config', function() {
    return require __DIR__ . DS . 'config.php';
});

$container->set('database', function(Container $container) {
    $dbConfig = $container->get('config')['database'];
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";

    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT => TRUE
    ]);

    return new MySQLConnection($pdo);
});

$container->set('cache', function(Container $container) {
    $cacheConfig = $container->get('config')['cache'];

    $predis = new Client("redis://{$cacheConfig['host']}:{$cacheConfig['port']}");
    $predis->auth($cacheConfig['password']);

    return new PRedisClient($predis, $container->get('config')['cache']['params']);
});

$container->set('pokemonApi', function(Container $container) {
    $pokeApiConfig = $container->get('config')['externalApi']['pokeapi'];

    return new PokeAPI($container->get(HttpClient::class), $pokeApiConfig);
});

$container->set('templatePresentation', function(Container $container) {
    $config = $container->get('config')['templatePresentation'];

    $loader = new FilesystemLoader($config['viewsPath']);

    $twig = new Environment($loader, [
        'cache' => $config['enableCache'] === true ? $config['cachePath'] : false
    ]);

    return new TwigEngine($twig, $config);
});