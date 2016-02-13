<?php

namespace Limenius\ReactBundle\Renderer;

use Nacmartin\PhpExecJs\PhpExecJs;

class ReactRenderer
{
    protected $logger;
    protected $phpExecJs;
    protected $serverBundlePath;

    public function __construct($logger, PhpExecJs $execJs, $serverBundlePath, $failLoud = false)
    {
        $this->logger = $logger;
        $this->phpExecJs = $execJs;
        $this->serverBundlePath = $serverBundlePath;
    }

    public function setServerBundlePath($serverBundlePath)
    {
        $this->serverBundlePath = $serverBundlePath;
    }

    public function render($componentName, $propsString, $uuid)
    {
        $serverBundle = file_get_contents($this->serverBundlePath);
        throw new \Exception($this->serverBundlePath);
        $this->phpExecJs->createContext($this->consolePolyfill()."\n".$serverBundle);
        $result = json_decode($this->phpExecJs->evalJs($this->wrap($componentName, $propsString, $uuid)), true);
        //throw new \Exception(var_export($result, true));
        if ($result['hasErrors']) {
            $this->LogErrors($result['consoleReplayScript']);
        }
        return $result['html'].$result['consoleReplayScript'];
    }

    protected function consolePolyfill()
    {
        $console = <<<JS
var console = { history: [] };
['error', 'log', 'info', 'warn'].forEach(function (level) {
  console[level] = function () {
    var argArray = Array.prototype.slice.call(arguments);
    if (argArray.length > 0) {
      argArray[0] = '[SERVER] ' + argArray[0];
    }
    console.history.push({level: level, arguments: argArray});
  };
});
JS;
        return $console;
    }

    protected function wrap($name, $propsString, $uuid)
    {
        $wrapperJs = <<<JS
(function() {
  var props = $propsString;
  return ReactOnRails.serverRenderReactComponent({
    name: '$name',
    domNodeId: '$uuid',
    props: props,
    trace: false,
    location: ''
  });
})()
JS;
        return $wrapperJs;
    }

    protected function logErrors($consoleReplay)
    {
        $lines = explode("\n", $consoleReplay);
        $usefulLines = array_slice($lines, 2, count($lines) - 4);
        foreach ($usefulLines as $line) {
            if (preg_match ('/console\.error\.apply\(console, \["\[SERVER\] (?P<msg>.*)"\]\);/' , $line, $matches)) {
                $this->logger->warning($matches['msg']);
            }
        }
    }
}
