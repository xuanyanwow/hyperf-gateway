<?php

namespace Friendsofhyperf\Gateway;

interface WorkerInterface
{
    public function start($daemon = false);

    public function onStart();

    public function onConnect($clientId);

    public function onMessage($clientId, $revData);

    public function onClose($conn);
}