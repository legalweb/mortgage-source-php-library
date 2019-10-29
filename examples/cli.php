<?php

namespace {
    require_once __DIR__ . '/../vendor/autoload.php';
}

namespace {
    use GetOpt\GetOpt;
    use GetOpt\Option;
    use GetOpt\Command;
    use GetOpt\ArgumentException;
    use GetOpt\ArgumentException\Missing;
    use Legalweb\MortgageSourceClientExample\Lib\Commands\GetClientToken;

    define('NAME', 'run');
    define('VERSION', '1.0-alpha');

    $opt = new GetOpt();
    $opt->addOptions([
        Option::create(null, 'version', GetOpt::NO_ARGUMENT)->setDescription("Show version information and quit"),
        Option::create('?', 'help', GetOpt::NO_ARGUMENT)->setDescription("Show this help and quit"),
        Option::create('f', 'config', GetOpt::OPTIONAL_ARGUMENT)->setValidation('is_readable')->setDescription("Specify configuration file to use"),
        Option::create('c', 'client', GetOpt::OPTIONAL_ARGUMENT)->setDescription("Provide client login if no config file provided"),
        Option::create('s', 'secret', GetOpt::OPTIONAL_ARGUMENT)->setDescription("Provide secret if no config file provided"),
        Option::create('e', 'endpoint', GetOpt::OPTIONAL_ARGUMENT)->setDescription("Provide endpoint if no config file provided"),
    ]);

    $opt->addCommand(Command::create('test-setup', function() {
        echo "When you see this message the setup works." . PHP_EOL;
    })->setDescription("Check if setup works"));

    $opt->addCommand(new GetClientToken());

    try {
        try {
            $opt->process();
        } catch(Missing $exception) {
            if ($opt->getOption('help')) {
                throw $exception;
            }
        }
    } catch(ArgumentException $exception) {
        file_put_contents('php://stderr', $exception->getMessage() . PHP_EOL);
        echo PHP_EOL . $opt->getHelpText();
        exit;
    }

    // show version and quit
    if ($opt->getOption('version')) {
        echo sprintf('%s: %s' . PHP_EOL, NAME, VERSION);
        exit;
    }

    // show help and quit
    $command = $opt->getCommand();
    if (!$command || $opt->getOption('help')) {
        echo $opt->getHelpText();
        exit;
    }

    // call the requested command
    call_user_func($command->getHandler(), $opt);
}
