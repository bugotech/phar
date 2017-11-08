<?php namespace Bugotech\Phar;

use Phar;
use SplFileInfo;
use Bugotech\IO\Filesystem;
use Symfony\Component\Finder\Finder;
use Bugotech\Phar\Events\AddFileEvent;

class Maker
{
    /**
     * Application Name.
     * @var string
     */
    protected $name = '';

    /**
     * Application Title.
     * @var string
     */
    protected $title = '';

    /**
     * Application Version.
     * @var string
     */
    protected $version = '';

    /**
     * Phar Alias.
     * @var string
     */
    protected $alias = '';

    /**
     * @var string
     */
    protected $filePhar = '';

    /**
     * @var string
     */
    protected $fileBat = '';

    /**
     * @var string
     */
    public $fileNameBin = 'artisan';

    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * @var string
     */
    protected $pathBase = '';

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @param Filesystem $files
     * @param $name
     * @param $title
     * @param $version
     * @param $pathBase
     */
    public function __construct($files, $name, $title, $version, $pathBase)
    {
        $this->files = $files;
        $this->pathBase = $pathBase;
        $this->setName($name);
        $this->title = $title;
        $this->version = $version;
    }

    /**
     * Gerar.
     */
    public function build()
    {
        $this->resetPhar();

        $this->makePhar();

        $this->makeBat();
    }

    /**
     * Gerar arquivo PHAR.
     */
    protected function makePhar()
    {
        $phar = new Phar($this->filePhar, 0, $this->alias);
        $phar->setSignatureAlgorithm(\Phar::SHA1);
        $phar->startBuffering();

        // Adicionar arquivos
        $this->addFiles($phar);

        // Adicionar arquivo bin
        $this->addBinFile($phar);

        // Adicionar Stup
        $this->addStub($phar);

        // Adicionar licenca
        $this->addLicence($phar);

        // Adicionar arquivo de update
        //$this->addUpdateFile($phar);

        // Finalizar phar.
        $phar->stopBuffering();
        unset($phar);
    }

    /**
     * Gerar arquivo BAT.
     */
    protected function makeBat()
    {
        $content = $this->files->get(__DIR__ . '/../stubs/bat.stub');
        $content = str_replace('DubbyAlias', $this->alias, $content);
        $this->files->put($this->fileBat, $content);
    }

    /**
     * Reiniciar arquivos compilados.
     */
    protected function resetPhar()
    {
        if ($this->files->exists($this->filePhar)) {
            $this->files->delete($this->filePhar);
        }

        if ($this->files->exists($this->fileBat)) {
            $this->files->delete($this->fileBat);
        }
    }

    /**
     * Set name app.
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
        $this->alias = $name . '.phar';
        $this->filePhar = $this->files->combine($this->pathBase, $this->alias);
        $this->fileBat = $this->files->combine($this->pathBase, $name . '.bat');
    }

    /**
     * Get name app.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get title app.
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get version app.
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Adicionar arquivos.
     * @param Phar $phar
     */
    public function addFiles(Phar $phar)
    {
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->name('*.stub')
            ->exclude('Tests')
            ->exclude('tests')
            ->exclude('/storage')
            ->exclude('/config')
            ->exclude('/build')
            ->in($this->pathBase);
        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }
    }

    /**
     * Adicionar arquivo tratado no phar.
     *
     * @param Phar $phar
     * @param SplFileInfo $file
     * @param bool $strip
     */
    private function addFile(Phar $phar, SplFileInfo $file, $strip = false)
    {
        // Forçar tirar espaços de PHP.
        $strip = ($file->getExtension() == 'php') ? true : $strip;

        $this->fireEvent($file->getRealPath());

        $path = str_replace($this->pathBase, '', $file->getRealPath());
        $content = $this->files->get($file);

        // Tratar espacos?
        if ($strip) {
            $content = $this->stripWhitespace($content);
        }

        // Eh arquivo de licenca?
        if ('LICENSE' === basename($file)) {
            $content = "\n" . $content . "\n";
        }

        // Tratar parametros
        foreach ($this->params as $pk => $pv) {
            $pk = sprintf('@%s@', $pk);
            $content = str_replace($pk, $pv, $content);
        }
        $phar->addFromString($path, $content);
    }

    /**
     * Adicionar arquivo BIN.
     * @param Phar $phar
     */
    protected function addBinFile(Phar $phar)
    {
        $fileBin = $this->files->combine($this->pathBase, $this->fileNameBin);
        if (! $this->files->exists($fileBin)) {
            return;
        }

        $this->fireEvent('BIN: file main');

        $content = $this->files->get($fileBin);
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString('artisan', $content);
    }

    /**
     * Adicionar Stub.
     * @param Phar $phar
     */
    protected function addStub(Phar $phar)
    {
        $this->fireEvent('STUB');

        // Add warning once the phar is older than 30 days
        $defineTime = '';
        if (array_key_exists('package_version', $this->params)) {
            $warningTime = time() + 30 * 86400;
            $defineTime = "define('PHAR_DEV_WARNING_TIME', $warningTime);\n";
        }

        $content = $this->files->get(__DIR__ . '/../stubs/compile.stub');
        $content = str_replace('DubbyAlias', $this->alias, $content);
        $content = str_replace('DubbyDefineDateTime', $defineTime, $content);

        $phar->setStub($content);
    }

    /**
     * Adicionar arquivo de licenca.
     * @param Phar $phar
     */
    protected function addLicence(Phar $phar)
    {
        $file = $this->files->combine($this->pathBase, 'LICENSE');
        if (! $this->files->exists($file)) {
            return;
        }

        $this->fireEvent('LICENCE');
        $this->addFile($phar, new SplFileInfo($file), false);
    }

    /**
     * Adicionar arquivo para update.
     * @param Phar $phar
     */
    protected function addUpdateFile(Phar $phar)
    {
        $file = $this->files->combine($this->pathBase, 'update.json');
        if ($this->files->exists($file)) {
            $this->addFile($phar, new SplFileInfo($file), false);
        }
    }

    /**
     * Removes whitespace from a PHP source string while preserving line numbers.
     *
     * @param  string $source A PHP string
     * @return string The PHP string with the whitespace removed
     */
    private function stripWhitespace($source)
    {
        // Verificar se token_get_all existe
        if (! function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (in_array($token[0], [T_COMMENT, T_DOC_COMMENT])) {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);
                $output .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }

    /**
     * Executar evento.
     * @param $filename
     */
    protected function fireEvent($filename)
    {
        event()->fire(new AddFileEvent($filename));
    }
}