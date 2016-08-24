<?php
/**
 * Pandoc PHP
 *
 * Copyright (c) Ryan Kadwell <ryan@riaka.ca>
 * Copyright (c) Ondrej Plsek <plsek.o@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pandoc;

use Symfony\Component\Process\ProcessBuilder;

/**
 * Naive wrapper for haskell's pandoc utility
 *
 * @author Ryan Kadwell <ryan@riaka.ca>
 */
class Pandoc
{
    /**
     * Where is the executable located
     * @var string
     */
    private $executable;

    /**
     * Where to take the content for pandoc from
     * @var string
     */
    private $tmpFile;

    /**
     * List of valid input types
     * @var array
     */
    private $inputFormats = array(
        'native',
        'json',
        'markdown',
        'markdown_strict',
        'markdown_phpextra',
        'markdown_github',
        'markdown_mmd',
        'rst',
        'mediawiki',
        'docbook',
        'textile',
        'html',
        'latex'
    );

    /**
     * List of valid output types
     * @var array
     */
    private $outputFormats = array(
        'native',
        'json',
        'docx',
        'odt',
        'epub',
        'epub3',
        'fb2',
        'html',
        'html5',
        's5',
        'slidy',
        'slideous',
        'dzslides',
        'docbook',
        'opendocument',
        'latex',
        'beamer',
        'context',
        'texinfo',
        'man',
        'markdown',
        'markdown_strict',
        'markdown_phpextra',
        'markdown_github',
        'markdown_mmd',
        'plain',
        'rst',
        'mediawiki',
        'textile',
        'rtf',
        'org',
        'asciidoc'
    );

    /**
     * Setup path to the pandoc binary
     *
     * @param string $tempDir
     * @param string $executable Path to the pandoc executable
     * @throws PandocException
     */
    public function __construct($tempDir, $executable = NULL)
    {
        $this->tmpFile = $tempDir . '/' . uniqid('pandoc');

        // Since we can not validate that the command that they give us is
        // *really* pandoc we will just check that its something.
        // If the provide no path to pandoc we will try to find it on our own
        if (!$executable) {
            $builder = new ProcessBuilder(array(
                'which',
                'pandoc',
            ));

            $process = $builder->getProcess();
            $process->run();

            if (!$process->isSuccessful()) {
                throw new PandocException($process->getErrorOutput());
            }

            $this->executable = $process->getOutput();

        } else {
            $this->executable = $executable;
        }

    }

    /**
     * @param string $tmpFile
     * @return $this
     */
    public function setTmpFile($tmpFile)
    {
        $this->tmpFile = $tmpFile;
        return $this;
    }


    /**
     * @return string
     */
    public function getTmpFile()
    {
        return $this->tmpFile;
    }


    /**
     * Run the conversion from one type to another
     *
     * @param string $content
     * @param string $from The type we are converting from
     * @param string $to The type we want to convert the document to
     *
     * @throws PandocException
     * @return string
     */
    public function convert($content, $from, $to)
    {
        if (!in_array($from, $this->inputFormats)) {
            throw new PandocException("$from is not a valid input format for pandoc");
        }

        if (!in_array($to, $this->outputFormats)) {
            throw new PandocException("$to is not a valid output format for pandoc");
        }

        file_put_contents($this->tmpFile, $content);
        @chmod($this->tmpFile, 0777);

        $builder = new ProcessBuilder(array(
            $this->executable,
            $this->tmpFile,
            "--from=$from",
            "--to=$to",
            "-o",
            $this->tmpFile
        ));

        $process = $builder->getProcess();
        $process->run();

        if (!$process->isSuccessful()) {
            throw new PandocException($process->getErrorOutput());
        }

        return file_get_contents($this->tmpFile);
    }


