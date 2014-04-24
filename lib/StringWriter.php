<?php

namespace Aerys;

use Alert\Reactor, Alert\Promise, Alert\Failure;

class StringWriter implements Writer {
    private $reactor;
    private $socket;
    private $writeWatcher;
    private $buffer;
    private $promise;
    private $mustClose;
    
    public function __construct(Reactor $reactor) {
        $this->reactor = $reactor;
    }

    public function prepareSubject($subject) {
        $this->socket = $subject->socket;
        $this->writeWatcher = $subject->writeWatcher;
        $this->buffer = ($subject->headers . $subject->body);
        $this->mustClose = $subject->mustClose;
    }

    public function writeResponse() {
        $bytesWritten = @fwrite($this->socket, $this->buffer);

        if ($bytesWritten === strlen($this->buffer)) {
            return $this->promise ? $this->fulfillWritePromise() : $this->mustClose;
        } elseif ($bytesWritten > 0) {
            $this->buffer = substr($this->buffer, $bytesWritten);
            return $this->promise ?: $this->makeWritePromise();
        } elseif (is_resource($this->socket)) {
            return $this->promise ?: $this->makeWritePromise();
        } elseif ($this->promise) {
            $this->failWritePromise(new TargetPipeException);
        } else {
            return new Failure(new TargetPipeException);
        }
    }

    private function makeWritePromise() {
        $this->promise = new Promise;
        $this->reactor->enable($this->writeWatcher);

        return $this->promise->getFuture();
    }

    private function fulfillWritePromise() {
        $this->reactor->disable($this->writeWatcher);
        $this->promise->succeed($this->mustClose);
    }

    private function failWritePromise(\Exception $e) {
        $this->reactor->disable($this->writeWatcher);
        $this->promise->fail($e);
    }
}