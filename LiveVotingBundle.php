<?php

namespace Netgen\LiveVotingBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class LiveVotingBundle extends Bundle
{
    public function getParent()
    {
        return 'TwigBundle';
    }
}
