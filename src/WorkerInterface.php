<?php

namespace Friendsofhyperf\Gateway;

interface WorkerInterface
{
    public function start($daemon = false);

    public function onStart();

    public function onConnect($fd);

    public function onMessage($fd, $revData);

    public function onClose($fd);
}