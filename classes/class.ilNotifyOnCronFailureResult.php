<?php

require_once './Services/Cron/classes/class.ilCronJobResult.php';

/**
 * Class ilNotifyOnCronFailureResult
 *
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */
class ilNotifyOnCronFailureResult extends ilCronJobResult
{

    /**
     * @param      $status  int
     * @param      $message string
     * @param null $code    string
     */
    public function __construct($status, $message)
    {
        $this->setStatus($status);
        $this->setMessage($message);
    }
}
