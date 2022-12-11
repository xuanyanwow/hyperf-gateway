<?php

declare(strict_types=1);
/**
 * This file is part of friendsofhyperf/components.
 * @link     https://github.com/friendsofhyperf/components
 * @document https://github.com/friendsofhyperf/components/blob/3.x/README.md
 * @contact  huangdijia@gmail.com
 */
namespace Friendsofhyperf\Gateway;

interface WorkerInterface
{
    public function start($daemon = false);

    public function onStart();

    public function onConnect($fd);

    public function onMessage($fd, $revData);

    public function onClose($fd);
}
