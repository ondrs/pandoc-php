<?php

use Pandoc\Pandoc;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';


class PandocTest extends \Tester\TestCase
{

    /** @var Pandoc */
    private $pandoc;


    function setUp()
    {
        $this->pandoc = new Pandoc(TEMP_DIR, 'pandoc');
    }


    function testPrintVersion()
    {
        $v = $this->pandoc->getVersion();
        Assert::type('string', $v);
    }


    function testInvalidFromTypeTriggersException()
    {
        Assert::exception(function () {
            $this->pandoc->convert("#Test Content", "not_value", "plain");
        }, 'Pandoc\PandocException');
    }


    function testInvalidToTypeTriggersException()
    {
        Assert::exception(function () {
            $this->pandoc->convert("#Test Content", "html", "not_valid");
        }, 'Pandoc\PandocException');

    }


    function testBasicMarkdownToHTML()
    {
        $converted = $this->pandoc->convert("#Test Heading", "markdown_github", "html");

        Assert::equal('<h1 id="test-heading">Test Heading</h1>', trim($converted)); // yes, trim is necessary
    }


    function testRunWithConvertsBasicMarkdownToJSON()
    {
        $options = array(
            'from' => 'markdown',
            'to' => 'json',
        );

        $converted = $this->pandoc->runWith('#Heading', $options);

        Assert::equal('[{"unMeta":{}},[{"t":"Header","c":[1,["heading",[],[]],[{"t":"Str","c":"Heading"}]]}]]', trim($converted));
    }


    function testCanConvertMultipleSuccessfully()
    {
        $converted1 = $this->pandoc->convert(
            "### Heading 1",
            "markdown",
            "html"
        );

        $converted2 = $this->pandoc->convert(
            "<h3 id=\"heading-1\">Heading 1</h3>",
            "html",
            "markdown"
        );

        Assert::equal("<h3 id=\"heading-1\">Heading 1</h3>", trim($converted1));
        Assert::equal("### Heading 1", trim($converted2));
    }


}

id(new PandocTest)->run();
