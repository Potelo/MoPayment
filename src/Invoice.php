<?php

namespace Potelo\MoPayment;

use Carbon\Carbon;
use Potelo\MoPayment\Moip\Invoice as Moip_Invoice;

class Invoice implements \JsonSerializable
{
    /**
     * The Moip invoice instance.
     *
     * @var \Potelo\MoPayment\Moip\Invoice
     */
    public $invoice;

    /**
     * Create a new invoice instance.
     *
     * @param  \Potelo\MoPayment\Moip\Invoice $invoice
     */
    public function __construct(Moip_Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Get a Carbon date for the invoice.
     *
     * @param  \DateTimeZone|string  $timezone
     * @return \Carbon\Carbon
     */
    public function date($timezone = null)
    {
        $carbon = Carbon::create(
            $this->invoice->creation_date->year,
            $this->invoice->creation_date->month,
            $this->invoice->creation_date->day,
            $this->invoice->creation_date->hour,
            $this->invoice->creation_date->minute,
            $this->invoice->creation_date->second
        );
        return $timezone ? $carbon->setTimezone($timezone) : $carbon;
    }

    /**
     * Get the total amount that was paid (or will be paid).
     *
     * @return string
     */
    public function total()
    {
        $total = $this->invoice->amount;
        return $total;
    }

    /**
     * Get the Moip invoice instance.
     *
     * @return \Potelo\MoPayment\Moip\Invoice
     */
    public function asMoipInvoice()
    {
        return $this->invoice;
    }

    /**
     * Dynamically get values from the Moip invoice.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->invoice->{$key};
    }

    public function jsonSerialize()
    {
        return get_object_vars($this->invoice);
    }
}