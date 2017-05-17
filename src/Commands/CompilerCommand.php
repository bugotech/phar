<?php namespace Bugotech\Phar\Commands;

use Bugotech\IO\Filesystem;
use Bugotech\Phar\Maker;
use Illuminate\Console\Command;

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

        $maker = new Maker($this->files, $json->name, $json->title, $json->version);
        $maker->build();
    }
}
