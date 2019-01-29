<?php

namespace OpxCore\Log;

trait MessageFormatter
{
    public function interpolateMessage($message, $context): string
    {
        /** @var \Exception $exception */
        $exception = $context['exception'] ?? null;

        if($exception instanceof \Exception) {
            $stackTrace = $exception->getTraceAsString();
        }

        // build a replacement array with braces around the context keys
        $replace = [];

        foreach ($context as $key => $val) {
            // check that the value can be casted to string
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        // interpolate replacement values into the message and return
        $processed = strtr($message, $replace);

        if(isset($stackTrace)) {
            $processed .= "\n{$stackTrace}";
        }

        return $processed;
    }
}