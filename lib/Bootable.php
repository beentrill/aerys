<?php

namespace Aerys;

interface Bootable {
    /**
     * @return Middleware|callable|null to be used instead of the class implementing Bootable (which may also implement Middleware and/or be callable)
     */
    function boot(Server $server, Logger $logger);
}
