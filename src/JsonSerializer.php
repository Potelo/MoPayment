<?php

namespace Potelo\MoPayment;

trait JsonSerializer {
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}