    /**
     * Run the pandoc command with specific options.
     *
     * Provides more control over what happens. You simply pass an array of
     * key value pairs of the command options omitting the -- from the start.
     * If you want to pass a command that takes no argument you set its value
     * to null.
     *
     * @param string $content The content to run the command on
     * @param array $options The options to use
     *
     * @throws PandocException
     * @return string The returned content
     */
    public function runWith($content, $options)
    {
        $commandOptions = array();

        $extFilesFormat = array(
            'docx',
            'odt',
            'epub',
            'fb2',
            'pdf'
        );

        $extFilesHtmlSlide = array(
            's5',
            'slidy',
            'dzslides',
            'slideous'
        );

        $builder = new ProcessBuilder();
        $builder->setPrefix(array(
            $this->executable,
            $this->tmpFile,
        ));

        $args = array();

        foreach ($options as $key => $value) {
            if ($key == 'to' && in_array($value, $extFilesFormat)) {
                $args = array_merge($args, array(
                    '-s',
                    '-S',
                    '-o',
                    "$this->tmpFile.$value",
                ));
                $format = $value;
                continue;
            } else if ($key == 'to' && in_array($value, $extFilesHtmlSlide)) {
                $args = array_merge($args, array(
                    '-s',
                    '-t',
                    $value,
                    '-o',
                    "$this->tmpFile.html",
                ));
                $format = 'html';
                continue;
            } else if ($key == 'to' && $value == 'epub3') {
                $args = array_merge($args, array(
                    '-S',
                    '-o',
                    "$this->tmpFile.epub",
                ));
                $format = 'epub';
                continue;
            } else if ($key == 'to' && $value == 'beamer') {
                $args = array_merge($args, array(
                    '-s',
                    '-t',
                    'beamer',
                    '-o',
                    "$this->tmpFile.pdf",
                ));
                $format = 'pdf';
                continue;
            } else if ($key == 'to' && $value == 'latex') {
                $args = array_merge($args, array(
                    '-s',
                    '-o',
                    "$this->tmpFile.tex",
                ));
                $format = 'tex';
                continue;
            } else if ($key == 'to' && $value == 'rst') {
                $args = array_merge($args, array(
                    '-s',
                    '-t',
                    'rst',
                    '--toc',
                    '-o',
                    "$this->tmpFile.text",
                ));
                $format = 'text';
                continue;
            } else if ($key == 'to' && $value == 'rtf') {
                $args = array_merge($args, array(
                    '-s',
                    '-o',
                    "$this->tmpFile.$value",
                ));
                $format = $value;
                continue;
            } else if ($key == 'to' && $value == 'docbook') {
                $args = array_merge($args, array(
                    '-s',
                    '-S',
                    '-t',
                    'docbook',
                    '-o',
                    "$this->tmpFile.db",
                ));
                $format = 'db';
                continue;
            } else if ($key == 'to' && $value == 'context') {
                $args = array_merge($args, array(
                    '-s',
                    '-t',
                    'context',
                    '-o',
                    "$this->tmpFile.tex",
                ));
                $format = 'tex';
                continue;
            } else if ($key == 'to' && $value == 'asciidoc') {
                $args = array_merge($args, array(
                    '-s',
                    '-S',
                    '-t',
                    'asciidoc',
                    '-o',
                    "$this->tmpFile.txt",
                ));
                $format = 'txt';
                continue;
            }


            if (NULL === $value) {
                $args[] = "--$key";
                continue;
            }

            $args[] = "--$key=$value";
        }

        file_put_contents($this->tmpFile, $content);
        @chmod($this->tmpFile, 0777);


        if (!isset($format)) {
            $args[] = "-o";
            $args[] = $this->tmpFile;
        }

        $process = $builder->setArguments($args)
            ->getProcess();

        $process->run();

        if (!$process->isSuccessful()) {
            throw new PandocException($process->getErrorOutput());
        }

        return isset($format)
            ? file_get_contents($this->tmpFile . '.' . $format)
            : file_get_contents($this->tmpFile);
    }


    /**
     * Remove the temporary files that were created
     */
    public function __destruct()
    {
        if (file_exists($this->tmpFile)) {
            @unlink($this->tmpFile);
        }

        if ($list = glob($this->tmpFile . '.*')) {
            foreach ($list as $filename) {
                @unlink($filename);
            }
        }
    }


    /**
     * @return string
     * @throws PandocException
     */
    public function getVersion()
    {
        $builder = new ProcessBuilder(array(
            $this->executable,
            '--version'
        ));

        $process = $builder->getProcess();
        $process->run();

        if (!$process->isSuccessful()) {
            throw new PandocException($process->getErrorOutput());
        }

        $output = $process->getOutput();
        $output = explode("\n", $output);

        return trim(str_replace('pandoc', '', $output[0]));
    }
}
