<?php namespace Bugotech\Phar\Commands;

use Bugotech\Phar\Maker;
use Bugotech\IO\Filesystem;
use Illuminate\Console\Command;
use Bugotech\Phar\Events\AddFileEvent;

class CompilerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'phar:compiler';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile application in PHAR';

    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * Create a new service provider instance.
     *
     * @param \Bugotech\IO\Filesystem $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $config_phar = base_path('phar.json');
        if (! $this->files->exists($config_phar)) {
            throw new \Exception('Phar.json not found.');
        }

        $json = json_decode($this->files->get($config_phar));

        $this->info('Compiling Application');
        $this->info('Name.........: ' . $json->name);
        $this->info('Title........: ' . $json->title);
        $this->info('version......: ' . $json->version);
        $this->info('---------------------------------------------');

        event()->listen('Bugotech\Phar\Events\AddFileEvent', function (AddFileEvent $event) {
            $this->info('..' . $event->getInfo());
        });

        // Compilar
        $maker = new Maker($this->files, $json->name, $json->title, $json->version);
        $maker->build();

        // Executar scripts
        $this->runScripts('phar.scripts.after-build');
    }

    /**
     * Executar scripts.
     * @param $keyList
     */
    protected function runScripts($keyList)
    {
        $this->info('Run scripts');
        $this->laravel->configure('phar');
        $scripts = $this->laravel['config']->get($keyList, []);
        foreach ($scripts as $item) {
            try {
                $this->info('..' . $item);
                $out = [];
                exec($item, $out);
                $this->info('  ' . trim(implode("\r\n", $out)));
            } catch (\Exception $e) {
                $this->error(sprintf("Error build: %s\r\nMessage: %s", $item, $e->getMessage()));
            }
        }
    }
}
