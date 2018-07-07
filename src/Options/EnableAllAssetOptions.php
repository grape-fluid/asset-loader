<?php

namespace Grapesc\GrapeFluid\Options;


class EnableAllAssetOptions implements IAssetOptions
{

    /**
     * @param string $option
     * @return bool
     */
    public function getOption($option)
    {
        return true;
    }
}